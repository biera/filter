# biera/filter

This package features a tiny lib which aims to ease every web-app project's problem: filtering. It's storage agnostic (check available [bindings](#bindings)) and comes with a micro-language which helps to build a "filter expression": 

``` 
{
    "and": [
        {
            "lte": {"releaseDate": "2000-12-31"}
        },
        {
            "in": {"genre": ["biography", "drama"]}
        }
    ]
}
```
It should be pretty self-explanatory (if not, it reads *all drama and biography movies released in XX century*).

## usage
Filter expression may be built directly using `Biera\Filter\Operator` which has constructor methods for all supported operators:
```php
use Biera\Filter\Operator;

// all drama and biography movies released in XX century
$filterExpression = Operator::and(
    [
        Operator::lte('releaseDate', new DateTimeImmutable('2000-12-31')),
        Operator::in('genre', ['bigraphy', 'drama'])
    ]       
);

assert($filterExpression instanceof Operator);
```

For more complex expressions, `Biera\Filter\FilterExpressionBuilder` with its fluent API may be a better option:
```php
use Biera\Filter\Operator;
use Biera\Filter\FilterExpressionBuilder;

// all biography movies which are released before 2001-01-01 or last at least 120 minutes 
$filterExpression = FilterExpressionBuilder::create()
    ->and()
        ->eq('genre', 'biography')
        ->or()
            ->lt('releaseDate', new DateTimeImmutable('2001-01-01'))
            ->gte('duration', 120)
        ->end()
    ->end()
    ->build();

assert($filterExpression instanceof Operator);
 ```
However, typically the filter expression is provided by client application as JSON encoded string, use `Biera\Filter\FilterExpressionFactory` is such cases:

```php
use Biera\Filter\Operator;
use Biera\Filter\FilterExpressionFactory;

$filterExpression = FilterExpressionFactory::createFromJson($_GET['filters']);

assert($filterExpression instanceof Operator);
```


Alright, you have filter expression. What's next?

Usually, this lib is used together with one of existing [bindings](#bindings). For the purpose of demonstration let's use [doctrine ORM](https://github.com/biera/filter-doctrine-orm) binding to see it in action:
```php
use Doctrine\ORM\EntityRepository;
use Biera\Filter\Operator as FilterExpression;
use Biera\Filter\Binding\Doctrine\ORM\WhereClauseFactory;

class MovieRepository extends EntityRepository
{   
    public function findAllByFilters(FilterExpression $filters): array
    {                
        $whereClause = WhereClauseFactory::createFromFilterExpression($filters);
           
        return $this->createQueryBuilder('movie')
            ->where($whereClause)
            ->getQuery()
            ->getResult();
    }
}
```

as you can see, it merely helps with the where clause. The job of joining, grouping and perhaps ordering still must be done by you. 

## installation
This lib is distributed as composer package and may be installed by typing:
```
composer require biera/filter
```

However, it's pretty useless without storage-specific binding, so consider to `require` a specific binding instead (which depends on `biera/filter` package). 

## bindings
* [PDO](https://github.com/biera/filter-pdo)
* [doctrine ORM](https://github.com/biera/filter-doctrine-orm)
* mongo (work in progress)