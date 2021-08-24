<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Infrastructure\Filesystem\DirPath;

use PHPUnit\Framework\TestCase;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Infrastructure\Filesystem\DirPath\FromNestedDirectoryNames;
use TG\Infrastructure\Filesystem\DirPath\Created;
use TG\Infrastructure\Filesystem\Filename\PortableFromString;
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Filesystem\DirPath\Tmp;

class FromNestedDirectoryNamesTest extends TestCase
{
    public function testExistentDirectory()
    {
        (new Created(
            new FromNestedDirectoryNames(
                new Tmp(),
                new PortableFromString('kvass')
            )
        ))
            ->value();

        $dir =
            new FromNestedDirectoryNames(
                new Tmp(),
                new PortableFromString('kvass')
            );

        $this->assertEquals(
            (new FromNestedDirectoryNames(
                new Tmp(),
                new PortableFromString('kvass')
            ))
                ->value()->pure()->raw(),
            $dir->value()->pure()->raw()
        );
    }

    public function testNonExistentDirectory()
    {
        $dir =
            new FromNestedDirectoryNames(
                new Tmp(),
                new PortableFromString('kvass')
            );

        $this->assertEquals('/tmp/project_custom_temp_dir/kvass', $dir->value()->pure()->raw());
        $this->assertFalse($dir->exists());
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }
}