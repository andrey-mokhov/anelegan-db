<?php
/**
 * @author Andrey N. Mokhov aka Andi <github at mokhov.com>
 * @license https://opensource.org/licenses/mit-license.php MIT (see the LICENSE file)
 * @copyright Copyright (c) 2016 Andrey N. Mokhov
 * Date: 07.04.2016
 * Time: 0:40
 * Project: Migration for Zend Framework
 */

namespace Anelegan\Db\Sql;


trait OptionAwareTrait
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @param array $options
     * @return static
     */
    public function setOptions(array $options = null)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;

        return $this;
    }
}