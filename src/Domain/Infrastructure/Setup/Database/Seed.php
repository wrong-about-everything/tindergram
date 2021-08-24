<?php

declare(strict_types=1);

namespace TG\Domain\Infrastructure\Setup\Database;

use Ramsey\Uuid\Uuid;
use TG\Domain\Experience\ExperienceId\Pure\BetweenAYearAndThree;
use TG\Domain\Experience\ExperienceId\Pure\BetweenThreeYearsAndSix;
use TG\Domain\Experience\ExperienceId\Pure\GreaterThanSix;
use TG\Domain\Experience\ExperienceId\Pure\LessThanAYear;
use TG\Domain\Position\PositionId\Pure\CEO;
use TG\Domain\Position\PositionId\Pure\Marketer;
use TG\Domain\Position\PositionId\Pure\ProductAnalyst;
use TG\Domain\Position\PositionId\Pure\ProjectManager;
use TG\Domain\Position\PositionId\Pure\SystemOrBusinessAnalyst;
use TG\Domain\Position\PositionId\Pure\ProductDesigner;
use TG\Domain\Position\PositionId\Pure\ProductManager;
use TG\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\About;
use TG\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Experience;
use TG\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Position;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Emptie;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use TG\Infrastructure\SqlDatabase\Agnostic\Query\TransactionalQueryFromMultipleQueries;

class Seed
{
    private $connection;

    public function __construct(OpenConnection $connection)
    {
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        $addGorgonzolaBot = $this->addGorgonzolaBot();
        if (!$addGorgonzolaBot->isSuccessful()) {
            return $addGorgonzolaBot;
        }

        $addAnalysisParadysisGroup = $this->addAnalysisParadysisGroup();
        if (!$addAnalysisParadysisGroup->isSuccessful()) {
            return $addAnalysisParadysisGroup;
        }

        $addRegistrationQuestions = $this->addRegistrationQuestions();
        if (!$addRegistrationQuestions->isSuccessful()) {
            return $addRegistrationQuestions;
        }

        return new Successful(new Emptie());
    }

    private function addGorgonzolaBot()
    {
        return
            (new SingleMutating(
                'insert into bot values (?, ?, \'false\', ?, ?, ?)',
                [
                    '1f6d0fd5-3179-47fb-b92d-f6bec4e8f016',
                    '1884532101:AAGlJklZYP5j72nC2UcvB0IbD05i70kQqWc',
                    '@gorgonzola_sandwich_bot',
                    json_encode([
                        (new CEO())->value(),
                        (new ProductManager())->value(),
                        (new ProjectManager())->value(),
                        (new Marketer())->value(),
                        (new ProductDesigner())->value(),
                        (new ProductAnalyst())->value(),
                        (new SystemOrBusinessAnalyst())->value(),
                    ]),
                    json_encode([
                        (new LessThanAYear())->value(),
                        (new BetweenAYearAndThree())->value(),
                        (new BetweenThreeYearsAndSix())->value(),
                        (new GreaterThanSix())->value()
                    ])
                ],
                $this->connection
            ))
                ->response();
    }

    private function addAnalysisParadysisGroup()
    {
        return
            (new SingleMutating(
                'insert into "group" values (?, ?, ?)',
                [Uuid::uuid4()->toString(), '1f6d0fd5-3179-47fb-b92d-f6bec4e8f016', 'Analysis Paradysis'],
                $this->connection
            ))
                ->response();
    }

    private function addRegistrationQuestions()
    {
        return
            (new TransactionalQueryFromMultipleQueries(
                [
                    new SingleMutating(
                        'insert into registration_question (id, profile_record_type, bot_id, ordinal_number, text) values (?, ?, ?, ?, ?)',
                        [
                            Uuid::uuid4()->toString(),
                            (new Position())->value(),
                            '1f6d0fd5-3179-47fb-b92d-f6bec4e8f016',
                            1,
                            'Привет, это бот для поиска интересного собеседника. Для того, чтобы я нашёл вам подходящую пару, ответьте, пожалуйста, на три вопроса. Итак, первый: кем вы работаете?'
                        ],
                        $this->connection
                    ),
                    new SingleMutating(
                        'insert into registration_question (id, profile_record_type, bot_id, ordinal_number, text) values (?, ?, ?, ?, ?)',
                        [Uuid::uuid4()->toString(), (new Experience())->value(), '1f6d0fd5-3179-47fb-b92d-f6bec4e8f016', 2, 'Какой у вас опыт работы на этой должности?'],
                        $this->connection
                    ),
                    new SingleMutating(
                        'insert into registration_question (id, profile_record_type, bot_id, ordinal_number, text) values (?, ?, ?, ?, ?)',
                        [Uuid::uuid4()->toString(), (new About())->value(), '1f6d0fd5-3179-47fb-b92d-f6bec4e8f016', 3, 'Можете написать пару слов о себе для вашего собеседника. Где вы работаете, чем увлекаетесь, в каких областях вы можете поделиться своим опытом, а в каких хотели бы прокачаться лучше.'],
                        $this->connection
                    ),
                ],
                $this->connection
            ))
                ->response();
    }
}