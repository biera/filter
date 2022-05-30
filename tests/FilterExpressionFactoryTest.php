<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Biera\Filter\FilterExpressionFactory;

class FilterExpressionFactoryTest extends TestCase
{
    /**
     * @test
     * @dataProvider validFilterExpressionDataProvider
     */
    public function itBuildsFilterExpressionFromJson(string $jsonFilterExpression): void
    {
        $this->assertJsonStringEqualsJsonString(
            json_encode(FilterExpressionFactory::createFromJson($jsonFilterExpression)), $jsonFilterExpression
        );
    }

    /**
     * @test
     * @dataProvider malformedFilterExpressionDataProvider
     */
    public function itThrowsExceptionWhenMalformedFilterExpressionProvided(string $filterExpression, string $expectedErrorMessage): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedErrorMessage);
        FilterExpressionFactory::createFromJson($filterExpression);
    }

    public function validFilterExpressionDataProvider(): array
    {
        return [
            [
                json_encode(
                    [
                        'true' => null
                    ]
                )
            ],
            [
                json_encode(
                    [
                        'false' => null
                    ]
                )
            ],
            [
                json_encode(
                    [
                        'null' => 'email'
                    ]
                )
            ],
            [
                json_encode(
                    [
                        'notNull' => 'email'
                    ]
                )
            ],
            [
                json_encode(
                    [
                        'lt' => [
                            'age' => 18
                        ]
                    ]
                )
            ],
            [
                json_encode(
                    [
                        'lte' => [
                            'age' => 18
                        ]
                    ]
                )
            ],
            [
                json_encode(
                    [
                        'gt' => [
                            'age' => 18
                        ]
                    ]
                )
            ],
            [
                json_encode(
                    [
                        'gte' => [
                            'age' => 18
                        ]
                    ]
                )
            ],
            [
                json_encode(
                    [
                        'in' => [
                            'id' => [1,2,3]
                        ]
                    ]
                )
            ],
            [
                json_encode(
                    [
                        'eq' => [
                            'id' => 1
                        ]
                    ]
                )
            ],
            [
                json_encode(
                    [
                        'neq' => [
                            'id' => 1
                        ]
                    ]
                )
            ],
            [
                json_encode(
                    [
                        'like' => [
                            'email' => '%@example.com'
                        ]
                    ]
                )
            ],
            [
                json_encode(
                    [
                        'and' => [
                            [
                                'like' => [
                                    'name' => '%Association'
                                ]
                            ],
                            [
                                'notNull' => 'email'
                            ]
                        ]
                    ]
                )
            ],
            [
                json_encode(
                    [
                        'or' => [
                            [
                                'like' => [
                                    'email' => '%admin%'
                                ]
                            ],
                            [
                                'null' => 'password'
                            ],
                            [
                                'true' => null
                            ]
                        ]
                    ]
                )
            ]
        ];
    }

    public function malformedFilterExpressionDataProvider(): array
    {
        return [
            [
                // malformed filter expression: "and" gets at least two parameters
                json_encode(
                    [
                        'and' => [
                            [
                                'lt' => [
                                    'age' => 18
                                ]
                            ]
                        ]
                    ]
                ),
                // expected error message
                "Operator 'and' takes at least 2 operands, 1 provided"
            ],
            [
                // malformed filter expression: "or" gets at least two parameters
                json_encode(
                    [
                        'or' => []
                    ]
                ),
                // expected error message
                "Operator 'or' takes at least 2 operands, 0 provided"
            ],
            [
                // malformed filter expression: "null" gets exactly one parameter of string type (identifier)
                json_encode(
                    [
                        'null' => []
                    ]
                ),
                // expected error message
                "Operator 'null' takes exactly 1 operand provided as {\"null\": value<string>} (e.g: {\"null\": \"age\"})."
            ],
            [
                // malformed filter expression: "notNull" gets exactly one parameter of string type (identifier)
                json_encode(
                    [
                        "notNull" => []
                    ]
                ),
                // expected error message
                "Operator 'notNull' takes exactly 1 operand provided as {\"notNull\": value<string>} (e.g: {\"notNull\": \"age\"})."
            ],
            [
                // malformed filter expression: missing expression type
                json_encode(
                    [
                        [
                            "gt" => [
                                "age" => 18
                            ]
                        ]
                    ]
                ),
                'Expression must be an object with exactly one property (operator name).'
            ],
            [
                // malformed filter expression: "greaterThan" not supported
                json_encode(
                    [
                        'greaterThan' => [
                            'age' => 18
                        ]
                    ]
                ),
                // expected error message
                "Unsupported expression type: 'greaterThan'"
            ],
            [
                // malformed filter expression: malformed JSON
                '[{property:"malformed JSON"]',
                // expected error message
                'Invalid JSON provided: [{property:"malformed JSON"]'
            ]
        ];
    }
}
