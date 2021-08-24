<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Activities\User\RegistersInBot\Domain\Reply;

use PHPUnit\Framework\TestCase;
use TG\Domain\Bot\BotId\BotId;
use TG\Domain\Bot\BotId\FromUuid;
use TG\Domain\Experience\ExperienceId\Pure\BetweenAYearAndThree;
use TG\Domain\Experience\ExperienceId\Pure\BetweenThreeYearsAndSix;
use TG\Domain\Experience\ExperienceId\Pure\GreaterThanSix;
use TG\Domain\Experience\ExperienceId\Pure\LessThanAYear;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Domain\Position\PositionId\Pure\SystemOrBusinessAnalyst;
use TG\Domain\Position\PositionId\Pure\ProductDesigner;
use TG\Domain\Position\PositionId\Pure\ProductManager;
use TG\Activities\User\RegistersInBot\Domain\Reply\NextRegistrationQuestionReplyToUser;
use TG\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Experience;
use TG\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Position;
use TG\Domain\TelegramUser\UserId\FromUuid as UserIdFromUuid;
use TG\Domain\TelegramUser\UserId\TelegramUserId;
use TG\Domain\BotUser\UserStatus\Pure\RegistrationIsInProgress;
use TG\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery;
use TG\Infrastructure\Http\Request\Url\Query\FromUrl;
use TG\Infrastructure\Http\Transport\Indifferent;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\FromInteger;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;
use TG\Infrastructure\Uuid\Fixed;
use TG\Infrastructure\Uuid\FromString;
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Stub\Table\Bot;
use TG\Tests\Infrastructure\Stub\Table\BotUser;
use TG\Tests\Infrastructure\Stub\Table\RegistrationQuestion;
use TG\Tests\Infrastructure\Stub\Table\TelegramUser;

class NextRegistrationQuestionReplyTest extends TestCase
{
    public function testPositionQuestion()
    {
        $connection = new ApplicationConnection();
        $httpTransport = new Indifferent();
        $this->seedBot($this->botId(), $connection);
        $this->seedBotUser($this->botId(), $this->telegramUserId(), $connection);
        $this->seedPositionQuestion($this->botId(), $connection);

        (new NextRegistrationQuestionReplyToUser(
            $this->telegramUserId(),
            $this->botId(),
            $connection,
            $httpTransport
        ))
            ->value();

        $this->assertCount(1, $httpTransport->sentRequests());
        $this->assertEquals('Кем работаете?', (new FromQuery(new FromUrl($httpTransport->sentRequests()[0]->url())))->value()['text']);
        $this->assertEquals(
            [
                [['text' => 'Продакт-менеджер'], ['text' => 'Продуктовый дизайнер']],
                [['text' => 'Системный/бизнес-аналитик']],
            ],
            json_decode(
                (new FromQuery(
                    new FromUrl(
                        $httpTransport->sentRequests()[0]->url()
                    )
                ))
                    ->value()['reply_markup'],
                true
            )['keyboard']
        );
    }

    public function testExperienceQuestion()
    {
        $connection = new ApplicationConnection();
        $httpTransport = new Indifferent();
        $this->seedBot($this->botId(), $connection);
        $this->seedBotUser($this->botId(), $this->telegramUserId(), $connection);
        $this->seedExperienceQuestion($this->botId(), $connection);

        (new NextRegistrationQuestionReplyToUser(
            $this->telegramUserId(),
            $this->botId(),
            $connection,
            $httpTransport
        ))
            ->value();

        $this->assertCount(1, $httpTransport->sentRequests());
        $this->assertEquals('Сколько?', (new FromQuery(new FromUrl($httpTransport->sentRequests()[0]->url())))->value()['text']);
        $this->assertEquals(
            [
                [['text' => 'Меньше года'], ['text' => 'От года до трёх лет']],
                [['text' => 'От трёх лет до шести'], ['text' => 'Больше шести лет']],
            ],
            json_decode(
                (new FromQuery(
                    new FromUrl(
                        $httpTransport->sentRequests()[0]->url()
                    )
                ))
                    ->value()['reply_markup'],
                true
            )['keyboard']
        );
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function telegramUserId(): InternalTelegramUserId
    {
        return new FromInteger(987654);
    }

    private function botId(): BotId
    {
        return new FromUuid(new Fixed());
    }

    private function userId(): TelegramUserId
    {
        return new UserIdFromUuid(new FromString('103729d6-330c-4123-b856-d5196812d509'));
    }

    private function seedBotUser(BotId $botId, InternalTelegramUserId $telegramUserId, OpenConnection $connection)
    {
        (new TelegramUser($connection))
            ->insert([
                ['id' => $this->userId()->value(), 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $telegramUserId->value(), 'telegram_handle' => 'dremuchee_bydlo'],
            ]);
        (new BotUser($connection))
            ->insert([
                ['bot_id' => $botId->value(), 'user_id' => $this->userId()->value(), 'status' => (new RegistrationIsInProgress())->value()]
            ]);
    }

    private function seedBot(BotId $botId, OpenConnection $connection)
    {
        (new Bot($connection))
            ->insert([
                [
                    'id' => $botId->value(),
                    'available_positions' => [(new ProductManager())->value(), (new ProductDesigner())->value(), (new SystemOrBusinessAnalyst())->value()],
                    'available_experiences' => [(new LessThanAYear())->value(), (new BetweenAYearAndThree())->value(), (new BetweenThreeYearsAndSix())->value(), (new GreaterThanSix())->value()],
                ]
            ]);
    }

    private function seedPositionQuestion(BotId $botId, OpenConnection $connection)
    {
        (new RegistrationQuestion($connection))
            ->insert([
                ['profile_record_type' => (new Position())->value(), 'bot_id' => $botId->value(), 'text' => 'Кем работаете?']
            ]);
    }

    private function seedExperienceQuestion(BotId $botId, OpenConnection $connection)
    {
        (new RegistrationQuestion($connection))
            ->insert([
                ['profile_record_type' => (new Experience())->value(), 'bot_id' => $botId->value(), 'text' => 'Сколько?']
            ]);
    }
}