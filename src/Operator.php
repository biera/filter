<?php declare(strict_types=1);

namespace Biera\Filter;

use BadMethodCallException;
use DateTimeInterface;
use InvalidArgumentException;
use JsonSerializable;
use LogicException;

final class Operator implements JsonSerializable
{
    public const AND = 'and';
    public const OR = 'or';
    public const EQ = 'eq';
    public const NEQ = 'neq';
    public const IN = 'in';
    public const LT = 'lt';
    public const LTE = 'lte';
    public const GT = 'gt';
    public const GTE = 'gte';
    public const LIKE = 'like';
    public const NULL = 'null';
    public const NOT_NULL = 'notNull';

    private array $operands;
    private string $type;

    private function __construct()
    {
    }

    public function type(): string
    {
        return $this->type;
    }

    public function operands(): array
    {
        return $this->operands;
    }

    public function identifier(): string
    {
        if (!$this->isTerminal()) {
            throw new BadMethodCallException(
                'This method is available only for terminal operators.'
            );
        }

        return $this->operands[0];
    }

    public function literal()
    {
        if (!$this->isBinary()) {
            throw new BadMethodCallException(
                'This method is available only for binary operators.'
            );
        }

        return $this->operands[1];
    }

    public function isUnary(): bool
    {
        return in_array($this->type, [self::NULL, self::NOT_NULL]);
    }

    public function isBinary(): bool
    {
        return in_array(
            $this->type,
            [
                self::EQ,
                self::NEQ,
                self::IN,
                self::LT,
                self::LTE,
                self::GT,
                self::GTE,
                self::LIKE
            ]
        );
    }

    public function isNary(): bool
    {
        return in_array($this->type, [self::AND, self::OR]);
    }

    public function isTerminal(): bool
    {
        return $this->isUnary() || $this->isBinary();
    }

    /**
     * Constructor for NULL operator
     *
     * Example:
     *
     * Operator::null('email')
     *
     * produces "email IS NULL"
     *
     * @param string $identifier
     * @return Operator
     */
    public static function null(string $identifier): Operator
    {
        return self::unary(self::NULL, $identifier);
    }

    /**
     * Constructor for NOT NULL operator
     *
     * Example:
     *
     * Operator::notNull('email')
     *
     * produces "email IS NOT NULL"
     *
     * @param string $identifier
     * @return Operator
     */
    public static function notNull(string $identifier): Operator
    {
        return self::unary('notNull', $identifier);
    }

    /**
     * Constructor for EQ ("equal") operator
     *
     * Example:
     *
     * Operator::eq(
     *  new Identifier('firstName'), new Literal('Joe')
     * )
     *
     * produces: "firstName = Joe"
     *
     * @param string $identifier
     * @param $literal
     * @return Operator
     */
    public static function eq(string $identifier, $literal): Operator
    {
        self::assertLiteralIsValid(self::EQ, $literal);

        return self::binary(self::EQ, $identifier, $literal);
    }

    /**
     * Constructor for NEQ ("not equal") operator
     *
     * Example:
     *
     * Operator::neq(
     *  new Identifier('firstName'), new Literal('Joe')
     * )
     *
     * produces: "firstName != Joe"
     *
     * @param string $identifier
     * @param mixed $literal
     * @return Operator
     */
    public static function neq(string $identifier, $literal): Operator
    {
        self::assertLiteralIsValid(self::NEQ, $literal);

        return self::binary(self::NEQ, $identifier, $literal);
    }

    /**
     * Constructor for IN operator
     *
     * Example:
     *
     * Operator::in(
     *  new Identifier('id'), new Literal([10, 11, 12])
     * )
     *
     * produces "id IN (10, 11, 12)"
     *
     * @param string $identifier
     * @param mixed $literal
     * @return Operator
     */
    public static function in(string $identifier, $literal): Operator
    {
        self::assertLiteralIsValid(self::IN, $literal);

        return self::binary(self::IN, $identifier, $literal);
    }

    /**
     * Constructor for LT ("less than") operator
     *
     * Example:
     *
     * Operator::lt(
     *   new Identifier('age'), new Literal(30)
     * )
     *
     * produces "age < 30"
     *
     * @param string $identifier
     * @param mixed $literal
     * @return Operator
     */
    public static function lt(string $identifier, $literal): Operator
    {
        self::assertLiteralIsValid(self::LT, $literal);

        return self::binary(self::LT, $identifier, $literal);
    }

    /**
     * Constructor for LTE ("less or equal than") operator
     *
     * Example:
     *
     * Operator::lt(
     *   new Identifier('age'), new Literal(30)
     * )
     *
     * produces "age <= 30"
     *
     * @param string $identifier
     * @param mixed $literal
     * @return Operator
     */
    public static function lte(string $identifier, $literal): Operator
    {
        self::assertLiteralIsValid(self::LT, $literal);

        return self::binary(self::LTE, $identifier, $literal);
    }

    /**
     * Constructor for GT ("grater than") operator
     *
     * Example:
     *
     * Operator::gt(
     *   new Identifier('age'), new Literal(18)
     * )
     *
     * produces "age > 18"
     *
     * @param string $identifier
     * @param mixed $literal must be a valid literal
     * @return Operator
     */
    public static function gt(string $identifier, $literal): Operator
    {
        self::assertLiteralIsValid(self::GT, $literal);

        return self::binary(self::GT, $identifier, $literal);
    }

    /**
     * Constructor for GTE ("grater or equal than") operator
     *
     * Example:
     *
     * Operator::gte(
     *   new Identifier('age'), new Literal(18)
     * )
     *
     * produces "age >= 18"
     *
     * @param string $identifier
     * @param mixed $literal must be a valid
     * @return Operator
     */
    public static function gte(string $identifier, $literal): Operator
    {
        self::assertLiteralIsValid(self::GTE, $literal);

        return self::binary(self::GTE, $identifier, $literal);
    }

    /**
     * Constructor for LIKE operator
     *
     * Example:
     *
     * Operator::gt(
     *   new Identifier('firstName'), new Literal('%imon')
     * )
     *
     * produces "firstName LIKE %imon"
     *
     * @param string $identifier
     * @param mixed $literal
     * @return Operator
     */
    public static function like(string $identifier, $literal): Operator
    {
        self::assertLiteralIsValid(self::LIKE, $literal);

        return self::binary(self::LIKE, $identifier, $literal);
    }

    /**
     * Constructor for OR operator
     *
     * Example:
     *
     * Operator::or(
     *  Operator::gte(
     *    new Identifier('age'), new Literal(18),
     *  ),
     *  Operator::notNull(
     *    new Identifier('email')
     *  )
     * )
     *
     * produces "age >= 18 OR email is NOT NULL"
     *
     * @param mixed ...$operands each operand is a valid expression (literal|identifier|operator)
     * @return Operator
     */
    public static function or(...$operands): Operator
    {
        return self::nary(self::OR, ...$operands);
    }

    /**
     * Constructor for AND operator
     *
     * Example:
     *
     * Operator::and(
     *  Operator::gte(
     *    new Identifier('age'), new Literal(18),
     *  ),
     *  Operator::notNull(
     *    new Identifier('email')
     *  )
     * )
     *
     * produces "age >= 18 and email is not null"
     *
     *
     * @param mixed ...$operands each operand is a valid expression (literal|identifier|operator)
     * @return Operator
     */
    public static function and(...$operands): Operator
    {
        return self::nary(self::AND, ...$operands);
    }

    private static function unary(string $operator, string $identifier): Operator
    {
        $unaryOperator = new self();
        $unaryOperator->type = $operator;
        $unaryOperator->operands = [$identifier];

        return $unaryOperator;
    }

    private static function binary(string $operator, string $identifier, $literal): Operator
    {
        $binaryOperator = new self();
        $binaryOperator->type = $operator;
        $binaryOperator->operands = [$identifier, $literal];

        return $binaryOperator;
    }

    private static function nary(string $operator, ...$operands): Operator
    {
        self::assertOperandsAreOperators($operands);
        $naryOperator = new self();
        $naryOperator->type = $operator;
        $naryOperator->operands = $operands;

        return $naryOperator;
    }

    private static function assertOperandsAreOperators(array $operands): void
    {
        foreach ($operands as $operand) {
            if(!$operand instanceof Operator) {
                throw new InvalidArgumentException(
                    sprintf('Each operand must be of %s type.', Operator::class)
                );
            }
        }
    }

    private static function assertLiteralIsValid(string $operator, $literal): void
    {
        switch ($operator)
        {
            case self::EQ:
            case self::NEQ:
                if (
                    !($literal instanceof DateTimeInterface
                        || is_string($literal)
                        || is_float($literal)
                        || is_int($literal)
                        || is_null($literal)
                    )
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'Second operand of "%s" operator must be of ?string|int|float|\DateTime type.',
                            $operator
                        )
                    );
                }

                break;

            case self::LT:
            case self::LTE:
            case self::GT:
            case self::GTE:
                if (
                    !($literal instanceof DateTimeInterface
                        || is_float($literal)
                        || is_int($literal)
                    )
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'Second operand of "%s" operator must be of ?float|int|\DateTime type.',
                            $operator
                        )
                    );
                }

                break;

            case self::LIKE:
                if (!is_string($literal)) {
                    throw new InvalidArgumentException(
                        'Second operand of "like" operator must be of string type.'
                    );
                }

                break;

            case self::IN:
                if (!is_array($literal)) {
                    throw new InvalidArgumentException(
                        'Second operand of "in" operator must be of array type.'
                    );
                }

                break;

            default:
                // this should never happen
                throw new LogicException(
                    sprintf(
                        'Unsupported operator type: %s.',
                        $operator
                    )
                );
        }
    }

    public function jsonSerialize(): array
    {
        if ($this->isBinary()) {
            return [$this->type => [$this->identifier() => $this->literal()]];
        } elseif ($this->isUnary()) {
            return [$this->type => $this->identifier()];
        } else {
            return [$this->type => $this->operands];
        }
    }

    public function __toString(): string
    {
        return $this->type;
    }
}
