<?php
require  __DIR__.'/../vendor/autoload.php';
require __DIR__.'/Entities/Product.php';
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Elshafey\DoctrineExtensions\WindowFunctions\Query\Mysql\Window;

// Create a simple "default" Doctrine ORM configuration for Annotations
$isDevMode = true;
$proxyDir = null;
$cache = null;
$useSimpleAnnotationReader = false;
$config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/Entities"), $isDevMode, $proxyDir, $cache, $useSimpleAnnotationReader);
// or if you prefer yaml or XML
//$config = Setup::createXMLMetadataConfiguration(array(__DIR__."/config/xml"), $isDevMode);
//$config = Setup::createYAMLMetadataConfiguration(array(__DIR__."/config/yaml"), $isDevMode);

// database configuration parameters
$conn = array(
    'driver' => 'pdo_sqlite',
    'path' => __DIR__ . '/db.sqlite',
);

// obtaining the entity manager
$entityManager = EntityManager::create($conn, $config);

$repo=$entityManager->getRepository('\Entities\Product');
$entityManager->getConfiguration()->addCustomStringFunction(
    'WINDOW',
    Window::class
);

// simple window function example
$q=$entityManager->createQueryBuilder()
->select('p')
->addSelect('WINDOW(ROW_NUMBER()) OVER() as rowNumber')
->from(\Entities\Product::class,'p')->getQuery();

echo 'Simple window function example: ';
echo $q->getSQL().PHP_EOL;
echo '-------------------------------------------------------------'.PHP_EOL;

//window function using partition by
$q=$entityManager->createQueryBuilder()
->select('p')
->addSelect('WINDOW(ROW_NUMBER()) OVER(PARTITION BY p.name) as rowNumber')
->from(\Entities\Product::class,'p')->getQuery();

echo 'Window function using partition by: ';
echo $q->getSQL().PHP_EOL;
echo '-------------------------------------------------------------'.PHP_EOL;

//window function using partition by and order by
$q=$entityManager->createQueryBuilder()
->select('p')
->addSelect('WINDOW(ROW_NUMBER()) OVER(PARTITION BY p.name ORDER BY p.id DESC) as rowNumber')
->from(\Entities\Product::class,'p')->getQuery();

echo 'Window function using partition by and order by: ';
echo $q->getSQL().PHP_EOL;