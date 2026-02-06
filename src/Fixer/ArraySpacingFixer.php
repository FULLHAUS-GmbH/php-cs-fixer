<?php

declare(strict_types=1);

/*
 * This file is part of the FULLHAUS PHP-CS-Fixer configuration.
 *
 * (c) 2026 FULLHAUS GmbH
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace FULLHAUS\CodingStandards\Fixer;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * Adds spaces inside array brackets (opposite of trim_array_spaces).
 * Transforms [1,2,3] to [ 1,2,3 ] and array(1,2,3) to array( 1,2,3 ).
 */
final class ArraySpacingFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Arrays should have spaces after opening bracket and before closing bracket.',
            [
                new CodeSample("<?php\n\$sample = [1, 2, 3];\n"),
                new CodeSample("<?php\n\$sample = array(1, 2, 3);\n"),
            ],
        );
    }

    public function getName(): string
    {
        return 'FULLHAUS/array_spacing';
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([ \T_ARRAY, CT::T_ARRAY_SQUARE_BRACE_OPEN ]);
    }

    public function getPriority(): int
    {
        // Should run after trim_array_spaces, array_syntax to override it
        return -30;
    }

    protected function applyFix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = $tokens->count() - 1; $index >= 0; --$index) {
            if ($tokens[$index]->isGivenKind([ \T_ARRAY, CT::T_ARRAY_SQUARE_BRACE_OPEN ])) {
                $this->fixArray($tokens, $index);
            }
        }
    }

    /**
     * Method to add leading/trailing whitespace within single line arrays.
     */
    private function fixArray(Tokens $tokens, int $index): void
    {
        $startIndex = $index;

        if ($tokens[$startIndex]->isGivenKind(\T_ARRAY)) {
            $startIndex = $tokens->getNextMeaningfulToken($startIndex);
            $endIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $startIndex);
        } else {
            $endIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE, $startIndex);
        }

        // Check if array is empty
        $nextMeaningfulIndex = $tokens->getNextMeaningfulToken($startIndex);

        if ($nextMeaningfulIndex === $endIndex) {
            // Empty array, don't add spaces
            return;
        }

        // Check if array is multiline
        for ($i = $startIndex; $i < $endIndex; ++$i) {
            if ($tokens[$i]->isGivenKind(\T_WHITESPACE) && str_contains($tokens[$i]->getContent(), "\n")) {
                // This is a multiline array, don't modify it
                return;
            }
        }

        // Fix space after opening bracket
        $nextIndex = $startIndex + 1;
        $nextToken = $tokens[$nextIndex];

        if ($nextToken->isWhitespace()) {
            // Replace with single space if it's not already a single space
            if ($nextToken->getContent() !== ' ') {
                $tokens[$nextIndex] = new Token([ \T_WHITESPACE, ' ' ]);
            }
        } else {
            // No whitespace, insert a space
            $tokens->insertAt($nextIndex, new Token([ \T_WHITESPACE, ' ' ]));
            $endIndex++; // Adjust end index since we inserted a token
        }

        // Fix space before closing bracket
        $prevIndex = $endIndex - 1;
        $prevToken = $tokens[$prevIndex];

        if ($prevToken->isWhitespace()) {
            // Replace with single space if it's not already a single space
            if ($prevToken->getContent() !== ' ') {
                $tokens[$prevIndex] = new Token([ \T_WHITESPACE, ' ' ]);
            }
        } else {
            // No whitespace, insert a space
            $tokens->insertAt($endIndex, new Token([ \T_WHITESPACE, ' ' ]));
        }
    }
}
