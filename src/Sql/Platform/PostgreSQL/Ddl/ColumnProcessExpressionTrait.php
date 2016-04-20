<?php
/**
 * @author Andrey N. Mokhov aka Andi <github at mokhov.com>
 * @license https://opensource.org/licenses/mit-license.php MIT (see the LICENSE file)
 * @copyright Copyright (c) 2016 Andrey N. Mokhov
 * Date: 31.03.2016
 * Time: 1:02
 * Project: Migration for Zend Framework
 */

namespace Anelegan\Db\Sql\Platform\PostgreSQL\Ddl;

use Zend\Db\Adapter\Driver\DriverInterface;
use Zend\Db\Adapter\ParameterContainer;
use Zend\Db\Adapter\Platform\PlatformInterface;
use Zend\Db\Sql\Ddl\Column;
use Zend\Db\Sql\ExpressionInterface;

trait ColumnProcessExpressionTrait
{
    /**
     * @param string $name
     *
     * @return string
     */
    private function normalizeColumnOption($name)
    {
        return strtolower(str_replace(['-', '_', ' '], '', $name));
    }

    /**
     * @param ExpressionInterface $column
     * @param PlatformInterface $platform
     * @param DriverInterface|null $driver
     * @param ParameterContainer|null $parameterContainer
     * @param null $namedParameterPrefix
     * @return mixed
     */
    protected function processExpression(
        ExpressionInterface $column,
        PlatformInterface $platform,
        DriverInterface $driver = null,
        ParameterContainer $parameterContainer = null,
        $namedParameterPrefix = null
    )
    {
        if (! $column instanceof Column\Column) {
            return parent::processExpression($column, $platform, $driver, $parameterContainer, $namedParameterPrefix);
        }

        $columnOptions = $column->getOptions();
        switch ($columnClass = get_class($column)) {
            case Column\Timestamp::class:
                unset($columnOptions['on_update']);
                $column->setOptions($columnOptions);
                break;
            case Column\BigInteger::class:
            case Column\Integer::class:
                unset($columnOptions['length']);
                $column->setOptions($columnOptions);
                break;
        }
        $sql = parent::processExpression($column, $platform, $driver, $parameterContainer, $namedParameterPrefix);

        // renaming column data type
        switch ($columnClass) {
            case Column\Datetime::class:
                $sql = preg_replace('/DATETIME/', 'TIMESTAMP', $sql, 1);
                break;
            case Column\Binary::class:
            case Column\Blob::class:
            case Column\Varbinary::class:
                $sql = preg_replace('/(?:(?:VAR)?BINARY|BLOB)(?:\s*\(\d+\))?(\s*)/', 'BYTEA$1', $sql, 1);
                break;
            case Column\Decimal::class:
                $sql = preg_replace('/DECIMAL/', 'NUMERIC', $sql, 1);
                break;
            case Column\Float::class:
            case Column\Floating::class:
                $sql = preg_replace('/FLOAT(?:\s*\(\d+(?:,\d+)?\))?(\s*)/', 'REAL$1', $sql, 1);
                break;
            case Column\Text::class:
                $sql = preg_replace('/TEXT(?:\s*\(\d+\))?(\s*)/', 'TEXT$1', $sql, 1);
                break;
        }

        // setting options
        foreach ($columnOptions as $optionName => $optionValue) {
            switch ($this->normalizeColumnOption($optionName)) {
                case 'withtimezone':
                    $sql = preg_replace('/(\s*)TIME(STAMP)?(\s*\(\d+\))?(\s*)/', '$1TIME$2$3 WITH TIME ZONE$4', $sql, 1);
                    break;
                case 'length':
                case 'precision':
                    $sql = preg_replace('/(\s*)TIME(STAMP)?(\s*)/', '$1TIME$2(' . intval($optionValue) . ')$3', $sql, 1);
                    break;
                case 'identity':
                case 'serial':
                case 'autoincrement':
                    $sql = preg_replace('/(\s*)(BIG)?INT(?:EGER)?(?:\s*\(\d+\))?(\s*)/', '$1$2SERIAL$3', $sql, 1);
                    break;
            }
        }

        return $sql;
    }
}
