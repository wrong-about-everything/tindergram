<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Infrastructure\Filesystem\FileContents;

use PHPUnit\Framework\TestCase;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Infrastructure\Filesystem\FileContents\FromFilePath;
use TG\Infrastructure\Filesystem\FileContents\Emptie;
use TG\Infrastructure\Filesystem\FileContents\FromString;
use TG\Infrastructure\Filesystem\Filename\PortableFromString;
use TG\Infrastructure\Filesystem\FilePath\Created;
use TG\Infrastructure\Filesystem\FilePath\FromDirAndFileName;
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Filesystem\DirPath\Tmp;

class FromFileTest extends TestCase
{
    public function testWhenFilePathDoesNotExistThenContentIsNotSuccessful()
    {
        $content =
            new FromFilePath(
                new FromDirAndFileName(new Tmp(), new PortableFromString('vasya'))
            );

        $this->assertFalse($content->value()->isSuccessful());
    }

    public function testWhenFilePathExistsThenContentIsRetrieved()
    {
        (new Created(
            new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
            new FromString('hello!')
        ))
            ->value();

        $content =
            new FromFilePath(
                new FromDirAndFileName(new Tmp(), new PortableFromString('vasya'))
            );

        $this->assertTrue($content->value()->isSuccessful());
        $this->assertEquals(
            'hello!',
            (new FromFilePath(
                new FromDirAndFileName(new Tmp(), new PortableFromString('vasya'))
            ))
                ->value()->pure()->raw()
        );
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();;
    }
}