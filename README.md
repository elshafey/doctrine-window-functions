# Window Functions Extension
Window functions extension is an extension that enable you to use SQL window functions easily inside doctrine.

## Installation 
`composer require elshafey/doctrine-window-functions`

## How To Use
```php
// configure the extension first
$entityManager->getConfiguration()->addCustomStringFunction(
    'WINDOW',
    \Elshafey\DoctrineExtensions\WindowFunctions\Query\Mysql\Window::class
);

// use your window function formula
$q=$entityManager->createQueryBuilder()
->select('p')
->addSelect('WINDOW(ROW_NUMBER()) OVER(PARTITION BY p.name) as rowNumber')
->from('\Entities\Product','p')->getQuery();
```

## Important Hint

Take care while using this extension and don't miss to wrap your **window function** by **WINDOW()**.
Examples:
- `WINDOW(COUNT(*)) OVER(PARTITION BY e.columnNamw)`
- `WINDOW(ROW_NUMBER()) OVER(PARTITION BY e.columnNamw)`

## Extension Compatibility

Currently this extension is tested and works fine with **MYSQL8**. Other platforms like Oracle, or MS-SQL Server is not tested.

## Missed Functionalities

The extension doesn't support yet `[frame_clause]`

## TODO

Add the capability of `[frame_clause]`