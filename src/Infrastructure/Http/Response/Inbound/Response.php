<?php

declare(strict_types=1);

namespace TG\Infrastructure\Http\Response\Inbound;

use TG\Infrastructure\Http\Response\Code;
use TG\Infrastructure\Http\Response\Header;

interface Response
{
    public function code(): Code;

    /**
     * @return Header[]
     */
    public function headers(): array;

    public function body(): string;

    public function isAvailable(): bool;
}
