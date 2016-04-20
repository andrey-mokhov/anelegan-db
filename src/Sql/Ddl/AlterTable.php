<?php
/**
 * @author Andrey N. Mokhov aka Andi <github at mokhov.com>
 * @license https://opensource.org/licenses/mit-license.php MIT (see the LICENSE file)
 * @copyright Copyright (c) 2016 Andrey N. Mokhov
 * Date: 12.04.2016
 * Time: 0:50
 * Project: Migration for Zend Framework
 */

namespace Anelegan\Db\Sql\Ddl;

use Anelegan\Db\Sql\OptionAwareTrait;
use Zend\Db\Adapter\Platform\PlatformInterface;
use Zend\Db\Sql\Ddl\AlterTable as ZendAlterTable;
use Zend\Db\Sql\Ddl\Column;
use Zend\Db\Sql\Ddl\Constraint;

class AlterTable extends ZendAlterTable
{
    use OptionAwareTrait;

    const RENAME_COLUMN = 'renameColumn';

    /**
     * @var Column\ColumnInterface[]
     */
    protected $renameColumn = [];

    /**
     * Specifications for Sql String generation
     * @var array
     */
    protected $specifications = [
        self::TABLE => "ALTER TABLE %1\$s\n",
        self::RENAME_COLUMN => [
            '%1$s' => [
                [2 => "CHANGE COLUMN %1\$s %2\$s,\n", 'combinedby' => ""],
            ],
        ],
        self::ADD_COLUMNS  => [
            "%1\$s" => [
                [1 => "ADD COLUMN %1\$s,\n", 'combinedby' => ""]
            ]
        ],
        self::CHANGE_COLUMNS  => [
            "%1\$s" => [
                [2 => "CHANGE COLUMN %1\$s %2\$s,\n", 'combinedby' => "   "],
            ]
        ],
        self::DROP_COLUMNS  => [
            "%1\$s" => [
                [1 => "DROP COLUMN %1\$s,\n", 'combinedby' => ""],
            ]
        ],
        self::ADD_CONSTRAINTS  => [
            "%1\$s" => [
                [1 => "ADD %1\$s,\n", 'combinedby' => ""],
            ]
        ],
        self::DROP_CONSTRAINTS  => [
            "%1\$s" => [
                [1 => "DROP CONSTRAINT %1\$s,\n", 'combinedby' => ""],
            ]
        ]
    ];

    /**
     * @param $name
     * @param Column\ColumnInterface $column
     * @return static
     */
    public function renameColumn($name, Column\ColumnInterface $column)
    {
        if (!empty($this->renameColumn) || !empty($this->addColumns) || !empty($this->changeColumns) || !empty($this->dropColumns) || !empty($this->addConstraints) || !empty($this->dropConstraints)) {
            trigger_error('One statement must rename a column, other separate statements must change table definition.', E_USER_DEPRECATED);
        }
        $this->renameColumn[$name] = $column;
        $this->addColumns = [];
        $this->changeColumns = [];
        $this->dropColumns = [];
        $this->addConstraints = [];
        $this->dropConstraints = [];

        return $this;
    }

    /**
     * @param  Column\ColumnInterface $column
     * @return static
     */
    public function addColumn(Column\ColumnInterface $column)
    {
        if (!empty($this->renameColumns)) {
            trigger_error('One statement must rename a column, other separate statements must change table definition.', E_USER_DEPRECATED);
            return $this;
        }
        return parent::addColumn($column);
    }

    /**
     * @param  string $name
     * @param  Column\ColumnInterface $column
     * @return static
     */
    public function changeColumn($name, Column\ColumnInterface $column)
    {
        if (!empty($this->renameColumns) || $name !== $column->getName()) {
            trigger_error('One statement must rename a column, other separate statements must change table definition.', E_USER_DEPRECATED);
            return $this;
        }
        return parent::changeColumn($name, $column);
    }

    /**
     * @param  string $name
     * @return static
     */
    public function dropColumn($name)
    {
        if (!empty($this->renameColumns)) {
            trigger_error('One statement must rename a column, other separate statements must change table definition.', E_USER_DEPRECATED);
            return $this;
        }
        return parent::dropColumn($name);
    }

    /**
     * @param  Constraint\ConstraintInterface $constraint
     * @return static
     */
    public function addConstraint(Constraint\ConstraintInterface $constraint)
    {
        if (!empty($this->renameColumns)) {
            trigger_error('One statement must rename a column, other separate statements must change table definition.', E_USER_DEPRECATED);
            return $this;
        }
        return parent::addConstraint($constraint);
    }

    /**
     * @param  string $name
     * @return static
     */
    public function dropConstraint($name)
    {
        if (!empty($this->renameColumns)) {
            trigger_error('One statement must rename a column, other separate statements must change table definition.', E_USER_DEPRECATED);
            return $this;
        }
        return parent::dropConstraint($name);
    }

    protected function processRenameColumn(PlatformInterface $adapterPlatform = null)
    {
        $sqls = [];
        foreach ($this->renameColumn as $name => $column) {
            $sqls[] = [
                $adapterPlatform->quoteIdentifier($name),
                $this->processExpression($column, $adapterPlatform)
            ];
        }

        return [$sqls];
    }
}
