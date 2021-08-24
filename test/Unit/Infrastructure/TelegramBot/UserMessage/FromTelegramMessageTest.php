<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Infrastructure\TelegramBot\UserMessage;

use PHPUnit\Framework\TestCase;
use TG\Infrastructure\TelegramBot\UserMessage\Pure\FromTelegramMessage;

class FromTelegramMessageTest extends TestCase
{
    public function testWithEmptyBody()
    {
        $this->assertFalse((new FromTelegramMessage(''))->exists());
    }
}