<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Infrastructure\Filesystem\DirPath;

use Exception;
use PHPUnit\Framework\TestCase;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Infrastructure\Filesystem\DirPath\ExistentFromAbsolutePathString;
use TG\Infrastructure\Filesystem\DirPath\FromNestedDirectoryNames;
use TG\Infrastructure\Filesystem\DirPath\Created;
use TG\Infrastructure\Filesystem\Filename\PortableFromString;
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Filesystem\DirPath\Tmp;

class ExistentFromAbsolutePathStringTest extends TestCase
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
            new ExistentFromAbsolutePathString(
                (new FromNestedDirectoryNames(
                    new Tmp(),
                    new PortableFromString('kvass')
                ))
                    ->value()->pure()->raw()
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
        try {
            new ExistentFromAbsolutePathString('/tmp/kvass');
        } catch (Exception $e) {
            return $this->assertTrue(true);
        }

        $this->fail('An exception should have been thrown');
    }

    public function testInvalidPath()
    {
        try {
            new ExistentFromAbsolutePathString('s6d5s8dg#$%^&\\');
        } catch (Exception $e) {
            return $this->assertTrue(true);
        }

        $this->fail('An exception should have been thrown');
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }
}