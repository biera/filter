<?php declare(strict_types=1);

namespace Biera\Filter\Modifier;

use Biera\Filter\Modifier;
use Biera\Filter\Operator;

class ChainModifier implements Modifier
{
    /**
     * @var Modifier[]
     */
    protected array $modifiers = [];

    /**
     * @param Modifier[]
     */
    public function __construct(iterable $modifiers = [])
    {
        foreach ($modifiers as $modifier) {
            $this->appendModifier($modifier);
        }
    }

    public function appendModifier(Modifier $modifier): void
    {
        array_push($this->modifiers, $modifier);
    }

    public function prependModifier(Modifier $modifier): void
    {
        array_unshift($this->modifiers, $modifier);
    }

    public function modify(Operator $filterExpression): Operator
    {
        return array_reduce(
            $this->modifiers,
            function (Operator $filterExpression, Modifier $modifier) {
                return $modifier->modify($filterExpression);
            },
            $filterExpression
        );
    }
}
