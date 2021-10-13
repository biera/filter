<?php declare(strict_types=1);

namespace Biera\Filter;

interface Modifier
{
    public function modify(Operator $operator): Operator;
}
