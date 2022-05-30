<?php declare(strict_types=1);

use Biera\Filter\Operator;
use PHPUnit\Framework\TestCase;
use Biera\Filter\FilterExpressionBuilder;

class FilterExpressionBuilderTest extends TestCase
{
    /**
     * @test
     * @dataProvider dataProvider
     */
    public function itBuildsExpression(FilterExpressionBuilder $builder, $expectedExpression): void
    {
        $this->assertJsonStringEqualsJsonString(
            $expectedExpression, json_encode($builder->build())
        );
    }

    /**
     * FilterExpressionBuilder::build could be called
     * at the end of building process
     * when expression tree is not empty, e.g:
     *
     * FilterExpressionBuilder::create()
     *      ->lt()
     *          ->identifier('age')
     *          ->value(18)
     *      ->end()
     *      ->build()
     *
     * @test
     */
    public function itThrowsExceptionWhenBuildMethodCalledOutOfContext(): void
    {
        $this->expectException(\BadMethodCallException::class);
        FilterExpressionBuilder::create()->build();
    }

    /**
     * FilterExpressionBuilder::end could be called
     * in operator context, e.g:
     *
     * FilterExpressionBuilder::create()
     *      ->and()
     *          ->lt('age', 18)
     *          ->eq('firstName', 'Joe')
     *      ->end()
     *      ->build()
     *
     * @test
     */
    public function itThrowsExceptionWhenEndMethodCalledOutOfContext(): void
    {
        $this->expectException(\BadMethodCallException::class);
        FilterExpressionBuilder::create()->end();
    }

    /**
     * FilterExpressionBuilder::end could be called
     * in operator context, the required number of operands
     * is also checked, e.g "and" operator requires at least
     * two operands, other number is invalid and exception is thrown.
     *
     * FilterExpressionBuilder::create()
     *      ->and()
     *          ->lt('age', 18)
     *          ->like('lastName', '%oe')
     *      ->end()
     *      ->build()
     *
     * @test
     */
    public function itThrowsExceptionWhenEndMethodCalledAndOperandsNumberInvalid(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Method end is not callable in current context: operator and requires at least 2 operands, 1 given');

        FilterExpressionBuilder::create()
            ->and()
                ->lt('age', 18)
            ->end()
            ->build();
    }

    /**
     * @test
     */
    public function itThrowsExceptionWhenOperatorsNestedIncorrectly(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Method and is not callable in current context.');

        FilterExpressionBuilder::create()
            ->lt('age', 18)
                ->and()
                    ->eq('firstName', 'John')
                    ->eq('lastName', 'Doe')
                ->end()
            ->end()
            ->build();
    }

    public function dataProvider(): array
    {
        return [
            [
                FilterExpressionBuilder::create()
                    ->true(),
                '{"true":null}'
            ],
            [
                FilterExpressionBuilder::create()
                    ->false(),
                '{"false":null}'
            ],
            [
                FilterExpressionBuilder::create()
                    ->null('email'),
                '{"null":"email"}'
            ],
            [
                FilterExpressionBuilder::create()
                    ->notNull('email'),
                '{"notNull":"email"}'
            ],
            [
                FilterExpressionBuilder::create()
                    ->lt('age', 18),
                '{"lt":{"age":18}}'
            ],
            [
                FilterExpressionBuilder::create()
                    ->lte('age', 18),
                '{"lte":{"age":18}}'
            ],
            [
                FilterExpressionBuilder::create()
                    ->gt('age', 18),
                '{"gt":{"age":18}}'
            ],
            [
                FilterExpressionBuilder::create()
                    ->gte('age', 18),
                '{"gte":{"age":18}}'
            ],
            [
                FilterExpressionBuilder::create()
                    ->in('id', [1, 2, 3]),
                '{"in":{"id":[1,2,3]}}'
            ],
            [
                FilterExpressionBuilder::create()
                    ->notIn('id', [1, 2, 3]),
                '{"notIn":{"id":[1,2,3]}}'
            ],
            [
                FilterExpressionBuilder::create()
                    ->eq('age', 18),
                '{"eq":{"age":18}}'
            ],
            [
                FilterExpressionBuilder::create()
                    ->neq('age', 18),
                '{"neq":{"age":18}}'
            ],
            [
                FilterExpressionBuilder::create()
                    ->like('firstName', '%oe'),
                '{"like":{"firstName":"%oe"}}'
            ],
            [
                FilterExpressionBuilder::create()
                    ->or()
                        ->like('firstName', '%oe')
                        ->like('lastName', '%oe')
                    ->end(),
                '{"or":[{"like":{"firstName":"%oe"}},{"like":{"lastName":"%oe"}}]}'
            ],
            [
                FilterExpressionBuilder::create()
                    ->and()
                        ->like('firstName', '%oe')
                        ->like('lastName', '%oe')
                    ->end(),
                '{"and":[{"like":{"firstName":"%oe"}},{"like":{"lastName":"%oe"}}]}'
            ],
            [
                FilterExpressionBuilder::create()
                    ->and()
                        ->like('firstName', '%oe')
                        ->or()
                            ->notNull('email')
                            ->gt('age', 18)
                        ->end()
                    ->end(),
                '{"and":[{"like":{"firstName":"%oe"}},{"or":[{"notNull":"email"},{"gt":{"age":18}}]}]}'
            ],
            [
                FilterExpressionBuilder::create()
                    ->and()
                        ->expression(
                            FilterExpressionBuilder::create()
                                ->like('firstName', '%oe')
                                ->build()
                        )
                        ->or()
                            ->expression(Operator::notNull('email'))
                            ->expression(Operator::gt('age', 18))
                        ->end()
                    ->end(),
                '{"and":[{"like":{"firstName":"%oe"}},{"or":[{"notNull":"email"},{"gt":{"age":18}}]}]}'
            ]
        ];
    }
}
