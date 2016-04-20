<?php
/**
 * @author Andrey N. Mokhov aka Andi <github at mokhov.com>
 * @license https://opensource.org/licenses/mit-license.php MIT (see the LICENSE file)
 * @copyright Copyright (c) 2016 Andrey N. Mokhov
 * Date: 30.03.2016
 * Time: 22:46
 * Project: Migration for Zend Framework
 */

namespace Anelegan\Db\Sql\Platform\PostgreSQL\Ddl;


use Zend\Db\Sql\Ddl\CreateTable;
use Zend\Db\Sql\Platform\PlatformDecoratorInterface;

class CreateTableDecorator extends CreateTable implements PlatformDecoratorInterface
{
    use ColumnProcessExpressionTrait;
    /**
     * @var CreateTable
     */
    protected $subject;

    /**
     * @param CreateTable $subject
     *
     * @return static
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }
}