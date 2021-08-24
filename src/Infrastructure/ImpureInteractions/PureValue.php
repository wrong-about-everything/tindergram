<?php

declare(strict_types=1);

namespace TG\Infrastructure\ImpureInteractions;

interface PureValue
{
    public function isPresent(): bool;

    public function raw();
}