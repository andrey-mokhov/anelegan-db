<?php
/**
 * @author Andrey N. Mokhov aka Andi <github at mokhov.com>
 * @license https://opensource.org/licenses/mit-license.php MIT (see the LICENSE file)
 * @copyright Copyright (c) 2016 Andrey N. Mokhov
 * Date: 31.03.2016
 * Time: 3:28
 * Project: Migration for Zend Framework
 */

namespace Anelegan\Db\Sql\Platform\Mysql;


use Anelegan\Db\Sql\Ddl\AlterTable;
use Anelegan\Db\Sql\Ddl\CreateTable;
use Zend\Db\Sql\Platform\Mysql\Mysql as ZendMysql;

class Mysql extends ZendMysql
{
    public function __construct()
    {
        $this->setTypeDecorator(AlterTable::class, new Ddl\AlterTableDecorator());
        $this->setTypeDecorator(CreateTable::class, new Ddl\CreateTableDecorator());
        parent::__construct();
    }
}