<?php

declare(strict_types=1);

namespace TG\Infrastructure\Http\Response\Outbound;

use TG\Infrastructure\Http\Response\Code;

interface Response
{
    public function code(): Code;

    public function headers(): array/*Header[]*/;

    public function body(): string;
}
