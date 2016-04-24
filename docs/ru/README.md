Миграции для Zend Framework 2
=============================
Данный пакет позволяет организовать процесс миграций для решений, основанных на Zend Framework 2.

# Установка с помощью Composer
Установка пакета осуществляется следующей командой:
```
composer require andrey-mokhov/anelegan-db
```
После установки добавьте модуль в систему путем изменения в файле `config/application.config.php`
Добавьте подключение модуля:
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

# Разработка миграций
Пакет миграций поддерживает установку миграций, разработанных собственными силами и/или с использованием абстрактного класса `Anelegan\Db\Migration\AbstractMigration`.
## Разработка собственных миграций
Для создания миграций данных следует реализовать интерфейс `Anelegan\Db\Migration\MigrationInterface`, в вашем классе необходимо реализовать четыре метода, объявленных в миграции:
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
1. Метод `getDependencies` должен возвращать массив со списком зависимостей, т.е. со списком миграций (а если точнее, то их псевдонимы), предварительная установка которых требуется для вашей миграции.
2. Метод `getName` должен возвращать строку, соответствующую имени вашей миграции, также данная строка должна соответсвовать ключу в массиве `aliases` конфигурационного файла блока `migration_manager` (об этом немного позже).
3. Метод `setUp` должен выполнять действия по изменению структуры/данных в базе данных.
4. Метод `tearDown` должен выполнять действия, обратные установке - откатывать изменения в базе данных.

## Разработка миграций с использованием абстрактного класса
В пакете реализован абстрактный класс `Anelegan\Db\Migration\AbstractMigration` который позволяет минимизировать рутину при разработке миграций.
При наследовании абстратного класса необходимо реализовать следующие методы:
```php
<?php
namespace Application\Migration;

use Anelegan\Db\Migration\AbstractMigration;

class CreateTesting extends AbstractMigration
{
    /**
     * Если ваша миграция не зависит от других миграций, то данный
     * метод можно не определять, он уже реализован в абстрактном
     * классе.
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

В абстрактном классе определены два метода по установке миграции и два для её удаления.

### Методы установки миграции
1. Метод `setUp` вызывается вне транзакции.
2. Метод `safeUp` вызывается внутрии транзакции, предназначен для изменения структуры и/или данных.

> **Внимание!** Если в вашем классе миграции реализованы оба метода, то в методе `setUp` вы должен самостоятельно вызвать `parent::setUp`, в обратном случае метод `safeUp` не будет вызван.

> **Помните!** Не все СУБД при откате транзакции откатывают изменения структуры базы данных.

Мои рекомендации: все изменения в базе данных осуществляйте в методе `safeUp`.

### Методы удаления миграции
1. Метод `tearDown` вызывается вне транзакции.
1. Метод `safeDown` вызывается внутри транзакции, предназначен для отката изменений, выполненных методом `safeUp`.

> **Внимание!** Если в вашем классе миграции реализованы оба метода, то в методе `tearDown` вы должен самостоятельно вызвать `parent::tearDown`, в обратном случае метод `safeDown` не будет вызван.

> **Помните!** Не все СУБД при откате транзакции откатывают изменения структуры базы данных.

Мои рекомендации: откат изменений базы данных осуществляйте в методе `safeDown`.

### Пример миграции
Данный пример выполнен с использованием абстраткного класса.
Создайте файл `module/Application/src/Application/Migration/CreateTesting.php` со следующим содержимым: 
```php
<?php
namespace Application\Migration;

use Anelegan\Db\Migration\AbstractMigration;
use Zend\Db\Sql\Ddl\Constraint\UniqueKey;

class CreateTesting extends AbstractMigration
{
    /**
     * Возвращаем имя миграции
     *
     * @return string
     */
    public function getName()
    {
        return 'testing';
    }

    /**
     * Создаем таблицу testing
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
     * Удаляем таблицу testing
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

> **Внимание!** Метод `getName` должен возвращать псевдоним миграции, данная строка должна соответствовать ключу в блоке `aliases` в конфигурационном файле приложения блока `migration_manager` (см.ниже). 

# Установка миграций
Все разработанные миграции необходимо определить в конфигурационном файле.
## Конфигурирем миграции
Все миграции следует объявлять в блоке `migration_manager`, именно с ним работает `MigrationPluginManager`.
Для примера создайте файл `config/autoload/migration.local.php` следующего содержания:
```php
<?php
use Application\Migration\CreateTesting;

return [
    'migration_manager' => [
        'aliases' => [
            // здесь ключ должен равен строке результата вызова метода (new CreateTesting)->getName();
            'testing' => CreateTesting::class, 
        ],
        'factories' => [
            CreateTesting::class => InvokableFactory::class,
        ],
    ],
];
```

> **Внимание!** Ключи в массиве `aliases` (в нашем примере `testing`) должны соответствовать результатам вызова метода `getName` соответствующих классов миграций (см. пример миграции выше). 

### Просмотр доступных миграций системы
Откройте консоль, перейдите в корневую папку вашего приложения и выполните следующую команду:
```console
# php public/index.php migrate list
 Installed migration:
    none
 Available migrations:
    > testing
```
Система показала, что отсутствуют установленые миграции, а из доступных найдена миграция `testing`.

### Установка миграций
Для установки доступных миграций, откройте консоль и выполните следующую команду:
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

> **Внимание!** В данном примере вы можете обнаружить, что приложение установило миграцию `initialization`, данная миграция является системной и необходима для отслеживания существующих миграци, их зависимостей и т.п.

> **Помните!** При вызове консольной команды `migrate up` система устанавит все доступные миграции.
Для установки только одной миграции используйте ключ `--migration=migration_name`, при этом, если указанная миграция зависит от других, ещё не установленных миграций, они также будут установлены.

### Удаление установленных миграций
Для отката установленных миграций, откройте консоль и выполните следующую команду:
```console
# php public/index.php migrate down
 Installed migrations:
    > testing
 Uninstall "testing" migration? [y/n]
 -  Uninstall "testing" migration...
    > drop table "testing"... done (time: 0.061s)
    "testing" migration successfully uninstalled.
```

> **Внимание!** При вызове консольной команды `migrate down` откатываются все установленные миграции в порядке, обратном их установки. При откате каждой миграции система будет запрашивать подтверждение удаления.
Для того, чтобы откатить только одну конкретную миграцию воспользуйтесь ключем `--migration=migration_name`, но помните при этом, что если от удаляемой миграции зависят какие-то другие, они также будут предварительно удалены.

# Поддерживаемые СУБД
В настоящий момент реализована поддержка следующих СУБД:

- MySQL
- PostgreSQL

Было осуществленно внутреннее тестирование миграции для перечисленных СУБД.
В будущем возможна реализация поддержки других СУБД.