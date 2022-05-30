<?php declare(strict_types=1);

namespace Biera\Filter;

use ArrayObject, BadMethodCallException, LogicException;

final class FilterExpressionBuilder
{
    private ?ArrayObject $currentNode = null;
    private bool $buildable = false;

    public static function create(): FilterExpressionBuilder
    {
        return new self();
    }

    public function true(): FilterExpressionBuilder
    {
        $this->assertCallableInCurrentContext(__FUNCTION__);
        $this->operator(Operator::TRUE);
        $this->end();

        return $this;
    }

    public function false(): FilterExpressionBuilder
    {
        $this->assertCallableInCurrentContext(__FUNCTION__);
        $this->operator(Operator::FALSE);
        $this->end();

        return $this;
    }

    public function null(string $identifier): FilterExpressionBuilder
    {
        $this->assertCallableInCurrentContext(__FUNCTION__);
        $this->operator(Operator::NULL, $identifier);
        $this->end();

        return $this;
    }

    public function notNull(string $identifier): FilterExpressionBuilder
    {
        $this->assertCallableInCurrentContext(__FUNCTION__);
        $this->operator(Operator::NOT_NULL, $identifier);
        $this->end();

        return $this;
    }

    public function and(): FilterExpressionBuilder
    {
        $this->assertCallableInCurrentContext(__FUNCTION__);
        $this->operator(Operator::AND);

        return $this;
    }

    public function or(): FilterExpressionBuilder
    {
        $this->assertCallableInCurrentContext(__FUNCTION__);
        $this->operator(Operator::OR);

        return $this;
    }

    public function lt(string $identifier, $value): FilterExpressionBuilder
    {
        $this->assertCallableInCurrentContext(__FUNCTION__);
        $this->operator(Operator::LT, $identifier, $value);
        $this->end();

        return $this;
    }

    public function lte(string $identifier, $value): FilterExpressionBuilder
    {
        $this->assertCallableInCurrentContext(__FUNCTION__);
        $this->operator(Operator::LTE, $identifier, $value);
        $this->end();

        return $this;
    }

    public function gt(string $identifier, $value): FilterExpressionBuilder
    {
        $this->assertCallableInCurrentContext(__FUNCTION__);
        $this->operator(Operator::GT, $identifier, $value);
        $this->end();

        return $this;
    }

    public function gte(string $identifier, $value): FilterExpressionBuilder
    {
        $this->assertCallableInCurrentContext(__FUNCTION__);
        $this->operator(Operator::GTE, $identifier, $value);
        $this->end();

        return $this;
    }

    public function in(string $identifier, array $value): FilterExpressionBuilder
    {
        $this->assertCallableInCurrentContext(__FUNCTION__);
        $this->operator(Operator::IN, $identifier, $value);
        $this->end();

        return $this;
    }

    public function notIn(string $identifier, array $value): FilterExpressionBuilder
    {
        $this->assertCallableInCurrentContext(__FUNCTION__);
        $this->operator(Operator::NOT_IN, $identifier, $value);
        $this->end();

        return $this;
    }

    public function like(string $identifier, string $value): FilterExpressionBuilder
    {
        $this->assertCallableInCurrentContext(__FUNCTION__);
        $this->operator(Operator::LIKE, $identifier, $value);
        $this->end();

        return $this;
    }

    public function eq(string $identifier, $value): FilterExpressionBuilder
    {
        $this->assertCallableInCurrentContext(__FUNCTION__);
        $this->operator(Operator::EQ, $identifier, $value);
        $this->end();

        return $this;
    }

    public function neq(string $identifier, $value): FilterExpressionBuilder
    {
        $this->assertCallableInCurrentContext(__FUNCTION__);
        $this->operator(Operator::NEQ, $identifier, $value);
        $this->end();

        return $this;
    }

    public function expression(Operator $expression): FilterExpressionBuilder
    {
        $this->assertCallableInCurrentContext(__FUNCTION__);
        $this->currentNode[$this->currentNode->expression][] = $expression;

        return $this;
    }

    public function end(): FilterExpressionBuilder
    {
        $this->assertCallableInCurrentContext(__FUNCTION__);
        // $currentNode is guaranteed not to be null
        // thanks to self::assertCallableInCurrentContext call
        if (!is_null($this->currentNode->parent)) {
            $this->currentNode = $this->currentNode->parent;
        } else {
            // the outermost operator operands list is closed:
            // mark as ready to call build method
            $this->buildable = true;
        }

        return $this;
    }

    public function build(): Operator
    {
        $this->assertCallableInCurrentContext(__FUNCTION__);

        $array = $this->toArray($this->currentNode);

        return FilterExpressionFactory::createFromArray(
            $array
        );
    }

    private function operator(string $operatorType, string $identifier = null, $value = null): void
    {
        if (Operator::TRUE == $operatorType || Operator::FALSE == $operatorType) {
            $node = new ArrayObject([$operatorType => null]);
        // "and" & "or" operators
        } else if (is_null($identifier)) {
            $node = new ArrayObject([$operatorType => new ArrayObject()]);
        } else {
            $node = is_null($value)
                // unary operators ("null" & "notNull")
                ? new ArrayObject([$operatorType => $identifier])
                // binary operators (e.g "eq", "notEq")
                : new ArrayObject([$operatorType => new ArrayObject([$identifier => $value])]);
        }

        // add $node as operand to operator in current context
        if ('root' !== $this->context()) {
            $this->currentNode[$this->currentNode->expression][] = $node;
        }

        $node->parent = $this->currentNode;
        $node->expression = $operatorType;
        $node->context = $operatorType;

        $this->currentNode = $node;
    }

    private function context(): string
    {
        return $this->buildable || is_null($this->currentNode) ? 'root' : $this->currentNode->context;
    }

    private function isCallableInCurrentContext(string $method): bool
    {
        $context = $this->context();

        switch ($method) {
            case Operator::AND:
            case Operator::OR:
            case Operator::TRUE:
            case Operator::FALSE:
            case Operator::NULL:
            case Operator::NOT_NULL:
            case Operator::LT:
            case Operator::LTE:
            case Operator::GT:
            case Operator::GTE:
            case Operator::IN:
            case Operator::NOT_IN:
            case Operator::EQ:
            case Operator::NEQ:
            case Operator::LIKE:
            case 'expression':
                return !$this->buildable && in_array($context, ['root', Operator::AND, Operator::OR]);

            case 'end' :
                if (
                    !in_array(
                        $context,
                        [
                            Operator::TRUE,
                            Operator::FALSE,
                            Operator::NULL,
                            Operator::NOT_NULL,
                            Operator::LT,
                            Operator::LTE,
                            Operator::GT,
                            Operator::GTE,
                            Operator::IN,
                            Operator::NOT_IN,
                            Operator::EQ,
                            Operator::NEQ,
                            Operator::LIKE,
                            Operator::AND,
                            Operator::OR
                        ]
                    )
                ) {
                    return false;
                }

                $operator = $context;

                // the current context is operator (e.g: "and"), assert operands count is valid
                // e.g: "and" operator requires at least two operands while "notNull" just one
                $operands = $this->currentNode[$operator];

                // "and"/"or" operator requires at least two operands
                if (in_array($operator, [Operator::AND, Operator::OR]) and count($operands) < 2) {
                    throw new BadMethodCallException(
                        sprintf(
                            "Method %s is not callable in current context: operator %s requires at least 2 operands, %d given",
                            $method,
                            $operator,
                            count($operands)
                        )
                    );
                }

                // other operators are not check, their operands correctness is guaranteed by
                // corresponding methods
                return true;

            case 'build':
                return $this->buildable;

            default :
                // this should never happen
                throw new LogicException("Method {$method} does not exist!");
        }
    }

    private function assertCallableInCurrentContext(string $method): void
    {
        if (!$this->isCallableInCurrentContext($method)) {
            throw new BadMethodCallException(
                "Method {$method} is not callable in current context."
            );
        }
    }

    private function toArray(ArrayObject $arrayObject): array
    {
        $array = $arrayObject->getArrayCopy();

        foreach ($array as $k => $v) {
            if ($v instanceof ArrayObject) {
                $array[$k] = $this->toArray($v);
            }
        }

        return $array;
    }
}
