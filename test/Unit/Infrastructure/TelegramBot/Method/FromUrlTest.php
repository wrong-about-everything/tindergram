<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Infrastructure\TelegramBot\Method;

use PHPUnit\Framework\TestCase;
use TG\Infrastructure\Http\Request\Url\Composite;
use TG\Infrastructure\Http\Request\Url\Fragment\NonSpecified as NonSpecifiedFragment;
use TG\Infrastructure\Http\Request\Url\Host\Localhost;
use TG\Infrastructure\Http\Request\Url\Path\FromString;
use TG\Infrastructure\Http\Request\Url\Port\NonSpecified;
use TG\Infrastructure\Http\Request\Url\Query\FromArray;
use TG\Infrastructure\Http\Request\Url\Scheme\Https;
use TG\Infrastructure\TelegramBot\Method\FromUrl;

class FromUrlTest extends TestCase
{
    public function test()
    {
        $this->assertEquals(
            'cloud',
            (new FromUrl(
                new Composite(
                    new Https(),
                    new Localhost(),
                    new NonSpecified(),
                    new FromString('/hey/you/get/off/of/my/cloud'),
                    new FromArray(['dressed up' => 'like Union Jack']),
                    new NonSpecifiedFragment()
                )
            ))
                ->value()
        );
    }
}