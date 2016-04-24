<?php
/**
 * @author Andrey N. Mokhov aka Andi <github at mokhov.com>
 * @license https://opensource.org/licenses/mit-license.php MIT (see the LICENSE file)
 * @copyright Copyright (c) 2016 Andrey N. Mokhov
 * Date: 13.04.2016
 * Time: 0:44
 * Project: Migration for Zend Framework
 */

namespace Anelegan\Db\Migration;


use Anelegan\Db\Sql\Platform\Platform;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Ddl\Constraint\ForeignKey;
use Zend\Db\Sql\Ddl\Constraint\PrimaryKey;
use Zend\Db\Sql\Ddl\Constraint\UniqueKey;
use Zend\Db\Sql\Predicate\Literal;
use Zend\Db\Sql\SqlInterface;

final class InitMigration extends AbstractMigration
{
    protected $tableName = 'migration';
    protected $dependTableName = 'migration_depend';

    /**
     * @return string
     */
    public function getName()
    {
        return 'initialization';
    }

    /**
     * @param SqlInterface $query
     * @return void
     */
    public function execute(SqlInterface $query)
    {
        $platform = new Platform($this->adapter);
        $platform->setSubject($query);
        if (defined('ANELEGAN_DB_DEBUG') && ANELEGAN_DB_DEBUG) {
            $this->console->writeLine(PHP_EOL . $platform->getSqlString($this->adapter->getPlatform()) . PHP_EOL);
        } else {
            $this->adapter->query($platform->getSqlString(), Adapter::QUERY_MODE_EXECUTE);
        }
    }

    /**
     * @return bool
     */
    public function safeUp()
    {
        $this->execute(
            $this->createTable(
                $this->tableName,
                [
                    'id' => $this->primaryKey(),
                    'name' => $this->string(255)->setNullable(false)->addConstraint(new UniqueKey()),
                    'installation_date' => $this->dateTime()->setNullable(false)->setDefault(new Literal('now()'))->setOption('WITH TIME ZONE', true),
                ],
                ['comment' => 'History table of Migration']
            )
        );
        $this->execute(
            $this->createTable(
                $this->dependTableName,
                [
                    'child_id' => $this->integer()->setNullable(false),
                    'parent_id' => $this->integer()->setNullable(false),
                ],
                ['comment' => 'Migration depends']
            )
                ->addConstraint(new ForeignKey($this->dependTableName . '_child_id_fk', 'child_id', $this->tableName, 'id', 'CASCADE', 'CASCADE'))
                ->addConstraint(new ForeignKey($this->dependTableName . '_parent_id_fk', 'parent_id', $this->tableName, 'id', 'RESTRICT', 'CASCADE'))
                ->addConstraint(new PrimaryKey(['child_id', 'parent_id'], $this->dependTableName . '_child_id_parent_id_pk'))
        );
        return true;
    }
}
