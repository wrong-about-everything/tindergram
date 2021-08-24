<?php

declare(strict_types=1);

namespace TG\Infrastructure\Filesystem\FilePath;

use Exception;
use TG\Infrastructure\Filesystem\FilePath;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class ExistentFromAbsolutePathString extends FilePath
{
    private $path;

    public function __construct(string $path)
    {
        if (preg_match('/^(\/[A-Za-z0-9._-]+)+$/', $path) === 0) {
            throw new Exception(sprintf('Dir path %s is invalid. You must specify a valid absolute path, for example /usr/lib/cpp.', $path));
        }
        $canonicalized = realpath($path);
        if ($canonicalized === false) {
            throw new Exception(sprintf('%s does not exist', $path));
        }
        if (!is_file($canonicalized)) {
            throw new Exception(sprintf('%s is not a file', $path));
        }

        $this->path = $canonicalized;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->path));
    }

    public function exists(): bool
    {
        return true;
    }
}