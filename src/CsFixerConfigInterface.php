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

namespace FULLHAUS\CodingStandards;

interface CsFixerConfigInterface
{
    public static function create(): static;
}
