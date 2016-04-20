<?php
/**
 * @author Andrey N. Mokhov aka Andi <github at mokhov.com>
 * @license https://opensource.org/licenses/mit-license.php MIT (see the LICENSE file)
 * @copyright Copyright (c) 2016 Andrey N. Mokhov
 * Date: 20.04.2016
 * Time: 3:10
 * Project: Migration for Zend Framework
 */

namespace Anelegan\Db\Migration\Controller;


use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MigrationControllerFactory implements FactoryInterface
{
    /**
     * The __invoke method is called when a script tries to call an object as a function.
     *
     * @param ContainerInterface $container
     * @param string $name
     * @param array $options
     * @return mixed
     */
    function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $instance = new MigrationController();
        return $instance->setMigrationService($container->getServiceLocator()->get('MigrationService'));
    }

    /**
     * Create service
     *
     * @param ServiceLocatorInterface $container
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, MigrationController::class);
    }

}
