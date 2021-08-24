<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Infrastructure\Filesystem\FilePath;

use PHPUnit\Framework\TestCase;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Infrastructure\Filesystem\FileContents\Emptie;
use TG\Infrastructure\Filesystem\FileContents\FromFilePath;
use TG\Infrastructure\Filesystem\FileContents\FromString;
use TG\Infrastructure\Filesystem\FilePath\Copied;
use TG\Infrastructure\Filesystem\FilePath\Created;
use TG\Infrastructure\Filesystem\FilePath\FromDirAndFileName;
use TG\Infrastructure\Filesystem\Filename\PortableFromString;
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Filesystem\DirPath\Tmp;

class CopiedTest extends TestCase
{
    public function testWhenOriginFileDoesNotExistThenItIsNotCopied()
    {
        $dir =
            new Copied(
                new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
                new FromDirAndFileName(new Tmp(), new PortableFromString('fedya'))
            );

        $this->assertFalse($dir->exists());
        $this->assertFalse($dir->value()->isSuccessful());
    }

    public function testWhenBothOriginAndDestinationFilesExistThenOriginIsNotCopied()
    {
        (new Created(
            new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
            new Emptie()
        ))
            ->value();
        (new Created(
            new FromDirAndFileName(new Tmp(), new PortableFromString('fedya')),
            new Emptie()
        ))
            ->value();

        $dir =
            new Copied(
                new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
                new FromDirAndFileName(new Tmp(), new PortableFromString('fedya'))
            );

        $this->assertTrue($dir->exists());
        $this->assertFalse($dir->value()->isSuccessful());
    }

    public function testWhenOriginFileExistsAndDestinationDoesNotThenOriginIsCopied()
    {
        (new Created(
            new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
            new FromString('Hey!')
        ))
            ->value();

        $dir =
            new Copied(
                new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
                new FromDirAndFileName(new Tmp(), new PortableFromString('fedya'))
            );

        $this->assertTrue($dir->exists());
        $this->assertTrue($dir->value()->isSuccessful());
        $this->assertEquals(
            'Hey!',
            (new FromFilePath(
                new FromDirAndFileName(new Tmp(), new PortableFromString('fedya'))
            ))
                ->value()->pure()->raw()
        );
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }
}