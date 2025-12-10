<?php

declare(strict_types=1);

/*
 * This file is part of the FULLHAUS PHP-CS-Fixer configuration.
 *
 * (c) 2025 FULLHAUS GmbH
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace FULLHAUS\CodingStandards\Fixer;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * Custom fixer for adding/managing header comments in mono repo projects.
 *
 * This fixer allows configuring different headers per project within a mono repo structure.
 */
final class HeaderCommentFixer extends AbstractFixer implements ConfigurableFixerInterface
{
    private string $headerComment = '';

    private bool $enabled = true;

    private string $location = 'after_declare_strict';

    private string $separate = 'both';

    private string $commentType = 'comment';

    private array $packagesPath = [];

    private string $headerTemplate = '';

    private array $composerCache = [];

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Add, replace or remove header comment for mono repo projects.',
            [
                // You can add code samples here if needed
            ],
        );
    }

    public function getName(): string
    {
        return 'FULLHAUS/header_comment';
    }

    public function getPriority(): int
    {
        return 20;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $this->enabled && $tokens->isTokenKindFound(T_OPEN_TAG);
    }

    protected function applyFix(\SplFileInfo $file, Tokens $tokens): void
    {
        if (!$this->enabled) {
            $this->removeHeader($tokens);

            return;
        }

        // Resolve the header for this specific file
        $resolvedHeader = $this->resolveHeaderForFile($file);

        if (empty($resolvedHeader)) {
            $this->removeHeader($tokens);

            return;
        }

        $this->insertHeader($tokens, $resolvedHeader);
    }

    public function configure(array $configuration): void
    {
        $this->enabled = $configuration['enabled'] ?? true;
        $this->headerComment = $configuration['header'] ?? '';
        $this->location = $configuration['location'] ?? 'after_declare_strict';
        $this->separate = $configuration['separate'] ?? 'both';
        $this->commentType = $configuration['comment_type'] ?? 'comment';
        $this->packagesPath = $configuration['packages_path'] ?? [];
        $this->headerTemplate = $configuration['header_template'] ?? '';
        $this->composerCache = [];
    }

    public function getConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        return new FixerConfigurationResolver(
            [
                (new FixerOptionBuilder('enabled', 'Whether the fixer is enabled.'))
                    ->setAllowedTypes([ 'bool' ])
                    ->setDefault(true)
                    ->getOption(),
                (new FixerOptionBuilder('header', 'The header comment text.'))
                    ->setAllowedTypes([ 'string' ])
                    ->setDefault('')
                    ->getOption(),
                (new FixerOptionBuilder('location', 'The location of the header comment.'))
                    ->setAllowedValues([ 'after_open', 'after_declare_strict' ])
                    ->setDefault('after_declare_strict')
                    ->getOption(),
                (new FixerOptionBuilder('separate', 'Whether to separate the header with newlines.'))
                    ->setAllowedValues([ 'both', 'top', 'bottom', 'none' ])
                    ->setDefault('both')
                    ->getOption(),
                (new FixerOptionBuilder('comment_type', 'The comment type to use for the header.'))
                    ->setAllowedValues([ 'comment', 'PHPDoc' ])
                    ->setDefault('comment')
                    ->getOption(),
                (new FixerOptionBuilder('packages_path', 'Array of paths to package directories for monorepo support.'))
                    ->setAllowedTypes([ 'array' ])
                    ->setDefault([])
                    ->getOption(),
                (new FixerOptionBuilder('header_template', 'Template string for package headers. Use {package_name} placeholder.'))
                    ->setAllowedTypes([ 'string' ])
                    ->setDefault('')
                    ->getOption(),
            ]
        );
    }

    private function insertHeader(Tokens $tokens, string $headerText): void
    {
        // Remove existing header first
        $this->removeHeader($tokens);

        $headerComment = $this->buildHeaderComment($headerText);

        // Find insertion point
        $insertionIndex = $this->findInsertionPoint($tokens);

        if ($insertionIndex === null) {
            return;
        }

        // Add the header comment with correct token type
        $commentTokenType = $this->commentType === 'PHPDoc' ? T_DOC_COMMENT : T_COMMENT;
        $tokens->insertAt($insertionIndex, new Token([ $commentTokenType, $headerComment ]));

        // Fix whitespace around the header to ensure proper spacing
        $this->fixWhiteSpaceAroundHeader($tokens, $insertionIndex);
    }

    private function removeHeader(Tokens $tokens): void
    {
        $headerStart = $this->findExistingHeaderStart($tokens);

        if ($headerStart === null) {
            return;
        }

        $token = $tokens[$headerStart];

        if ($token->isGivenKind(T_COMMENT) || $token->isGivenKind(T_DOC_COMMENT)) {
            // Remove whitespace before the header comment if present
            $prevIndex = $headerStart - 1;
            if ($prevIndex >= 0 && $tokens[$prevIndex]->isWhitespace()) {
                $tokens->clearAt($prevIndex);
            }

            // Remove the comment token
            $tokens->clearAt($headerStart);

            // Remove whitespace after the header comment
            $nextIndex = $headerStart + 1;
            while ($nextIndex < count($tokens) && $tokens[$nextIndex]->isWhitespace()) {
                $tokens->clearAt($nextIndex);
                $nextIndex++;
            }
        }
    }

    private function findInsertionPoint(Tokens $tokens): ?int
    {
        // Start from -1 since getNextTokenOfKind searches from index+1
        $openTagIndex = 0;
        if (!$tokens[0]->isGivenKind(T_OPEN_TAG)) {
            return null;
        }

        if ($this->location === 'after_open') {
            // Insert right after the opening tag
            $nextIndex = $openTagIndex + 1;
            // Skip any existing whitespace
            while ($nextIndex < count($tokens) && $tokens[$nextIndex]->isWhitespace()) {
                $nextIndex++;
            }

            return $nextIndex;
        }

        // Find declare(strict_types=1);
        $declareIndex = $tokens->getNextTokenOfKind($openTagIndex, [ [ T_DECLARE ] ]);

        if ($declareIndex !== null) {
            // Find the semicolon after declare
            $semicolonIndex = $tokens->getNextTokenOfKind($declareIndex, [ ';' ]);

            if ($semicolonIndex !== null) {
                // Skip whitespace after semicolon to find insertion point
                $nextIndex = $semicolonIndex + 1;
                while ($nextIndex < count($tokens) && $tokens[$nextIndex]->isWhitespace()) {
                    $nextIndex++;
                }

                return $nextIndex;
            }
        }

        // Fallback to after open tag if no declare found
        $nextIndex = $openTagIndex + 1;
        while ($nextIndex < count($tokens) && $tokens[$nextIndex]->isWhitespace()) {
            $nextIndex++;
        }

        return $nextIndex;
    }

    private function findExistingHeaderStart(Tokens $tokens): ?int
    {
        $openTagIndex = 0;
        if (!$tokens[0]->isGivenKind(T_OPEN_TAG)) {
            return null;
        }

        // Look for comments after the open tag and potentially after declare
        $searchStart = $openTagIndex + 1;

        // Skip past declare if present
        $declareIndex = $tokens->getNextTokenOfKind($openTagIndex, [ [ T_DECLARE ] ]);
        if ($declareIndex !== null) {
            $semicolonIndex = $tokens->getNextTokenOfKind($declareIndex, [ ';' ]);
            if ($semicolonIndex !== null) {
                $searchStart = $semicolonIndex + 1;
            }
        }

        // Look for the first comment after our search start point
        for ($i = $searchStart; $i < count($tokens); $i++) {
            $token = $tokens[$i];

            if ($token->isWhitespace()) {
                continue;
            }

            if ($token->isGivenKind([ T_COMMENT, T_DOC_COMMENT ])) {
                // Check if this looks like a header comment (multi-line block comment)
                $content = $token->getContent();
                if (str_starts_with($content, '/*') && !str_starts_with($content, '/**')) {
                    return $i;
                }
            }

            // Stop at the first non-whitespace, non-comment token
            if (!$token->isGivenKind([ T_COMMENT, T_DOC_COMMENT ])) {
                break;
            }
        }

        return null;
    }

    private function buildHeaderComment(string $headerText): string
    {
        $lines = explode("\n", trim($headerText));

        if (count($lines) === 0 || (count($lines) === 1 && empty($lines[0]))) {
            return '';
        }

        // Use /** */ for PHPDoc style, /* */ for regular comment
        $commentStart = $this->commentType === 'PHPDoc' ? "/**\n" : "/*\n";

        $comment = $commentStart;
        foreach ($lines as $line) {
            $comment .= ' * ' . $line . "\n";
        }
        $comment .= ' */';

        return $comment;
    }

    /**
     * Resolve the header for a specific file.
     * If packages_path and header_template are configured, finds the package
     * and generates a header based on the composer.json name.
     */
    private function resolveHeaderForFile(\SplFileInfo $file): string
    {
        // If no packages path configured, use default header
        if (empty($this->packagesPath) || empty($this->headerTemplate)) {
            return $this->headerComment;
        }

        $filePath = $file->getRealPath();
        if ($filePath === false) {
            return $this->headerComment;
        }

        // Find which package this file belongs to
        $packageName = $this->findPackageNameForFile($filePath);

        if ($packageName === null) {
            return $this->headerComment;
        }

        $this->headerTemplate = str_replace('{package_name}', $packageName, $this->headerTemplate);
        $this->headerTemplate = str_replace('{year}', (new \DateTime())->format('Y'), $this->headerTemplate);

        // Replace placeholder in template
        return $this->headerTemplate;
    }

    /**
     * Find the package name from composer.json for a given file path.
     */
    private function findPackageNameForFile(string $filePath): ?string
    {
        foreach ($this->packagesPath as $packagesRoot) {
            // Normalize paths
            $packagesRoot = rtrim($packagesRoot, '/');

            // Check if file is within this packages root
            if (!str_starts_with($filePath, $packagesRoot)) {
                continue;
            }

            // Find the package directory (first directory level under packages root)
            $relativePath = substr($filePath, strlen($packagesRoot) + 1);
            $pathParts = explode('/', $relativePath);

            if (empty($pathParts[0])) {
                continue;
            }

            $packageDir = $packagesRoot . '/' . $pathParts[0];

            // Get package name from composer.json
            $packageName = $this->getPackageNameFromComposer($packageDir);

            if ($packageName !== null) {
                return $packageName;
            }
        }

        return null;
    }

    /**
     * Read the package name from composer.json in the given directory.
     * Results are cached to avoid repeated file reads.
     */
    private function getPackageNameFromComposer(string $packageDir): ?string
    {
        // Check cache first
        if (isset($this->composerCache[$packageDir])) {
            return $this->composerCache[$packageDir];
        }

        $composerJsonPath = $packageDir . '/composer.json';

        if (!file_exists($composerJsonPath)) {
            $this->composerCache[$packageDir] = null;

            return null;
        }

        $composerJson = file_get_contents($composerJsonPath);
        if ($composerJson === false) {
            $this->composerCache[$packageDir] = null;

            return null;
        }

        $composerData = json_decode($composerJson, true);
        if (!is_array($composerData) || !isset($composerData['name'])) {
            $this->composerCache[$packageDir] = null;

            return null;
        }

        $packageName = $composerData['name'];
        $this->composerCache[$packageDir] = $packageName;

        return $packageName;
    }

    /**
     * Count line breaks in a specific direction from the given index.
     */
    private function getLineBreakCount(Tokens $tokens, int $index, int $direction): int
    {
        $whitespace = '';
        $adjacentIndex = $index + $direction;

        while ($adjacentIndex >= 0 && $adjacentIndex < count($tokens)) {
            $token = $tokens[$adjacentIndex];

            if ($token->isWhitespace()) {
                $whitespace .= $token->getContent();
                $adjacentIndex += $direction;
            } else {
                break;
            }
        }

        return substr_count($whitespace, "\n");
    }

    /**
     * Fix whitespace around the header comment to ensure proper spacing.
     */
    private function fixWhiteSpaceAroundHeader(Tokens $tokens, int $headerIndex): void
    {
        $lineEnding = "\n";

        // Fix lines after header comment
        if (
            ($this->separate === 'both' || $this->separate === 'bottom')
            && null !== $tokens->getNextMeaningfulToken($headerIndex)
        ) {
            $expectedLineCount = 2;
        } else {
            $expectedLineCount = 1;
        }

        if ($headerIndex === count($tokens) - 1) {
            $tokens->insertAt($headerIndex + 1, new Token([ T_WHITESPACE, str_repeat($lineEnding, $expectedLineCount) ]));
        } else {
            $lineBreakCount = $this->getLineBreakCount($tokens, $headerIndex, 1);

            if ($lineBreakCount < $expectedLineCount) {
                $missing = str_repeat($lineEnding, $expectedLineCount - $lineBreakCount);

                if ($tokens[$headerIndex + 1]->isWhitespace()) {
                    $tokens[$headerIndex + 1] = new Token([ T_WHITESPACE, $missing . $tokens[$headerIndex + 1]->getContent() ]);
                } else {
                    $tokens->insertAt($headerIndex + 1, new Token([ T_WHITESPACE, $missing ]));
                }
            } elseif ($lineBreakCount > $expectedLineCount && $tokens[$headerIndex + 1]->isWhitespace()) {
                $newLinesToRemove = $lineBreakCount - $expectedLineCount;
                $content = $tokens[$headerIndex + 1]->getContent();
                // Remove extra newlines from the beginning
                $tokens[$headerIndex + 1] = new Token(
                    [
                        T_WHITESPACE,
                        preg_replace("/^(?:\\r\\n|\\n|\\r){{$newLinesToRemove}}/", '', $content),
                    ]
                );
            }
        }

        // Fix lines before header comment
        $expectedLineCount = ($this->separate === 'both' || $this->separate === 'top') ? 2 : 1;
        $prev = $tokens->getPrevNonWhitespace($headerIndex);

        if ($prev !== null) {
            $regex = '/\h$/';

            if ($tokens[$prev]->isGivenKind(T_OPEN_TAG) && preg_match($regex, $tokens[$prev]->getContent())) {
                $tokens[$prev] = new Token([ T_OPEN_TAG, preg_replace($regex, $lineEnding, $tokens[$prev]->getContent()) ]);
            }
        }

        $lineBreakCount = $this->getLineBreakCount($tokens, $headerIndex, -1);

        if ($lineBreakCount < $expectedLineCount) {
            // Insert missing line breaks before the header
            $tokens->insertAt($headerIndex, new Token([ T_WHITESPACE, str_repeat($lineEnding, $expectedLineCount - $lineBreakCount) ]));
        }
    }
}

