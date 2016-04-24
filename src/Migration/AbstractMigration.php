<?php
/**
 * @author Andrey N. Mokhov aka Andi <github at mokhov.com>
 * @license https://opensource.org/licenses/mit-license.php MIT (see the LICENSE file)
 * @copyright Copyright (c) 2016 Andrey N. Mokhov
 * Date: 31.03.2016
 * Time: 1:35
 * Project: Migration for Zend Framework
 */

namespace Anelegan\Db\Migration;


use Anelegan\Db\Sql\Ddl\AlterTable;
use Anelegan\Db\Sql\Ddl\CreateTable;
use Anelegan\Db\Sql\Ddl\DropTable;
use Anelegan\Db\Sql\Ddl\TruncateTable;
use Anelegan\Db\Sql\Platform\Platform;
use Zend\Console\Adapter\AdapterInterface as ConsoleAdapter;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\AdapterAwareTrait;
use Zend\Db\Sql\Ddl\AlterTable as ZendAlterTable;
use Zend\Db\Sql\Ddl\CreateTable as ZendCreateTable;
use Zend\Db\Sql\Ddl\Column;
use Zend\Db\Sql\Ddl\Constraint\PrimaryKey;
use Zend\Db\Sql\Delete;
use Zend\Db\Sql\Exception\InvalidArgumentException;
use Zend\Db\Sql\Insert;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\SqlInterface;
use Zend\Db\Sql\Update;

abstract class AbstractMigration implements MigrationInterface
{
    use AdapterAwareTrait;

    /**
     * @var ConsoleAdapter
     */
    protected $console;

    /**
     * @return array
     */
    public function getDependencies()
    {
        return [];
    }

    /**
     * @param ConsoleAdapter $console
     * @return static
     */
    public function setConsole(ConsoleAdapter $console)
    {
        $this->console = $console;

        return $this;
    }

    /**
     * @return bool
     */
    protected function safeUp()
    {
        return true;
    }

    /**
     * @return bool
     */
    protected function safeDown()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function setUp()
    {
        $connection = $this->adapter->driver->getConnection();
        $connection->beginTransaction();
        try {
            if (false === ($result = $this->safeUp())) {
                $connection->rollback();
                return false;
            }
            $connection->commit();
            return $result;
        }
        catch (\Exception $e) {
            $this->console->writeLine(sprintf('Exception: %s (%s:%s)', $e->getMessage(), $e->getFile(), $e->getLine()));
            $this->console->writeLine($e->getTraceAsString());
            $connection->rollback();
            return false;
        }
    }

    /**
     * @return bool
     */
    public function tearDown()
    {
        $connection = $this->adapter->driver->getConnection();
        $connection->beginTransaction();
        try {
            if (false === ($result = $this->safeDown())) {
                $connection->rollback();
                return false;
            }
            $connection->commit();
            return $result;
        } catch (\Exception $e) {
            $this->console->writeLine(sprintf('Exception: %s (%s:%s)', $e->getMessage(), $e->getFile(), $e->getLine()));
            $this->console->writeLine($e->getTraceAsString());
            $connection->rollback();
            return false;
        }
    }

    /**
     * @param SqlInterface $query
     * @return void
     */
    public function execute(SqlInterface $query)
    {
        $time = microtime(true);
        $adapterPlatform = $this->adapter->getPlatform();
        if ($query instanceof ZendCreateTable) {
            $this->console->write('    > create table ' . $adapterPlatform->quoteIdentifier($query->getRawState(ZendCreateTable::TABLE)) . '...');
        } elseif ($query instanceof ZendAlterTable) {
            $this->console->write('    > alter table ' . $adapterPlatform->quoteIdentifier($query->getRawState(ZendAlterTable::TABLE)) . '...');
        } elseif ($query instanceof TruncateTable) {
            $this->console->write('    > truncate table ' . $adapterPlatform->quoteIdentifier($query->getRawState(TruncateTable::TABLE)) . '...');
        } elseif ($query instanceof DropTable) {
            $this->console->write('    > drop table ' . $adapterPlatform->quoteIdentifier($query->getRawState(DropTable::TABLE)) . '...');
        } elseif ($query instanceof Insert) {
            $this->console->write('    > insert into ' . $adapterPlatform->quoteIdentifier($query->getRawState('table')) . '...');
        } elseif ($query instanceof Update) {
            $this->console->write('    > update table ' . $adapterPlatform->quoteIdentifier($query->getRawState('table')) . '...');
        } elseif ($query instanceof Delete) {
            $this->console->write('    > delete from ' . $adapterPlatform->quoteIdentifier($query->getRawState('table')) . '...');
        } else {
            throw new InvalidArgumentException(sprintf('Parameter of a method %s must be a executable Sql statement that modifies a table.', __METHOD__));
        }

        $platform = new Platform($this->adapter);
        $platform->setSubject($query);
        if (defined('ANELEGAN_DB_DEBUG') && ANELEGAN_DB_DEBUG) {
            $this->console->write(PHP_EOL . $platform->getSqlString($this->adapter->getPlatform()) . PHP_EOL);
        } else {
            $this->adapter->query($platform->getSqlString(), Adapter::QUERY_MODE_EXECUTE);
        }
        $this->console->writeLine(' done (time: ' . sprintf('%.3f', microtime(true) - $time) . 's)');
    }

    /**
     * @param string $tableName
     * @param Column\Column[] $columns
     * @param array|null $options
     * @return CreateTable
     */
    public function createTable($tableName, array $columns, array $options = null)
    {
        $table = new CreateTable($tableName, is_array($options) && isset($options['temporary']));
        if (is_array($options)) {
            $table->setOptions($options);
        }
        foreach ($columns as $columnName => $column) {
            if (! is_int($columnName)) {
                $column->setName($columnName);
            }
            $table->addColumn($column);
        }
        return $table;
    }

    /**
     * @param string $tableName
     * @param Column\Column[] $addColumns
     * @param array $options
     * @return AlterTable
     */
    public function alterTable($tableName, array $addColumns = [], array $options = null)
    {
        $table = new AlterTable($tableName);
        if (is_array($options)) {
            $table->setOptions($options);
        }
        foreach ($addColumns as $columnName => $column) {
            if (! is_int($columnName)) {
                $column->setName($columnName);
            }
            $table->addColumn($column);
        }
        return $table;
    }

    /**
     * @param string $tableName
     * @param array|null $options
     * @return TruncateTable
     */
    public function truncateTable($tableName, array $options = null)
    {
        $table = new TruncateTable($tableName);
        if (is_array($options)) {
            $table->setOptions($options);
        }
        return $table;
    }

    /**
     * @param string $tableName
     * @param array $options
     * @return DropTable
     */
    public function dropTable($tableName, array $options = null)
    {
        $table = new DropTable($tableName);
        if (is_array($options)) {
            $table->setOptions($options);
        }
        return $table;
    }

    /**
     * @param $tableName
     * @param array $columns
     * @return Insert
     */
    public function insert($tableName, array $columns)
    {
        return (new Sql($this->adapter))->insert($tableName)->values($columns);
    }

    /**
     * @param $tableName
     * @param array $columns
     * @param null $condition
     * @return Update
     */
    public function update($tableName, array $columns, $condition = null)
    {
        $update = (new Sql($this->adapter))->update($tableName)->set($columns);
        if (null !== $condition) {
            $update->where($condition);
        }
        return $update;
    }

    /**
     * @param $tableName
     * @param null $condition
     * @return Delete
     */
    public function delete($tableName, $condition = null)
    {
        $delete = (new Sql($this->adapter))->delete($tableName);
        if (null !== $condition) {
            $delete->where($condition);
        }
        return $delete;
    }

    /**
     * @param integer|null $length
     * @return Column\Integer
     */
    public function primaryKey($length = null)
    {
        return $this->integer($length)->addConstraint(new PrimaryKey())->setOption('serial', true);
    }

    /**
     * @param integer|null $length
     * @return Column\BigInteger
     */
    public function bigPrimaryKey($length = null)
    {
        return $this->bigInteger($length)->addConstraint(new PrimaryKey())->setOption('serial', true);
    }

    /**
     * @param integer|null $length
     * @return Column\Integer
     */
    public function integer($length = null)
    {
        $column = new Column\Integer(null);
        if (null !== $length && intval($length)) {
            $column->setOption('length', (int) $length);
        }
        return $column;
    }

    /**
     * @param integer|null $length
     * @return Column\BigInteger
     */
    public function bigInteger($length = null)
    {
        $column = new Column\BigInteger(null);
        if (null !== $length && intval($length)) {
            $column->setOption('length', (int) $length);
        }
        return $column;
    }

    /**
     * @param integer|null $length
     * @return Column\Binary
     */
    public function binary($length = null)
    {
        return new Column\Binary(null, $length);
    }

    /**
     * @return Column\Boolean
     */
    public function boolean()
    {
        return new Column\Boolean(null);
    }

    /**
     * @return Column\Date
     */
    public function date()
    {
        return new Column\Date(null);
    }

    /**
     * @param integer|null $precision
     * @return Column\Datetime
     */
    public function dateTime($precision = null)
    {
        $column = new Column\Datetime(null);
        if (null !== $precision) {
            $column->setOption('precision', (int) $precision);
        }
        return $column;
    }

    /**
     * @param integer|null $digits
     * @param integer|null $decimal
     * @return Column\Decimal
     */
    public function decimal($digits = null, $decimal = null)
    {
        return new Column\Decimal(null, $digits, $decimal);
    }

    /**
     * @param integer|null $digits
     * @param integer|null $decimal
     * @return Column\Decimal
     */
    public function numeric($digits = null, $decimal = null)
    {
        return $this->decimal($digits, $decimal);
    }

    /**
     * @param integer|null $digits
     * @param integer|null $decimal
     * @return Column\Floating
     */
    public function float($digits = null, $decimal = null)
    {
        return new Column\Floating(null, $digits, $decimal);
    }

    /**
     * @param integer $length
     * @return Column\Varchar
     */
    public function string($length = 50)
    {
        return new Column\Varchar(null, $length);
    }

    /**
     * @param integer|null $length
     * @return Column\Text
     */
    public function text($length = null)
    {
        return new Column\Text(null, $length);
    }

    /**
     * @param integer|null $precision
     * @return Column\Time
     */
    public function time($precision = null)
    {
        $column = new Column\Time(null);

        if (null !== $precision) {
            $column->setOption('precision', (int) $precision);
        }

        return $column;
    }

    /**
     * @param integer|null $precision
     * @return Column\Timestamp
     */
    public function timestamp($precision = null)
    {
        $column = new Column\Timestamp(null);

        if (null !== $precision) {
            $column->setOption('precision', (int) $precision);
        }

        return $column;
    }
}