<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Infrastructure\Filesystem\DirPath;

use PHPUnit\Framework\TestCase;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Infrastructure\Filesystem\DirPath\Created;
use TG\Infrastructure\Filesystem\DirPath\Removed;
use TG\Infrastructure\Filesystem\DirPath\FromNestedDirectoryNames;
use TG\Infrastructure\Filesystem\Filename\PortableFromString;
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Filesystem\DirPath\Tmp;

class RemovedTest extends TestCase
{
    public function testWhenDirectoryDoesNotExistThenItIsNotRemoved()
    {
        $dir = new Removed(new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya')));

        $this->assertFalse($dir->exists());
        $this->assertFalse(
            $dir->value()->isSuccessful()
        );
        $this->assertFalse(
            (new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya')))->exists()
        );
    }

    public function testWhenDirectoryExistsThenItIsRemoved()
    {
        (new Created(
            new FromNestedDirectoryNames(
                new Created(
                    new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya'))
                ),
                new PortableFromString('fedya')
            )
        ))
            ->value();
        $dir = new Removed(new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya')));

        $this->assertFalse($dir->exists());
        $this->assertTrue($dir->value()->isSuccessful());
        $this->assertFalse(
            (new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya')))->exists()
        );
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }
}