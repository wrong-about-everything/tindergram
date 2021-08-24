<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Infrastructure\Filesystem\FilePath;

use PHPUnit\Framework\TestCase;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Infrastructure\Filesystem\DirPath\Created;
use TG\Infrastructure\Filesystem\DirPath\FromNestedDirectoryNames;
use TG\Infrastructure\Filesystem\FileContents\Emptie;
use TG\Infrastructure\Filesystem\Filename\PortableFromString;
use TG\Infrastructure\Filesystem\FilePath\Created as CreatedFile;
use TG\Infrastructure\Filesystem\FilePath\ExistentFromDirAndFileName;
use TG\Infrastructure\Filesystem\FilePath\FromDirAndFileName;
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Filesystem\DirPath\NonExistent;
use TG\Tests\Infrastructure\Filesystem\DirPath\Tmp;

class ExistentFromDirAndFileNameTest extends TestCase
{
    public function testDirDoesNotExist()
    {
        $filePath = new ExistentFromDirAndFileName(new NonExistent(), new PortableFromString('vasya'));

        $this->assertFalse($filePath->exists());
        $this->assertFalse($filePath->value()->isSuccessful());
    }

    public function testDirExistsButFilenameInItDoesNot()
    {
        $filePath = new ExistentFromDirAndFileName(new Tmp(), new PortableFromString('vasya'));

        $this->assertFalse($filePath->exists());
        $this->assertFalse($filePath->value()->isSuccessful());
    }

    public function testPathExistsButItIsADirectory()
    {
        (new Created(new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya'))))->value();

        $filePath = new ExistentFromDirAndFileName(new Tmp(), new PortableFromString('vasya'));

        $this->assertFalse($filePath->exists());
        $this->assertFalse($filePath->value()->isSuccessful());

    }

    public function testWhenFileExistsThenEverythingIsOK()
    {
        (new CreatedFile(
            new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
            new Emptie()
        ))
            ->value();

        $file = new ExistentFromDirAndFileName(new Tmp(), new PortableFromString('vasya'));

        $this->assertTrue($file->exists());
        $this->assertEquals(
            (new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')))
                ->value()->pure()->raw(),
            $file->value()->pure()->raw()
        );
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }
}