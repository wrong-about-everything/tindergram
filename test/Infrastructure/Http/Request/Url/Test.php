<?php

declare(strict_types=1);

namespace TG\Tests\Infrastructure\Http\Request\Url;

use TG\Infrastructure\Http\Request\Url;

class Test extends Url
{
    public function value(): string
    {
        return 'http://example.org';
    }
}