<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Infrastructure\Http\Request\Url\Path;

use PHPUnit\Framework\TestCase;
use TG\Infrastructure\Http\Request\Url\Path\FromString;

class FromStringTest extends TestCase
{
    public function testSuccess()
    {
        $this->assertEquals(
            'belov',
            (new FromString('belov'))->value()
        );
    }
}
