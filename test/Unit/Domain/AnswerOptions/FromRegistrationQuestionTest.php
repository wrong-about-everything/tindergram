<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Domain\AnswerOptions;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use TG\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\About;
use TG\Domain\SentReplyToUser\ReplyOptions\FromRegistrationQuestion;
use TG\Domain\Bot\BotId\BotId;
use TG\Domain\Bot\BotId\FromUuid;
use TG\Domain\Experience\ExperienceId\Pure\BetweenAYearAndThree;
use TG\Domain\Experience\ExperienceId\Pure\BetweenThreeYearsAndSix;
use TG\Domain\Experience\ExperienceId\Pure\GreaterThanSix;
use TG\Domain\Experience\ExperienceId\Pure\LessThanAYear;
use TG\Domain\Experience\ExperienceName\BetweenAYearAndThreeName;
use TG\Domain\Experience\ExperienceName\BetweenThreeYearsAndSixName;
use TG\Domain\Experience\ExperienceName\GreaterThanSixYearsName;
use TG\Domain\Experience\ExperienceName\LessThanAYearName;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use TG\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use TG\Domain\Position\PositionId\Pure\FromInteger;
use TG\Domain\Position\PositionId\Pure\ProductAnalyst;
use TG\Domain\Position\PositionId\Pure\ProductDesigner;
use TG\Domain\Position\PositionId\Pure\ProductManager;
use TG\Domain\Position\PositionId\Pure\ProjectManager;
use TG\Domain\Position\PositionId\Pure\SystemOrBusinessAnalyst;
use TG\Domain\Position\PositionName\FromPosition;
use TG\Domain\Position\PositionName\ProductAnalystName;
use TG\Domain\Position\PositionName\ProductDesignerName;
use TG\Domain\Position\PositionName\ProductManagerName;
use TG\Domain\Position\PositionName\ProjectManagerName;
use TG\Domain\Position\PositionName\SystemOrBusinessAnalystName;
use TG\Domain\RegistrationQuestion\ById;
use TG\Domain\RegistrationQuestion\RegistrationQuestion as DomainRegistrationQuestion;
use TG\Domain\RegistrationQuestion\RegistrationQuestionId\Pure\FromString as RegistrationQuestionIdFromString;
use TG\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Experience;
use TG\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Position;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\Uuid\FromString;
use TG\Tests\Infrastructure\Environment\Reset;
use TG\Tests\Infrastructure\Stub\Table\Bot;
use TG\Tests\Infrastructure\Stub\Table\RegistrationQuestion;

class FromRegistrationQuestionTest extends TestCase
{
    public function testMultiplePositions()
    {
        $connection = new ApplicationConnection();
        $this->seedBot($this->botId(), $this->availablePositionIds(), $connection);
        $registrationQuestion = $this->newPositionRegistrationQuestion($this->botId(), $connection);

        $this->assertEquals(
            [
                [['text' => (new ProductAnalystName())->value()], ['text' => (new ProductDesignerName())->value()]],
                [['text' => (new SystemOrBusinessAnalystName())->value()], ['text' => (new ProductManagerName())->value()]],
                [['text' => (new ProjectManagerName())->value()]],
            ],
            (new FromRegistrationQuestion($registrationQuestion, $this->botId(), $connection))->value()->pure()->raw()
        );
    }

    public function testMultipleExperiences()
    {
        $connection = new ApplicationConnection();
        $this->seedBot($this->botId(), $this->availablePositionIds(), $connection);
        $registrationQuestion = $this->newExperienceRegistrationQuestion($this->botId(), $connection);

        $this->assertEquals(
            [
                [['text' => (new LessThanAYearName())->value()], ['text' => (new BetweenAYearAndThreeName())->value()]],
                [['text' => (new BetweenThreeYearsAndSixName())->value()], ['text' => (new GreaterThanSixYearsName())->value()]],
            ],
            (new FromRegistrationQuestion($registrationQuestion, $this->botId(), $connection))->value()->pure()->raw()
        );
    }

    public function testAboutMeQuestionCanBeSkipped()
    {
        $connection = new ApplicationConnection();
        $this->seedBot($this->botId(), [], $connection);
        $registrationQuestion = $this->newAboutMeRegistrationQuestion($this->botId(), $connection);

        $this->assertEquals(
            [
                [['text' => 'Пропустить']],
            ],
            (new FromRegistrationQuestion($registrationQuestion, $this->botId(), $connection))->value()->pure()->raw()
        );
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function newPositionRegistrationQuestion(BotId $botId, OpenConnection $connection): DomainRegistrationQuestion
    {
        $id = Uuid::uuid4()->toString();
        (new RegistrationQuestion($connection))
            ->insert([
                ['id' => $id, 'bot_id' => $botId->value(), 'profile_record_type' => (new Position())->value()]
            ]);

        return new ById(new RegistrationQuestionIdFromString($id), $connection);
    }

    private function newExperienceRegistrationQuestion(BotId $botId, OpenConnection $connection): DomainRegistrationQuestion
    {
        $id = Uuid::uuid4()->toString();
        (new RegistrationQuestion($connection))
            ->insert([
                ['id' => $id, 'bot_id' => $botId->value(), 'profile_record_type' => (new Experience())->value()]
            ]);

        return new ById(new RegistrationQuestionIdFromString($id), $connection);
    }

    private function newAboutMeRegistrationQuestion(BotId $botId, OpenConnection $connection): DomainRegistrationQuestion
    {
        $id = Uuid::uuid4()->toString();
        (new RegistrationQuestion($connection))
            ->insert([
                ['id' => $id, 'bot_id' => $botId->value(), 'profile_record_type' => (new About())->value()]
            ]);

        return new ById(new RegistrationQuestionIdFromString($id), $connection);
    }

    private function seedBot(BotId $botId, array $availablePositions, OpenConnection $connection)
    {
        (new Bot($connection))
            ->insert([
                [
                    'id' => $botId->value(),
                    'available_positions' => $availablePositions,
                    'available_experiences' => [(new LessThanAYear())->value(), (new BetweenAYearAndThree())->value(), (new BetweenThreeYearsAndSix())->value(), (new GreaterThanSix())->value(), ]
                ]
            ]);
    }

    private function botId(): BotId
    {
        return new FromUuid(new FromString('5bf56c96-859d-4f34-ae18-8c33ba8226f7'));
    }

    private function availablePositionIds()
    {
        return [
            (new ProductAnalyst())->value(),
            (new ProductDesigner())->value(),
            (new SystemOrBusinessAnalyst())->value(),
            (new ProductManager())->value(),
            (new ProjectManager())->value()
        ];
    }
}