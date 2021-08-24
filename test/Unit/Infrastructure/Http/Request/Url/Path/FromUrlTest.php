<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Infrastructure\Http\Request\Url\Path;

use PHPUnit\Framework\TestCase;
use TG\Infrastructure\Http\Request\Url\FromString;
use TG\Infrastructure\Http\Request\Url\Path\FromUrl;

class FromUrlTest extends TestCase
{
    public function testWhenPathIsPresent()
    {
        $path =
            new FromUrl(
                new FromString('http://vasya.belov/fedya?tolya#vitya')
            );

        $this->assertEquals(
            '/fedya',
            $path->value()
        );
    }

    public function testWhenPathIsAbsent()
    {
        $this->assertEquals(
            '',
            (new FromUrl(
                new FromString('http://vasya.belov?tolya#vitya')
            ))
                ->value()
        );
    }
}
