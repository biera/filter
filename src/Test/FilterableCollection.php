<?php declare(strict_types=1);

namespace Biera\Filter\Test;

use Biera\Filter\Operator as FilterExpression;

interface FilterableCollection
{
    public function findByFilters(FilterExpression $expression): array;
}
