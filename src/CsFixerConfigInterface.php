<?php

declare(strict_types=1);

namespace FULLHAUS\CodingStandards;

interface CsFixerConfigInterface
{
    public function __construct(string $name);
}