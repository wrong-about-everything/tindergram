<?php

declare(strict_types=1);

namespace TG\Tests\Unit\Domain\Matches;

use PHPUnit\Framework\TestCase;
use TG\Domain\Matches\ReadModel\Pure\FromArray;
use TG\Domain\Matches\ReadModel\Pure\WithMatchedDropoutsWithinTheSameSegment;

class WithMatchedDropoutsWithinTheSameSegmentTest extends TestCase
{
    /**
     * @dataProvider originalMatchesAndMatchesWithMatchedDropouts
     */
    public function testDifferentCombinations(array $originalMatches, array $matchesWithMatchedDropouts)
    {
        $this->assertEquals(
            $matchesWithMatchedDropouts,
            (new WithMatchedDropoutsWithinTheSameSegment(
                new FromArray($originalMatches)
            ))
                ->value()
        );
    }

    public function originalMatchesAndMatchesWithMatchedDropouts()
    {
        return [
            [
                [
                    'dropouts' => [1],
                    'matches' => [],
                ],
                [
                    'dropouts' => [1],
                    'matches' => [],
                ],
            ],
            [
                [
                    'dropouts' => [],
                    'matches' => [[1, 2]],
                ],
                [
                    'dropouts' => [],
                    'matches' => [[1, 2]],
                ],
            ],
            [
                [
                    'dropouts' => [2],
                    'matches' => [[1, 3]],
                ],
                [
                    'dropouts' => [2],
                    'matches' => [[1, 3]],
                ],
            ],
            [
                [
                    'dropouts' => [4, 2],
                    'matches' => [[1, 3]],
                ],
                [
                    'dropouts' => [],
                    'matches' => [[1, 3], [4, 2]],
                ],
            ],
            [
                [
                    'dropouts' => [4, 7, 6],
                    'matches' => [[1, 3], [5, 2]],
                ],
                [
                    'dropouts' => [6],
                    'matches' => [[1, 3], [5, 2], [4, 7]],
                ]
            ],
        ];
    }
}