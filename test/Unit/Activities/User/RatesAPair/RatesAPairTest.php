<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Activities\User\RatesAPair;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use TG\Activities\User\RatesAPair\RatesAPair;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Domain\BotUser\UserStatus\Pure\Registered;
use TG\Domain\InternalApi\RateCallbackData\ThumbsDown;
use TG\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery;
use TG\Infrastructure\Http\Request\Url\Query\FromUrl;
use TG\Infrastructure\Http\Transport\HttpTransport;
use TG\Infrastructure\Http\Transport\Indifferent;
use TG\Infrastructure\Logging\Logs\DevNull;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Stub\Table\BotUser;

class RatesAPairTest extends TestCase
{
    public function testWhenUserDownvotesNewPairThenHisChoiceIsPersistedAndHeSeesANextPair()
    {
        $connection = new ApplicationConnection();
        $this->createBotUser($this->recipientTelegramId(), $connection);
        $this->createBotUser($this->pairTelegramId(), $connection);
        $transport = new Indifferent();

        $response = $this->userReply($this->recipientTelegramId(), $this->pairTelegramId(), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(2, $transport->sentRequests());
        $this->assertEquals(
            'К сожалению, мы пока не можем принять ответ в виде текста. Поэтому выберите, пожалуйста, один из вариантов ответа. Если ни один не подходит — напишите в @flurr_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
        $this->assertEquals(
            [['text' => 'Мужские'], ['text' => 'Женские']],
            json_decode((new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['reply_markup'], true)['keyboard'][0]
        );
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function recipientTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(1111111);
    }

    private function pairTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(22222222);
    }

    private function createBotUser(InternalTelegramUserId $telegramUserId, OpenConnection $connection)
    {
        (new BotUser($connection))
            ->insert([
                [
                    'id' => Uuid::uuid4()->toString(),
                    'first_name' => 'Vadim',
                    'last_name' => 'Samokhin',
                    'telegram_id' => $telegramUserId->value(),
                    'telegram_handle' => 'dremuchee_bydlo',
                    'status' => (new Registered())->value(),
                ]
            ]);
    }

    private function userReply(InternalTelegramUserId $voterTelegramId, InternalTelegramUserId $pairTelegramId, HttpTransport $transport, OpenConnection $connection)
    {
        return
            new RatesAPair(
                $voterTelegramId,
                new ThumbsDown($pairTelegramId),
                $transport,
                $connection,
                new DevNull()
            );
    }
}