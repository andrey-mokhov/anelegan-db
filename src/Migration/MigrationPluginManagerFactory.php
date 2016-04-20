<?php
/**
 * @author Andrey N. Mokhov aka Andi <github at mokhov.com>
 * @license https://opensource.org/licenses/mit-license.php MIT (see the LICENSE file)
 * @copyright Copyright (c) 2016 Andrey N. Mokhov
 * Date: 12.04.2016
 * Time: 23:50
 * Project: Migration for Zend Framework
 */

namespace Anelegan\Db\Migration;

use Zend\Mvc\Service\AbstractPluginManagerFactory;

class MigrationPluginManagerFactory extends AbstractPluginManagerFactory
{
    const PLUGIN_MANAGER_CLASS = MigrationPluginManager::class;
}
