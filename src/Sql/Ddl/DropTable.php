<?php
/**
 * @author Andrey N. Mokhov aka Andi <github at mokhov.com>
 * @license https://opensource.org/licenses/mit-license.php MIT (see the LICENSE file)
 * @copyright Copyright (c) 2016 Andrey N. Mokhov
 * Date: 07.04.2016
 * Time: 1:08
 * Project: Migration for Zend Framework
 */

namespace Anelegan\Db\Sql\Ddl;

use Anelegan\Db\Sql\OptionAwareTrait;
use Zend\Db\Adapter\Platform\PlatformInterface;
use Zend\Db\Sql\Ddl\DropTable as ZendDropTable;

class DropTable extends ZendDropTable
{
    use OptionAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function getSqlString(PlatformInterface $adapterPlatform = null)
    {
        $sql = parent::getSqlString($adapterPlatform);

        if (empty($options = $this->getOptions())) {
            return $sql;
        }
        $hasCascadeRestrict = false;
        foreach ($options as $option => $value) {
            if (is_int($option)) {
                $option = $value;
            }
            switch ($this->normalizeCommandOption($option)) {
                case 'ifexists':
                    $sql = preg_replace('/^(DROP TABLE)/', '$1 IF EXISTS', $sql);
                    break;
                case 'cascade':
                    if (! $hasCascadeRestrict) {
                        $hasCascadeRestrict = true;
                        $sql .= ' CASCADE';
                    }
                    break;
                case 'restrict':
                    if (! $hasCascadeRestrict) {
                        $hasCascadeRestrict = true;
                        $sql .= ' RESTRICT';
                    }
                    break;
            }
        }
        return $sql;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function normalizeCommandOption($name)
    {
        return strtolower(str_replace(['-', '_', ' '], '', $name));
    }

    /**
     * @param  string|null $key
     * @return array
     */
    public function getRawState($key = null)
    {
        $rawState = [
            self::TABLE => $this->table,
        ];

        return (isset($key) && array_key_exists($key, $rawState)) ? $rawState[$key] : $rawState;
    }
}