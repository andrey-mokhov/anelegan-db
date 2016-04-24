<?php
/**
 * @author Andrey N. Mokhov aka Andi <github at mokhov.com>
 * @license https://opensource.org/licenses/mit-license.php MIT (see the LICENSE file)
 * @copyright Copyright (c) 2016 Andrey N. Mokhov
 * Date: 13.04.2016
 * Time: 0:41
 * Project: Migration for Zend Framework
 */

use Anelegan\Db\Migration\Controller\MigrationController;
use Anelegan\Db\Migration\Controller\MigrationControllerFactory;
use Anelegan\Db\Migration\MigrationPluginManager;
use Anelegan\Db\Migration\MigrationPluginManagerFactory;
use Anelegan\Db\Migration\Service\MigrationService;
use Anelegan\Db\Migration\Service\MigrationServiceFactory;

return [
    'console' => [
        'router' => [
            'routes' => [
                'migrate_action' => [
                    'type' => 'simple',
                    'options' => [
                        'route' => 'migrate [list|up|down]:subAction [--migration=] [--interactive=]',
                        'defaults' => [
                            'controller' => MigrationController::class,
                            'action' => 'choose',
                        ],
                    ],
                ],
            ],
        ],
    ],
    'controllers' => array(
        'factories' => [
            MigrationController::class => MigrationControllerFactory::class,
        ],
    ),
    'service_manager' => [
        'aliases' => [
            'MigrationPluginManager' => MigrationPluginManager::class,
            'MigrationService' => MigrationService::class,
        ],
        'factories' => [
            MigrationPluginManager::class => MigrationPluginManagerFactory::class,
            MigrationService::class => MigrationServiceFactory::class,
        ],
    ],
    'migration_manager' => [

    ],
];