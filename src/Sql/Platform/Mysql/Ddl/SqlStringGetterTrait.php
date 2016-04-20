<?php
/**
 * @author Andrey N. Mokhov aka Andi <github at mokhov.com>
 * @license https://opensource.org/licenses/mit-license.php MIT (see the LICENSE file)
 * @copyright Copyright (c) 2016 Andrey N. Mokhov
 * Date: 12.04.2016
 * Time: 3:19
 * Project: Migration for Zend Framework
 */

namespace Anelegan\Db\Sql\Platform\Mysql\Ddl;


use Anelegan\Db\Sql\Ddl\AlterTable;
use Zend\Db\Adapter\Platform\PlatformInterface;

trait SqlStringGetterTrait
{
    /**
     * @param PlatformInterface|null $adapterPlatform
     * @return string
     */
    public function getSqlString(PlatformInterface $adapterPlatform = null)
    {
        $sql = parent::getSqlString($adapterPlatform);
        if (empty($options = $this->subject->getOptions())) {
            return $sql;
        }

        /* IDE warning fix */
        $alterTableClassName = AlterTable::class;
        if ($this instanceof $alterTableClassName && (!empty($this->renameColumn) || !empty($this->addColumns) || !empty($this->changeColumns) || !empty($this->dropColumns) || !empty($this->addConstraints) || !empty($this->dropConstraints))) {
            trigger_error('One statement must change a table options, other separate statements must change table definition.', E_USER_DEPRECATED);
            return $sql;
        }

        $defaultOptions = [
            'DEFAULT CHARACTER SET' => 'utf8',
            'DEFAULT COLLATE' => 'utf8_general_ci',
            'ENGINE' => 'InnoDB',
        ];
        foreach ($options as $option => $value) {
            switch ($this->normalizeTableOption($option)) {
                case 'charset':
                case 'defaultcharset':
                case 'characterset':
                case 'defaultcharacterset':
                    $defaultOptions['DEFAULT CHARACTER SET'] = $value;
                    break;
                case 'collate':
                case 'defaultcollate':
                    $defaultOptions['DEFAULT COLLATE'] = $value;
                    break;
                case 'engine':
                    $defaultOptions['ENGINE'] = $value;
                    break;
                case 'comment':
                    $defaultOptions['COMMENT'] = $adapterPlatform->quoteValue($value);
                    break;
            }
        }
        array_walk($defaultOptions, function (&$value, $key) {
            $value = $key . ' ' . $value;
        });

        $sql .= ' ' . implode(' ', $defaultOptions);

        return $sql;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function normalizeTableOption($name)
    {
        return strtolower(str_replace(['-', '_', ' '], '', $name));
    }
}