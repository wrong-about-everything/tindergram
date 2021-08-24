<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Infrastructure\Filesystem\FilePath;

use Exception;
use PHPUnit\Framework\TestCase;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Infrastructure\Filesystem\DirPath\Created;
use TG\Infrastructure\Filesystem\DirPath\FromNestedDirectoryNames;
use TG\Infrastructure\Filesystem\FileContents\Emptie;
use TG\Infrastructure\Filesystem\Filename\PortableFromString;
use TG\Infrastructure\Filesystem\FilePath\Created as CreatedFile;
use TG\Infrastructure\Filesystem\FilePath\ExistentFromAbsolutePathString;
use TG\Infrastructure\Filesystem\FilePath\FromDirAndFileName;
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Filesystem\DirPath\Tmp;

class ExistentFromAbsolutePathStringTest extends TestCase
{
    public function testWhenPathStringIsInvalidThenExceptionIsThrown()
    {
        try {
            new ExistentFromAbsolutePathString('36&%^*');
        } catch (Exception $e) {
            return $this->assertTrue(true);
        }

        $this->fail('An exception should have been thrown');
    }

    public function testWhenFileDoesNotExistThenExceptionIsThrown()
    {
        try {
            new ExistentFromAbsolutePathString('/sdfg/fgj/qwer/fgh/fgh/w/er/rty');
        } catch (Exception $e) {
            return $this->assertTrue(true);
        }

        $this->fail('An exception should have been thrown');
    }

    public function testWhenThisPathRepresentsADirectoryThenExceptionIsThrown()
    {
        (new Created(new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya'))))->value();

        try {
            new ExistentFromAbsolutePathString(
                (new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya')))
                    ->value()->pure()->raw()
            );
        } catch (Exception $e) {
            return $this->assertTrue(true);
        }

        $this->fail('An exception should have been thrown');
    }

    public function testWhenFileExistsThenEverythingIsOK()
    {
        (new CreatedFile(
            new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')),
            new Emptie()
        ))
            ->value();

        $file =
            new ExistentFromAbsolutePathString(
                (new FromDirAndFileName(new Tmp(), new PortableFromString('vasya')))
                    ->value()->pure()->raw()
            );

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