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
use TG\Infrastructure\Filesystem\FilePath\FromDirAndFileName;
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Filesystem\DirPath\NonExistent;
use TG\Tests\Infrastructure\Filesystem\DirPath\Tmp;

class FromDirAndFileNameTest extends TestCase
{
    public function testDirDoesNotExist()
    {
        $filePath = new FromDirAndFileName(new NonExistent(), new PortableFromString('vasya'));

        $this->assertFalse($filePath->exists());
        $this->assertTrue($filePath->value()->isSuccessful());
        $this->assertEquals(
            sprintf('%s/vasya', (new NonExistent())->value()->pure()->raw()),
            $filePath->value()->pure()->raw()
        );
    }

    public function testDirExistsButFilenameInItDoesNot()
    {
        $filePath = new FromDirAndFileName(new Tmp(), new PortableFromString('vasya'));

        $this->assertFalse($filePath->exists());
        $this->assertTrue($filePath->value()->isSuccessful());
        $this->assertEquals(
            sprintf('%s/vasya', (new Tmp())->value()->pure()->raw()),
            $filePath->value()->pure()->raw()
        );
    }

    public function testPathExistsButItIsADirectory()
    {
        (new Created(new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya'))))->value();

        $filePath = new FromDirAndFileName(new Tmp(), new PortableFromString('vasya'));

        $this->assertFalse($filePath->exists());
        $this->assertTrue($filePath->value()->isSuccessful());
        $this->assertEquals(
            sprintf('%s/vasya', (new Tmp())->value()->pure()->raw()),
            $filePath->value()->pure()->raw()
        );
    }

    public function testWhenFileExistsThenEverythingIsOK()
    {
        (new CreatedFile(
            new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
            new Emptie()
        ))
            ->value();

        $filePath = new FromDirAndFileName(new Tmp(), new PortableFromString('vasya'));

        $this->assertTrue($filePath->exists());
        $this->assertTrue($filePath->value()->isSuccessful());
        $this->assertEquals(
            sprintf('%s/vasya', (new Tmp())->value()->pure()->raw()),
            $filePath->value()->pure()->raw()
        );
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }
}