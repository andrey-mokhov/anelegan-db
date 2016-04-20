<?php
/**
 * @author Andrey N. Mokhov aka Andi <github at mokhov.com>
 * @license https://opensource.org/licenses/mit-license.php MIT (see the LICENSE file)
 * @copyright Copyright (c) 2016 Andrey N. Mokhov
 * Date: 07.04.2016
 * Time: 0:29
 * Project: Migration for Zend Framework
 */

namespace Anelegan\Db\Sql\Platform\PostgreSQL\Ddl;


use Anelegan\Db\Sql\Ddl\TruncateTable;
use Zend\Db\Adapter\Platform\PlatformInterface;
use Zend\Db\Sql\Platform\PlatformDecoratorInterface;

class TruncateTableDecorator extends TruncateTable implements PlatformDecoratorInterface
{
    /**
     * @var TruncateTable
     */
    protected $subject;

    /**
     * @param TruncateTable $subject
     *
     * @return static
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

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

        $chooseOptions = [];
        foreach ($options as $option => $value) {
            if (is_int($option)) {
                $option = $value;
            }
            switch ($this->normalizeCommandOption($option)) {
                case 'restart':
                case 'restartidentity':
                    if (! in_array('CONTINUE IDENTITY', $chooseOptions)) {
                        $chooseOptions[] = 'RESTART IDENTITY';
                    }
                    break;
                case 'continue':
                case 'continueidentity':
                    if (! in_array('RESTART IDENTITY', $chooseOptions)) {
                        $chooseOptions[] = 'CONTINUE IDENTITY';
                    }
                    break;
                case 'cascade':
                    if (! in_array('RESTRICT', $chooseOptions)) {
                        $chooseOptions[] = 'CASCADE';
                    }
                    break;
                case 'restrict':
                    if (! in_array('CASCADE', $chooseOptions)) {
                        $chooseOptions[] = 'RESTRICT';
                    }
                    break;
            }
        }

        $sql .= ' ' . implode(' ', $chooseOptions);

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
}