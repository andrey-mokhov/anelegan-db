<?php
/**
 * @author Andrey N. Mokhov aka Andi <github at mokhov.com>
 * @license https://opensource.org/licenses/mit-license.php MIT (see the LICENSE file)
 * @copyright Copyright (c) 2016 Andrey N. Mokhov
 * Date: 31.03.2016
 * Time: 1:13
 * Project: Migration for Zend Framework
 */

namespace Anelegan\Db\Sql\Platform\PostgreSQL\Ddl;


use Anelegan\Db\Sql\Ddl\AlterTable;
use Anelegan\Db\Sql\Ddl\Column\DefaultValue;
use Zend\Db\Adapter\Platform\PlatformInterface;
use Zend\Db\Sql\Ddl\Column;
use Zend\Db\Sql\Platform\PlatformDecoratorInterface;

class AlterTableDecorator extends AlterTable implements PlatformDecoratorInterface
{
    use ColumnProcessExpressionTrait;

    /**
     * Specifications for Sql String generation
     * @var array
     */
    protected $specifications = [
        self::TABLE => "ALTER TABLE %1\$s\n",
        self::RENAME_COLUMN => [
            '%1$s' => [
                [2 => 'RENAME COLUMN %1$s TO %2$s', 'combinedby' => ""],
            ],
        ],
        self::ADD_COLUMNS  => [
            "%1\$s" => [
                [1 => "ADD COLUMN %1\$s,\n", 'combinedby' => ""]
            ]
        ],
        self::CHANGE_COLUMNS  => [
            "%1\$s" => [
                [2 => "ALTER COLUMN %1\$s%2\$s,\n", 'combinedby' => "   "],
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
     * @var AlterTable
     */
    protected $subject;

    /**
     * @param AlterTable $subject
     *
     * @return static
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    protected function processRenameColumn(PlatformInterface $adapterPlatform = null)
    {
        $sqls = [];
        foreach ($this->renameColumn as $name => $column) {
            $sqls[] = [
                $adapterPlatform->quoteIdentifier($name),
                $adapterPlatform->quoteIdentifier($column->getName())
            ];
            break;
        }

        return [$sqls];
    }

    protected function processChangeColumns(PlatformInterface $adapterPlatform = null)
    {
        /* @var Column\Column $column  */
        $sqls = [];
        foreach ($this->changeColumns as $name => $column) {
            if ($name !== $column->getName()) {
                trigger_error('One statement must rename a column, other separate statements must change table definition.', E_USER_DEPRECATED);
            }
            $default = $column->getDefault();
            $columnClass = get_class($column);
            $emptyColumn = new $columnClass(null, true);
            $emptyColumn->setOptions($column->getOptions());
            $sqls[] = [
                $adapterPlatform->quoteIdentifier($name),
                ' SET DATA TYPE' . $this->processExpression($emptyColumn, $adapterPlatform)
            ];
            $sqls[] = [
                $adapterPlatform->quoteIdentifier($name),
                null !== $default ? (' SET ' . $this->processExpression(new DefaultValue($default), $adapterPlatform)) : ' DROP DEFAULT'
            ];
            $sqls[] = [
                $adapterPlatform->quoteIdentifier($name),
                $column->isNullable() ? ' DROP NOT NULL' : ' SET NOT NULL'
            ];
        }

        return [$sqls];
    }


    /**
     * Copy variables from the subject into the local properties
     */
    protected function localizeVariables()
    {
        if (! $this instanceof PlatformDecoratorInterface) {
            return;
        }

        foreach (get_object_vars($this->subject) as $name => $value) {
            if ('specifications' === $name) {
                continue;
            }
            $this->{$name} = $value;
        }
    }

}
