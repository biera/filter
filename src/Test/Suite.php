<?php declare(strict_types=1);

namespace Biera\Filter\Test;

use PHPUnit\Framework\TestCase;
use Biera\Filter\FilterExpressionBuilder;
use Biera\Filter\Operator;

abstract class Suite extends TestCase
{
    /**
     * @test
     * @dataProvider dataProvider
     */
    public function itFiltersCollection(Operator $expression, array $expectedResults): void
    {
        $this->assertEquals(
            $expectedResults, $this->getFilterableCollection()->findByFilters($expression)
        );
    }

    public function dataProvider(): array
    {
        return [
            // "eq" operator with many-to-many relation: all movies with Samuel L Jackson
            [
                FilterExpressionBuilder::create()
                    ->eq('actor.fullName', 'Samuel L Jackson')
                    ->build(),
                [
                    'Jackie Brown',
                    'Pulp Fiction'
                ]
            ],
            // "eq" operator: all movies titled Blues Brothers
            [
                FilterExpressionBuilder::create()
                    ->eq('title', 'Blues Brothers')
                    ->build(),
                [
                    'Blues Brothers'
                ]
            ],
            // "lte" operator: all movies released in XI century
            [
                FilterExpressionBuilder::create()
                    ->lte('releaseDate', new \DateTime('2000-12-31'))
                    ->build(),
                [
                    'Amores Perros',
                    'Arizona Dream',
                    'Blues Brothers',
                    'Crna mačka, beli mačor',
                    'Dom za vješanje',
                    'Easy Rider',
                    'Interview with the Vampire: The Vampire Chronicles',
                    'Jackie Brown',
                    'Pulp Fiction',
                    'The shining'
                ]
            ],
            // "like" operator: all movies with "Brothers" keyword
            [
                FilterExpressionBuilder::create()
                    ->like('title', '%Brothers%')
                    ->build(),
                [
                    'Blues Brothers',
                    'Brothers'
                ]
            ],
            // "in" operator: all biography or adventure movies
            [
                FilterExpressionBuilder::create()
                    ->in('genre.name', ['biography', 'adventure'])
                    ->build(),
                [
                    'Easy Rider',
                    'El abrazo de la serpiente',
                    'El ángel',
                    'The Pursuit of Happyness'
                ]
            ],
            // "and" operator: all biography or adventure movies that last more than 120 minutes
            [
                FilterExpressionBuilder::create()
                    ->and()
                        ->in('genre.name', ['biography', 'adventure'])
                        ->gt('duration', 120)
                    ->end()
                    ->build(),
                [
                    'El abrazo de la serpiente'
                ]
            ],

            // "or" operator: all biography movies or that last more than 120 minutes
            [
                FilterExpressionBuilder::create()
                    ->or()
                        ->eq('genre.name', 'biography')
                        ->gt('duration', 120)
                    ->end()
                    ->build(),
                [
                    'Amores Perros',
                    'Arizona Dream',
                    'Blues Brothers',
                    'Dom za vješanje',
                    'El abrazo de la serpiente',
                    'El ángel',
                    'Interview with the Vampire: The Vampire Chronicles',
                    'Jackie Brown',
                    'Pulp Fiction',
                    'The Pursuit of Happyness',
                    'The shining'
                ]
            ]
        ];
    }

    public abstract function getFilterableCollection(): FilterableCollection;

    protected static function loadSQLSchemaAndData(\PDO $connection): void
    {
        $statements = array_filter(
            explode("\n", file_get_contents(__DIR__ . '/../../resources/dump.sql'))
        );

        $connection->beginTransaction();

        foreach ($statements as $statement) {
            $connection->exec($statement);
        }

        $connection->commit();
    }
}
