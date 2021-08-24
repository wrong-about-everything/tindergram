<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Infrastructure\Filesystem\DirPath;

use PHPUnit\Framework\TestCase;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Infrastructure\Filesystem\DirPath\Copied;
use TG\Infrastructure\Filesystem\DirPath\Created;
use TG\Infrastructure\Filesystem\DirPath\FromNestedDirectoryNames;
use TG\Infrastructure\Filesystem\Filename\PortableFromString;
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Filesystem\DirPath\Tmp;

class CopiedTest extends TestCase
{
    public function testWhenOriginDirectoryDoesNotExistThenItIsNotCopied()
    {
        $dir =
            new Copied(
                new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya')),
                new FromNestedDirectoryNames(new Tmp(), new PortableFromString('fedya'))
            );

        $this->assertFalse($dir->exists());
        $this->assertFalse($dir->value()->isSuccessful());
    }

    public function testWhenBothOriginAndDestinationDirectoriesExistThenOriginIsNotCopied()
    {
        (new Created(new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya'))))->value();
        (new Created(new FromNestedDirectoryNames(new Tmp(), new PortableFromString('fedya'))))->value();

        $dir =
            new Copied(
                new FromNestedDirectoryNames(new Tmp(), new PortableFromString('vasya')),
                new FromNestedDirectoryNames(new Tmp(), new PortableFromString('fedya'))
            );

        $this->assertTrue($dir->exists());
        $this->assertFalse($dir->value()->isSuccessful());
    }

    public function testWhenOriginDirectoryExistsAndDestinationDoesNotThenOriginIsCopied()
    {
        $this->markTestIncomplete('Implement corresponding logic');
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }
}