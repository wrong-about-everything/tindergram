<?php

declare(strict_types=1);

namespace TG\Infrastructure\UserStory;

interface UserStory
{
    public function response(): Response;

    public function exists(): bool;
}