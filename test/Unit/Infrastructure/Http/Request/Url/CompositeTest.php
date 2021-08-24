<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Infrastructure\Http\Request\Url;

use PHPUnit\Framework\TestCase;
use TG\Infrastructure\Http\Request\Url\Composite;
use TG\Infrastructure\Http\Request\Url\Host\FromString as Domain;
use TG\Infrastructure\Http\Request\Url\Fragment\NonSpecified as NonSpecifiedFragment;
use TG\Infrastructure\Http\Request\Url\Path\FromString as Path;
use TG\Infrastructure\Http\Request\Url\Port\NonSpecified as NonSpecifiedPort;
use TG\Infrastructure\Http\Request\Url\Query\NonSpecified as NonSpecifiedQuery;
use TG\Infrastructure\Http\Request\Url\Scheme\NonSpecified as NonSpecifiedScheme;

class CompositeTest extends TestCase
{
    public function testSuccess()
    {
        $query =
            new Composite(
                new NonSpecifiedScheme(),
                new Domain('vasya/'),
                new NonSpecifiedPort(),
                new Path('belov'),
                new NonSpecifiedQuery(),
                new NonSpecifiedFragment()
            );

        $this->assertEquals('vasya/belov', $query->value());
    }
}
