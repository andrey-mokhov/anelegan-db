<?php
/**
 * @author Andrey N. Mokhov aka Andi <github at mokhov.com>
 * @license https://opensource.org/licenses/mit-license.php MIT (see the LICENSE file)
 * @copyright Copyright (c) 2016 Andrey N. Mokhov
 * Date: 30.03.2016
 * Time: 22:43
 * Project: Migration for Zend Framework
 */

namespace Anelegan\Db\Sql\Platform\PostgreSQL;


use Zend\Db\Sql\Platform\AbstractPlatform;

class PostgreSQL extends AbstractPlatform
{
    public function __construct()
    {
        $this->setTypeDecorator('Zend\Db\Sql\Ddl\CreateTable', new Ddl\CreateTableDecorator());
        $this->setTypeDecorator('Anelegan\Db\Sql\Ddl\AlterTable', new Ddl\AlterTableDecorator());
        $this->setTypeDecorator('Anelegan\Db\Sql\Ddl\TruncateTable', new Ddl\TruncateTableDecorator());
    }
}
