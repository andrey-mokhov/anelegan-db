<?php
/**
 * @author Andrey N. Mokhov aka Andi <github at mokhov.com>
 * @license https://opensource.org/licenses/mit-license.php MIT (see the LICENSE file)
 * @copyright Copyright (c) 2016 Andrey N. Mokhov
 * Date: 17.04.2016
 * Time: 23:00
 * Project: Migration for Zend Framework
 */

namespace Anelegan\Db\Migration\Service;


use Anelegan\Db\Migration\Exception\StopMigrationException;
use Anelegan\Db\Migration\MigrationInterface;
use Anelegan\Db\Migration\MigrationPluginManager;
use Zend\Console\Adapter\AdapterInterface as ConsoleAdapter;
use Zend\Console\ColorInterface;
use Zend\Console\Prompt\Confirm;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\AdapterAwareTrait;
use Zend\Db\Adapter\Exception\InvalidQueryException;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;

class MigrationService
{
    use AdapterAwareTrait;

    /*
     * @var bool
     */
    protected $migrationTableCreated;

    protected $migrationTable = 'migration';

    protected $dependsTable = 'migration_depend';

    protected $installedMigrations = [];

    protected $availableMigrations = [];

    /**
     * @var ConsoleAdapter
     */
    protected $console;

    /**
     * @var string
     */
    private $sequenceName;

    /**
     * @var MigrationPluginManager
     */
    protected $migrationPluginManager;

    public function setMigrationPluginManager(MigrationPluginManager $pluginManager)
    {
        $this->migrationPluginManager = $pluginManager;

        return $this;
    }

    /**
     * @return MigrationPluginManager
     */
    public function getMigrationPluginManager()
    {
        return $this->migrationPluginManager;
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

    public function getInstalledMigrations()
    {
        if (!empty($this->installedMigrations)) {
            return $this->installedMigrations;
        }
        try {
            $sql = new Sql($this->adapter);
            $query = $sql
                ->select($this->migrationTable)
                ->where(new Expression('name <> ?', ['initialization']))
                ->order(['installation_date' => Select::ORDER_ASCENDING]);
            $statement = $sql->prepareStatementForSqlObject($query);
            foreach ($statement->execute() as $migration) {
                $instance = $this->migrationPluginManager->get($migration['name']);
                $this->installedMigrations[$migration['name']] = [
                    'id' => $migration['id'],
                    'name' => $migration['name'],
                    'depends' => $instance->getDependencies(),
                    'instance' => $instance,
                ];
            }
            $this->migrationTableCreated = true;
        } catch (InvalidQueryException $e) {
            $this->migrationTableCreated = false;
        }
        return $this->installedMigrations;
    }

    public function getAvailableMigrations()
    {
        if (!empty($this->availableMigrations)) {
            return $this->availableMigrations;
        }
        $installed = array_keys($this->getInstalledMigrations());
        $registeredServices = $this->migrationPluginManager->getRegisteredServices();
        if (isset($registeredServices['aliases'])) {
            $this->pushAvailableMigration($registeredServices['aliases']);
        }
        if (isset($registeredServices['invokableClasses'])) {
            $this->pushAvailableMigration($registeredServices['invokableClasses']);
        }
        if (isset($registeredServices['factories'])) {
            $this->pushAvailableMigration($registeredServices['factories']);
        }
        unset($this->availableMigrations['initialization']);

        uasort($this->availableMigrations, function ($arr1, $arr2) use ($installed) {
            $cnt1 = count(array_diff($arr1['depends'], $installed));
            $cnt2 = count(array_diff($arr2['depends'], $installed));
            if ($cnt1 < $cnt2) {
                return -1;
            } elseif ($cnt1 == $cnt2) {
                return 0;
            }
            return 1;
        });
        return $this->availableMigrations;
    }

    protected function pushAvailableMigration($registeredServices)
    {
        if (!is_array($registeredServices) && !$registeredServices instanceof \Traversable) {
            return;
        }

        foreach ($registeredServices as $instanceName) {
            if (!$this->migrationPluginManager->has($instanceName)) {
                continue;
            }
            try {
                $instance = $this->migrationPluginManager->get($instanceName);
            } catch (ServiceNotCreatedException $e) {
                continue;
            }
            $name = $instance->getName();
            if (!isset($this->installedMigrations[$name]) && !isset($this->availableMigrations[$name])) {
                $this->availableMigrations[$name] = [
                    'name' => $name,
                    'depends' => $instance->getDependencies(),
                    'instance' => $instance,
                ];
            }
        }
    }

    /**
     * @param string $migrationName
     * @param bool $interactive
     * @return int
     * @throws StopMigrationException
     */
    public function installMigration($migrationName, $interactive = false)
    {
        if ($interactive && !Confirm::prompt(sprintf(' Install "%s" migration? [y/n] ', $migrationName))) {
            throw new StopMigrationException(' Migration process canceled by user.');
        }
        if (!$this->migrationTableCreated) {
            $this->runMigration($this->migrationPluginManager->get('initialization'));
            $this->migrationTableCreated = true;
        }

        if (isset($this->installedMigrations[$migrationName])) {
            return $this->installedMigrations[$migrationName]['id'];
        }

        $instance = $this->migrationPluginManager->get($migrationName);
        $dependIds = array_map([$this, __FUNCTION__], $instance->getDependencies());

        return $this->runMigration($instance, $dependIds);
    }

    /**
     * @param MigrationInterface $migration
     * @param array $dependIds
     * @return int|null
     * @throws \Exception
     */
    protected function runMigration(MigrationInterface $migration, array $dependIds = [])
    {
        try {
            $this->console->writeLine(sprintf(' +  Installing "%s" migration...', $migration->getName()), ColorInterface::YELLOW);
            $migration->setUp();
            $migrationId = $this->rememberMigration($migration, $dependIds);
            $this->console->writeLine(sprintf('    "%s" migration successfully installed.', $migration->getName()), ColorInterface::GREEN);
            return $migrationId;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param MigrationInterface $migration
     * @param array $dependIds
     * @return int
     */
    protected function rememberMigration(MigrationInterface $migration, array $dependIds = [])
    {
        $name = $migration->getName();
        $sql = new Sql($this->adapter);
        $insert = $sql->insert($this->migrationTable)->values(['name' => $name]);
        $this->adapter->query($sql->buildSqlString($insert), Adapter::QUERY_MODE_EXECUTE);
        $lastInsertId = $this->adapter->getDriver()->getConnection()->getLastGeneratedValue($this->getSequenceName());
        if (!empty($dependIds)) {
            foreach ($dependIds as $dependId) {
                if ($dependId) {
                    $this->getDependsStatement()->execute([$lastInsertId, $dependId]);
                }
            }
        }
        $this->installedMigrations[$name] = [
            'id' => $lastInsertId,
            'name' => $name,
            'depends' => $migration->getDependencies(),
            'instance' => $migration,
        ];
        unset($this->availableMigrations[$name]);
        return $lastInsertId;
    }

    /**
     * @return \Zend\Db\Adapter\Driver\StatementInterface
     */
    private function getDependsStatement()
    {
        static $insertStatement = null;
        if (null !== $insertStatement) {
            return $insertStatement;
        }

        $sql = new Sql($this->adapter);
        $insert = $sql->insert($this->dependsTable)->values(['child_id' => new Expression('?'), 'parent_id' => new Expression('?')]);
        return $insertStatement = $sql->prepareStatementForSqlObject($insert);
    }
    /**
     * @return string
     */
    protected function getSequenceName()
    {
        if (null !== $this->sequenceName) {
            return $this->sequenceName;
        }
        $platform = $this->adapter->getPlatform();
        switch ($platform->getName()) {
            case 'PostgreSQL':
                $sql = new Sql($this->adapter);
                $select = $sql->select()->columns(['seq' => new Expression('pg_get_serial_sequence(?, ?)', [$this->migrationTable, 'id'])]);
                $result = $this->adapter->query($select->getSqlString($platform), Adapter::QUERY_MODE_EXECUTE);
                $row = $result->current();
                return $this->sequenceName = $row['seq'];

        }

        return null;
    }

    public function uninstallMigration($migrationName, $interactive = false)
    {
        if (!isset($this->installedMigrations[$migrationName])) {
            return;
        }
        if ($interactive && !Confirm::prompt(sprintf(' Uninstall "%s" migration? [y/n] ', $migrationName))) {
            throw new StopMigrationException(' Uninstalling migrations canceled by user.');
        }

        $sql = new Sql($this->adapter);
        $query = $sql
            ->select(['d' => $this->dependsTable])
            ->columns([])
            ->join(['m' => $this->migrationTable], 'm.id = d.child_id', ['name'])
            ->where(['d.parent_id' => $this->installedMigrations[$migrationName]['id']])
            ->order(['m.installation_date' => Select::ORDER_DESCENDING]);
        $statement = $sql->prepareStatementForSqlObject($query);
        $children = [];
        foreach ($statement->execute() as $migration) {
            $children[] = $migration['name'];
        }
        array_map([$this, __FUNCTION__], $children);

        $this->removeMigration($this->installedMigrations[$migrationName]['instance']);
    }

    protected function removeMigration(MigrationInterface $migration)
    {
        $this->console->writeLine(sprintf(' -  Uninstall "%s" migration...', $migration->getName()), ColorInterface::YELLOW);
        $migration->tearDown();
        $this->forgetMigration($migration);
        $this->console->writeLine(sprintf('    "%s" migration successfully uninstalled.', $migration->getName()), ColorInterface::CYAN);
    }

    protected function forgetMigration(MigrationInterface $migration)
    {
        $name = $migration->getName();

        $sql = new Sql($this->adapter);
        $delete = $sql->delete($this->migrationTable)->where(['name' => $name]);
        $this->adapter->query($sql->buildSqlString($delete), Adapter::QUERY_MODE_EXECUTE);

        $this->availableMigrations[$name] = $this->installedMigrations[$name];
        unset($this->installedMigrations[$name]);
    }
}