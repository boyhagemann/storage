# Immutable Data Store

> This is just a proof of concept! Do not use this in production because of its highly experimental nature.

#### Drivers
- Mysql

## Entity

```php
// Do an initial setup
$pdo = new PDO( ... );
$entity = new MysqlEntity($pdo);

// 
```

## Record

```php
// Do an initial setup
$pdo = new PDO( ... );
$entity = new MysqlEntity($pdo);
$storage = new MysqlRecord($pdo);

$resource = $entity->get('My resource');

```