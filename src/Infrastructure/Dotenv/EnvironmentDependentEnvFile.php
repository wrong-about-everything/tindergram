<?php

declare(strict_types=1);

namespace TG\Infrastructure\Dotenv;

use Dotenv\Dotenv as OneAndOnly;
use TG\Infrastructure\Filesystem\DirPath;
use TG\Infrastructure\Filesystem\Filename\PortableFromString;
use TG\Infrastructure\Filesystem\FilePath\FromDirAndFileName;
use TG\Infrastructure\Http\Request\Inbound\Request;

class EnvironmentDependentEnvFile implements DotEnv
{
    private $concreteDotEnv;

    public function __construct(DirPath $root, Request $inboundRequest)
    {
        $this->concreteDotEnv = $this->concrete($root, $inboundRequest);
    }

    public function load(): void
    {
        $this->concreteDotEnv->load();
    }

    private function concrete(DirPath $root, Request $inboundRequest): DotEnv
    {
        if ((new FromDirAndFileName($root, new PortableFromString('.env.dev')))->exists()) {
            if (isset($inboundRequest->headers()['X-This-Is-Functional-Test']) && $inboundRequest->headers()['X-This-Is-Functional-Test'] === '1') {
                return new DefaultEnvFile(OneAndOnly::createUnsafeImmutable($root->value()->pure()->raw(), '.env.dev.testing_mode'));
            } else {
                return new DefaultEnvFile(OneAndOnly::createUnsafeImmutable($root->value()->pure()->raw(), '.env.dev'));
            }
        }

        return new NonExistentEnvFile();
    }
}
