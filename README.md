Migration for Zend Framework
============================
This package allows you to organize DB migration for solutions based on ZF2.

Sorry for my English. The Russian-language documentation can be found at the [following link](ru/README.md).

# Installation using Composer
Installation package by command:
```console
composer require andrey-mokhov/anelegan-db
```
After package installation add module in `config/application.config.php`
```php
<?php
return [
    // This should be an array of module namespaces used in the application.
    'modules' => [
        'Anelegan\Db', // <- added migration module
        // ... and yours modules
    ],
    // ... other settings
];
```

# Development of migration
Migration module supported migration with implemented interface `Anelegan\Db\Migration\MigrationInterface`.

## Development self migration
Your class must implemented interface `Anelegan\Db\Migration\MigrationInterface`:

```php
<?php
namespace Anelegan\Db\Migration;

interface MigrationInterface
{
    /**
     * @return array
     */
    public function getDependencies();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return bool
     */
    public function setUp();

    /**
     * @return bool
     */
    public function tearDown();
}
```

1. method `getDependencies` must return list with names of dependent migrations.
2. method `getName` must return migration name equal key of array `aliases` in `migration_manager` your configuration.
3. method `setUp` must change scheme and/or data in your database.
4. method `tearDown` must rollback changes of method `setUp`.

# Development migration with AbstractMigration
In Migration package you can found `Anelegan\Db\Migration\AbstractMigration`. This abstract class allows you to facilitate the development of migration.
When inheriting this abstract class you must implement next methods:

```php
<?php
namespace Application\Migration;

use Anelegan\Db\Migration\AbstractMigration;

class CreateTesting extends AbstractMigration
{
    /**
     * If your migration does not depend on other migrations, this
     * method can not determine, he has implemented in the abstract
     * class.
     * 
     * @return array
     */
    public function getDependencies()
    {
        return [];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'testing';
    }
}
```

Abstract class also have two methods:

- method `safeUp` - called by `setUp` after start transaction. Method can change your database.
- method `safeDown` - called by `tearDown` after start transaction. Method must rollback change of method `safeUp`.

### Migration example
Create file `module/Application/src/Application/Migration/CreateTesting.php` with the following contents:
```php
<?php
namespace Application\Migration;

use Anelegan\Db\Migration\AbstractMigration;
use Zend\Db\Sql\Ddl\Constraint\UniqueKey;

class CreateTesting extends AbstractMigration
{
    /**
     * Return migration name
     *
     * @return string
     */
    public function getName()
    {
        return 'testing';
    }

    /**
     * Create table with testing name
     *
     * @return bool
     */
    protected function safeUp()
    {
        $tableDefinition = $this->createTable('testing', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->setNullable(false)->addConstraint(new UniqueKey()),
            'description' => $this->text(),
        ], ['comment' => 'Test creating table']);
        $this->execute($tableDefinition);

        return true;
    }

    /**
     * Drop table with testing name
     *
     * @return bool
     */
    protected function safeDown()
    {
        $dropTable = $this->dropTable('testing');
        $this->execute($dropTable);

        return true;
    }
}
```

# Installing migration
All migration must defined in configure file in `migration_manager` section.

## Configure
For example create file `config/autoload/migration.local.php` with the following contents:
```php
<?php
use Application\Migration\CreateTesting;

return [
    'migration_manager' => [
        'aliases' => [
            // this key "testing" must match result of calling method (new CreateTesting)->getName();
            'testing' => CreateTesting::class, 
        ],
        'factories' => [
            CreateTesting::class => InvokableFactory::class,
        ],
    ],
];
```

## View list available migrations
Open shell and execute

```console
# php public/index.php migrate list
 Installed migration:
    none
 Available migrations:
    > testing
```

## Install migrations

To install available migration, execute

```console
# php public/index.php migrate up
 Available migrations:
    > testing
 Install available migration? [y/n] y
 +  Installing "initialization" migration...
    "initialization" migration successfully installed.
 +  Installing "testing" migration...
    > create table "testing"... done (time: 0.265s)
    "testing" migration successfully installed.
```

> "Initialization" migration install in system by default. This migration allows you to control the installed migrations and their dependencies.

## Remove installed migration

To remove installed migrations, execute

```console
# php public/index.php migrate down
 Installed migrations:
    > testing
 Uninstall "testing" migration? [y/n]
 -  Uninstall "testing" migration...
    > drop table "testing"... done (time: 0.061s)
    "testing" migration successfully uninstalled.
```

# Supported database system
Current version supports:

- MySQL
- PostgreSQL