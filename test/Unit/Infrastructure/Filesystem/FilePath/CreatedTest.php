<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Infrastructure\Filesystem\FilePath;

use PHPUnit\Framework\TestCase;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Infrastructure\Filesystem\FileContents\FromString;
use TG\Infrastructure\Filesystem\Filename\PortableFromString;
use TG\Infrastructure\Filesystem\FilePath\Created;
use TG\Infrastructure\Filesystem\FilePath\FromDirAndFileName;
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Filesystem\DirPath\Tmp;

class CreatedTest extends TestCase
{
    public function testWhenFileDoesNotExistThenItIsCreated()
    {
        $filePath =
            new Created(
                new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
                new FromString('vasya')
            );

        $this->assertTrue($filePath->value()->isSuccessful());
        $this->assertTrue($filePath->exists());
    }

    public function testWhenFileExistsThenItIsNotCreated()
    {
        (new Created(
            new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
            new FromString('vasya')
        ))
            ->value();

        $filePath =
            new Created(
                new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
                new FromString('vasya')
            );

        $this->assertFalse($filePath->value()->isSuccessful());
        $this->assertTrue($filePath->exists());
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }
}