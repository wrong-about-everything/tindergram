<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Infrastructure\Filesystem\FilePath;

use PHPUnit\Framework\TestCase;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Infrastructure\Filesystem\FileContents\FromString;
use TG\Infrastructure\Filesystem\Filename\PortableFromString;
use TG\Infrastructure\Filesystem\FilePath\Created;
use TG\Infrastructure\Filesystem\FilePath\FromDirAndFileName;
use TG\Infrastructure\Filesystem\FilePath\Removed;
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Filesystem\DirPath\Tmp;

class RemovedTest extends TestCase
{
    public function testWhenFileDoesNotExistThenItIsNotRemoved()
    {
        $filePath =
            new Removed(
                new FromDirAndFileName(new Tmp(), new PortableFromString('vasya'))
            );

        $this->assertFalse($filePath->value()->isSuccessful());
        $this->assertFalse($filePath->exists());
    }

    public function testWhenFileExistsThenItIsRemoved()
    {
        (new Created(
            new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
            new FromString('vasya')
        ))
            ->value();

        $filePath =
            new Removed(
                new FromDirAndFileName(new Tmp(), new PortableFromString('vasya'))
            );

        $this->assertTrue($filePath->value()->isSuccessful());
        $this->assertFalse($filePath->exists());
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }
}