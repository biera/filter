<?php declare(strict_types=1);

namespace Biera\Filter;

use JsonException, RuntimeException;

class FilterExpressionFactory
{
    public static function createFromJson(string $json): Operator
    {
        try {
            return self::build(
                json_decode($json, true, 512, JSON_THROW_ON_ERROR)
            );
        } catch (JsonException $jsonException) {
            throw new RuntimeException("Invalid JSON provided: $json");
        }
    }

    public static function createFromArray(array $expression, bool $validate = false): Operator
    {
        return self::build($expression, $validate);
    }

    private static function build(array $expression, bool $validate = true): Operator
    {
        !$validate || self::assertExpressionValid($expression);

        $operator = array_key_first($expression);

        switch ($operator)
        {
            case Operator::AND :
            case Operator::OR :
                !$validate || self::assertNaryOperatorOperandsValid($expression, $operator);

                return Operator::{$operator}(
                    ...array_map(
                        fn ($expressionTree) => $expressionTree instanceOf Operator
                            ? $expressionTree : self::build($expressionTree),
                        $expression[$operator]
                    )
                );

            case Operator::LT :
            case Operator::LTE :
            case Operator::GT :
            case Operator::GTE :
            case Operator::IN :
            case Operator::EQ :
            case Operator::NEQ :
            case Operator::LIKE :
                !$validate || self::assertBinaryOperatorOperandsValid($expression, $operator);

                $identifier = array_key_first($expression[$operator]);

                return Operator::{$operator}($identifier, $expression[$operator][$identifier]);

            case Operator::NULL     :
            case Operator::NOT_NULL :
                !$validate || self::assertUnaryOperatorOperandValid($expression, $operator);

                return Operator::{$operator}($expression[$operator]);

            default :
                throw new RuntimeException(
                    "Unsupported expression type: '{$operator}'"
                );
        }
    }

    /**
     * Assert expression filter is an object (in a sens of JSON) with exactly one
     * property (expression type), e.g:
     *
     * {
     *   "and" : [/list of operands/]
     * }
     *
     * not
     *
     * {
     *   "and" : [/list of and operands/], "or" : [/list of or operands/]
     * }
     *
     * nor anything else
     */
    private static function assertExpressionValid(array $expressionTree): void
    {
        if (!self::isOneElementAssocArray($expressionTree)) {
            throw new RuntimeException(
                'Expression must be an object with exactly one property (operator name).'
            );
        }
    }

    private static function assertNaryOperatorOperandsValid(array $expression, string $operator): void
    {
        $operandsCount = count($expression[$operator]);

        if ($operandsCount < 2) {
            throw new RuntimeException(
                "Operator '{$operator}' takes at least 2 operands, {$operandsCount} provided."
            );
        }
    }

    private static function assertBinaryOperatorOperandsValid(array $expression, string $operator): void
    {
        if (!self::isOneElementAssocArray($expression)) {
            throw new RuntimeException(
                "Operator '{$operator}' takes exactly 2 operands provided as {identifier<string>: value<mixed>} (e.g: {\"age\": 18})."
            );
        }
    }

    private static function assertUnaryOperatorOperandValid(array $expression, string $operator): void
    {
        if (!is_string($expression[$operator])) {
            throw new RuntimeException(
                "Operator '{$operator}' takes exactly 1 operand provided as {\"{$operator}\": value<string>} (e.g: {\"{$operator}\": \"age\"})."
            );
        }
    }

    private static function isOneElementAssocArray($value): bool
    {
        return is_array($value) && 1 == count($value) && is_string(array_key_first($value));
    }
}
