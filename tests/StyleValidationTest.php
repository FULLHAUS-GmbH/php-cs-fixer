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

namespace FULLHAUS\CodingStandards\Tests;

use FULLHAUS\CodingStandards\CsFixerConfig;
use FULLHAUS\CodingStandards\Fixer\ArraySpacingFixer;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerFactory;
use PhpCsFixer\RuleSet\RuleSet;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\TestCase;

class StyleValidationTest extends TestCase
{
    private CsFixerConfig $config;
    private FixerFactory $fixerFactory;
    private array $fixers;

    protected function setUp(): void
    {
        $this->config = CsFixerConfig::create();
        $this->fixerFactory = new FixerFactory();
        $this->fixerFactory->registerBuiltInFixers();
        $this->fixerFactory->registerCustomFixers([new ArraySpacingFixer()]);

        // Verwende die Rules aus der tatsächlichen Config
        $ruleSet = new RuleSet($this->config->getRules());
        $this->fixers = $this->fixerFactory->useRuleSet($ruleSet)->getFixers();
    }

    /**
     * Test that the FULLHAUS config converts array syntax to short syntax
     */
    public function testArraySyntaxShort(): void
    {
        $input = '<?php

$array = array(1, 2, 3);
';
        $expected = '<?php

$array = [ 1, 2, 3 ];
';

        $result = $this->applyFullhausConfig($input);
        self::assertSame($expected, $result);
    }

    /**
     * Test that concat has one space
     */
    public function testConcatSpacing(): void
    {
        $input = '<?php

$str = "hello"."world";
';
        $expected = '<?php

$str = \'hello\' . \'world\';
';

        $result = $this->applyFullhausConfig($input);
        self::assertSame($expected, $result);
    }

    /**
     * Test that cast has no spaces
     */
    public function testCastSpaces(): void
    {
        $input = '<?php

$int = (int) $value;
';
        $expected = '<?php

$int = (int)$value;
';

        $result = $this->applyFullhausConfig($input);
        self::assertSame($expected, $result);
    }

    /**
     * Test single quotes are enforced
     */
    public function testSingleQuote(): void
    {
        $input = '<?php

$str = "hello";
';
        $expected = '<?php

$str = \'hello\';
';

        $result = $this->applyFullhausConfig($input);
        self::assertSame($expected, $result);
    }

    /**
     * Test trailing comma in multiline arrays
     */
    public function testTrailingCommaInMultilineArray(): void
    {
        $input = '<?php

$array = [
    1,
    2,
    3
];
';
        $expected = '<?php

$array = [
    1,
    2,
    3,
];
';

        $result = $this->applyFullhausConfig($input);
        self::assertSame($expected, $result);
    }

    /**
     * Test yoda style is disabled for equal
     */
    public function testYodaStyleDisabled(): void
    {
        $input = '<?php

if (5 == $value) {
    return true;
}
';
        $expected = '<?php

if ($value == 5) {
    return true;
}
';

        $result = $this->applyFullhausConfig($input);
        self::assertSame($expected, $result);
    }

    /**
     * Test nullable type with union syntax
     */
    public function testNullableTypeUnionSyntax(): void
    {
        $input = '<?php

function test(?string $value): ?int
{
    return null;
}
';
        $expected = '<?php

function test(string|null $value): int|null
{
    return null;
}
';

        $result = $this->applyFullhausConfig($input);
        self::assertSame($expected, $result);
    }

    /**
     * Test nullable type with union syntax
     */
    public function testNullableOrder(): void
    {
        $input = '<?php

function test(null|string $value): null|int
{
    return null;
}
';
        $expected = '<?php

function test(string|null $value): int|null
{
    return null;
}
';

        $result = $this->applyFullhausConfig($input);
        self::assertSame($expected, $result);
    }

    /**
     * Test list syntax uses short form
     */
    public function testListSyntaxShort(): void
    {
        $input = '<?php

list($a, $b) = [1, 2];
';
        $expected = '<?php

[$a, $b] = [ 1, 2 ];
';

        $result = $this->applyFullhausConfig($input);
        self::assertSame($expected, $result);
    }

    /**
     * Test blank line before return statement
     */
    public function testBlankLineBeforeReturn(): void
    {
        $input = '<?php

function test()
{
    $a = 1;
    return $a;
}
';
        $expected = '<?php

function test()
{
    $a = 1;

    return $a;
}
';

        $result = $this->applyFullhausConfig($input);
        self::assertSame($expected, $result);
    }

    /**
     * Test no unused imports
     */
    public function testNoUnusedImports(): void
    {
        $input = '<?php

use Some\Unused\Class;
use Some\Used\Class as UsedClass;

$obj = new UsedClass();
';
        $expected = '<?php

use Some\Used\Class as UsedClass;

$obj = new UsedClass();
';

        $result = $this->applyFullhausConfig($input);
        self::assertSame($expected, $result);
    }

    /**
     * Test ternary to null coalescing
     */
    public function testTernaryToNullCoalescing(): void
    {
        $input = '<?php

$value = isset($foo) ? $foo : "default";
';
        $expected = '<?php

$value = $foo ?? \'default\';
';

        $result = $this->applyFullhausConfig($input);
        self::assertSame($expected, $result);
    }

    /**
     * Test declare equals normalize without spaces
     */
    public function testDeclareEqualNormalize(): void
    {
        $input = '<?php

declare(strict_types = 1);
';
        $expected = '<?php

declare(strict_types=1);
';

        $result = $this->applyFullhausConfig($input);
        self::assertSame($expected, $result);
    }

    /**
     * Test array spacing adds spaces inside brackets
     */
    public function testArraySpacing(): void
    {
        $input = '<?php

$array = [1, 2, 3];
$another = [\'a\', \'b\'];
$empty = [];
';
        $expected = '<?php

$array = [ 1, 2, 3 ];
$another = [ \'a\', \'b\' ];
$empty = [];
';

        $result = $this->applyFullhausConfig($input);
        self::assertSame($expected, $result);
    }

    /**
     * Test array spacing with array() syntax
     */
    public function testArraySpacingWithArraySyntax(): void
    {
        $input = '<?php

$array = array(1, 2, 3);
';
        $expected = '<?php

$array = [ 1, 2, 3 ];
';

        $result = $this->applyFullhausConfig($input);
        self::assertSame($expected, $result);
    }

    /**
     * Test array spacing does not affect multiline arrays
     */
    public function testArraySpacingMultiline(): void
    {
        $input = '<?php

$array = [
    1,
    2,
    3,
];
';
        $expected = '<?php

$array = [
    1,
    2,
    3,
];
';

        $result = $this->applyFullhausConfig($input);
        self::assertSame($expected, $result);
    }

    /**
     * Helper method to apply fixers to code
     */
    private function fixCode(string $code, array $rules): string
    {
        $ruleSet = new RuleSet($rules);
        $fixers = $this->fixerFactory->useRuleSet($ruleSet)->getFixers();

        $tokens = Tokens::fromCode($code);

        /** @var FixerInterface $fixer */
        foreach ($fixers as $fixer) {
            if ($fixer->isCandidate($tokens)) {
                $fixer->fix($this->createMockSplFileInfo(), $tokens);
            }
        }

        return $tokens->generateCode();
    }

    /**
     * Create a mock SplFileInfo for the fixer
     */
    private function createMockSplFileInfo(): \SplFileInfo
    {
        return new \SplFileInfo(__FILE__);
    }

    private function applyFullhausConfig(string $input)
    {
        return $this->fixCode($input, $this->config->getRules());
    }
}
