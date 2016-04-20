<?php
/**
 * @author Andrey N. Mokhov aka Andi <github at mokhov.com>
 * @license https://opensource.org/licenses/mit-license.php MIT (see the LICENSE file)
 * @copyright Copyright (c) 2016 Andrey N. Mokhov
 * Date: 31.03.2016
 * Time: 3:08
 * Project: Migration for Zend Framework
 */

namespace Anelegan\Db\Sql\Ddl;

use Anelegan\Db\Sql\OptionAwareTrait;
use Zend\Db\Sql\Ddl\CreateTable as ZendCreateTable;

class CreateTable extends ZendCreateTable
{
    use OptionAwareTrait;
}