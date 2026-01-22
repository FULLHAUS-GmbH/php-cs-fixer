<?php

declare(strict_types=1);

/*
 * This file is part of the FULLHAUS PHP-CS-Fixer configuration.
 *
 * (c) 2024-2026 FULLHAUS GmbH
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

$config = FULLHAUS\CodingStandards\CsFixerConfig::create();
$config->getFinder()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return $config;
