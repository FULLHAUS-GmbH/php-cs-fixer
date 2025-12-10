<?php

declare(strict_types=1);

/*
 * This file is part of the FULLHAUS PHP-CS-Fixer configuration.
 *
 * (c) 2024-2025 FULLHAUS GmbH
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
        // Run after declare_strict_types fixer
        return -10;
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
        $this->packagesPath = $configuration['packages_path'] ?? [];
        $this->headerTemplate = $configuration['header_template'] ?? '';
        $this->composerCache = [];
    }

    public function getConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('enabled', 'Whether the fixer is enabled.'))
                ->setAllowedTypes(['bool'])
                ->setDefault(true)
                ->getOption(),
            (new FixerOptionBuilder('header', 'The header comment text.'))
                ->setAllowedTypes(['string'])
                ->setDefault('')
                ->getOption(),
            (new FixerOptionBuilder('location', 'The location of the header comment.'))
                ->setAllowedValues(['after_open', 'after_declare_strict'])
                ->setDefault('after_declare_strict')
                ->getOption(),
            (new FixerOptionBuilder('separate', 'Whether to separate the header with newlines.'))
                ->setAllowedValues(['both', 'top', 'bottom', 'none'])
                ->setDefault('both')
                ->getOption(),
            (new FixerOptionBuilder('comment_type', 'The comment type to use for the header.'))
                ->setAllowedValues(['comment', 'PHPDoc'])
                ->setDefault('comment')
                ->getOption(),
            (new FixerOptionBuilder('packages_path', 'Array of paths to package directories for monorepo support.'))
                ->setAllowedTypes(['array'])
                ->setDefault([])
                ->getOption(),
            (new FixerOptionBuilder('header_template', 'Template string for package headers. Use {package_name} placeholder.'))
                ->setAllowedTypes(['string'])
                ->setDefault('')
                ->getOption(),
        ]);
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

        // Prepare tokens to insert
        $tokensToInsert = [];

        // Add blank line before if needed
        if ($this->separate === 'both' || $this->separate === 'top') {
            $tokensToInsert[] = new Token([T_WHITESPACE, "\n"]);
        }

        // Add the header comment
        $tokensToInsert[] = new Token([T_COMMENT, $headerComment]);

        // Add blank line after if needed
        if ($this->separate === 'both' || $this->separate === 'bottom') {
            $tokensToInsert[] = new Token([T_WHITESPACE, "\n\n"]);
        } else {
            $tokensToInsert[] = new Token([T_WHITESPACE, "\n"]);
        }

        // Insert the tokens
        $tokens->insertAt($insertionIndex, $tokensToInsert);
    }

    private function removeHeader(Tokens $tokens): void
    {
        $headerStart = $this->findExistingHeaderStart($tokens);

        if ($headerStart === null) {
            return;
        }

        // Find the end of the header comment
        $headerEnd = $headerStart;
        $token = $tokens[$headerStart];

        if ($token->isGivenKind(T_COMMENT) || $token->isGivenKind(T_DOC_COMMENT)) {
            // Remove the comment token
            $tokens->clearAt($headerStart);

            // Also remove trailing whitespace
            $nextIndex = $tokens->getNextNonWhitespace($headerStart);
            if ($nextIndex !== null && $tokens[$nextIndex]->isWhitespace()) {
                $tokens->clearAt($nextIndex);
            }
        }
    }

    private function findInsertionPoint(Tokens $tokens): ?int
    {
        $openTagIndex = $tokens->getNextTokenOfKind(0, [[T_OPEN_TAG]]);

        if ($openTagIndex === null) {
            return null;
        }

        if ($this->location === 'after_open') {
            return $openTagIndex + 1;
        }

        // Find declare(strict_types=1);
        $declareIndex = $tokens->getNextTokenOfKind($openTagIndex, [[T_DECLARE]]);

        if ($declareIndex !== null) {
            // Find the semicolon after declare
            $semicolonIndex = $tokens->getNextTokenOfKind($declareIndex, [';']);

            if ($semicolonIndex !== null) {
                return $tokens->getNextMeaningfulToken($semicolonIndex);
            }
        }

        // Fallback to after open tag
        return $tokens->getNextMeaningfulToken($openTagIndex);
    }

    private function findExistingHeaderStart(Tokens $tokens): ?int
    {
        $openTagIndex = $tokens->getNextTokenOfKind(0, [[T_OPEN_TAG]]);

        if ($openTagIndex === null) {
            return null;
        }

        // Look for comments after the open tag and potentially after declare
        $searchStart = $openTagIndex + 1;

        // Skip past declare if present
        $declareIndex = $tokens->getNextTokenOfKind($openTagIndex, [[T_DECLARE]]);
        if ($declareIndex !== null) {
            $semicolonIndex = $tokens->getNextTokenOfKind($declareIndex, [';']);
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

            if ($token->isGivenKind([T_COMMENT, T_DOC_COMMENT])) {
                // Check if this looks like a header comment (multi-line block comment)
                $content = $token->getContent();
                if (str_starts_with($content, '/*') && !str_starts_with($content, '/**')) {
                    return $i;
                }
            }

            // Stop at the first non-whitespace, non-comment token
            if (!$token->isGivenKind([T_COMMENT, T_DOC_COMMENT])) {
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

        $comment = "/*\n";
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

        // Replace placeholder in template
        return str_replace('{package_name}', $packageName, $this->headerTemplate);
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
}

