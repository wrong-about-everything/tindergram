<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Infrastructure\Filesystem\FileContents;

use PHPUnit\Framework\TestCase;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Infrastructure\Filesystem\FileContents\AppendedConcurrentSafelyToExistingFile;
use TG\Infrastructure\Filesystem\FileContents\Emptie;
use TG\Infrastructure\Filesystem\FileContents\FromFilePath;
use TG\Infrastructure\Filesystem\Filename\PortableFromString;
use TG\Infrastructure\Filesystem\FilePath\Created;
use TG\Infrastructure\Filesystem\FilePath\FromDirAndFileName;
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Filesystem\DirPath\Tmp;

class AppendedConcurrentSafelyToExistingFileTest extends TestCase
{
    public function testWhenFilePathDoesNotExistThenNewContentIsNotAppended()
    {
        $content =
            new AppendedConcurrentSafelyToExistingFile(
                new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
                'hello!'
            );

        $this->assertFalse($content->value()->isSuccessful());
    }

    public function testWhenFilePathExistsThenNewContentIsAppended()
    {
        (new Created(
            new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
            new Emptie()
        ))
            ->value();

        $content =
            new AppendedConcurrentSafelyToExistingFile(
                new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
                'hello!'
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