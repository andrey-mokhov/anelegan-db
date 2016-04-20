<?php
/**
 * @author Andrey N. Mokhov aka Andi <github at mokhov.com>
 * @license https://opensource.org/licenses/mit-license.php MIT (see the LICENSE file)
 * @copyright Copyright (c) 2016 Andrey N. Mokhov
 * Date: 12.04.2016
 * Time: 22:28
 * Project: Migration for Zend Framework
 */

namespace Anelegan\Db\Migration;


use Interop\Container\ContainerInterface;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\ConfigInterface;
use Zend\ServiceManager\Exception;
use Zend\ServiceManager\Exception\InvalidServiceException;
use Zend\ServiceManager\Factory\InvokableFactory;

/**
 * Class MigrationPluginManager
 * @package Anelegan\Db
 *
 * @method MigrationInterface get($name, $options = [], $usePeeringServiceManagers = true)
 */
class MigrationPluginManager extends AbstractPluginManager
{
    protected $aliases = [
        'initialization' => InitMigration::class,
    ];

    protected $factories = [
        InitMigration::class => InvokableFactory::class,
    ];
    /**
     * An object type that the created instance must be instanced of
     *
     * @var string
     */
    protected $instanceOf = MigrationInterface::class;

    /**
     * Constructor
     *
     * Add a default initializer to ensure the plugin is valid after instance
     * creation.
     *
     * Additionally, the constructor provides forwards compatibility with v3 by
     * overloading the initial argument. v2 usage expects either null or a
     * ConfigInterface instance, and will ignore any other arguments. v3 expects
     * a ContainerInterface instance, and will use an array of configuration to
     * seed the current instance with services. In most cases, you can ignore the
     * constructor unless you are writing a specialized factory for your plugin
     * manager or overriding it.
     *
     * @param null|ConfigInterface|ContainerInterface $configOrContainerInstance
     * @param array $v3config If $configOrContainerInstance is a container, this
     *     value will be passed to the parent constructor.
     * @throws Exception\InvalidArgumentException if $configOrContainerInstance
     *     is neither null, nor a ConfigInterface, nor a ContainerInterface.
     */
    public function __construct($configOrContainerInstance = null, array $v3config = [])
    {
        if (empty($v3config) && $configOrContainerInstance instanceof ContainerInterface) {
            $config = $configOrContainerInstance->get('Config');
            if (isset($config['migration_manager'])) {
                $v3config = $config['migration_manager'];
            }
        }
        parent::__construct($configOrContainerInstance, $v3config);
        $this->addInitializer([$this, 'dbAdapterInitializer'], false);
        $this->addInitializer([$this, 'consoleAdapterInitializer'], false);
    }


    /**
     * {@inheritDoc}
     */
    public function validate($instance)
    {
        if ($instance instanceof $this->instanceOf) {
            return;
        }
        throw new InvalidServiceException(sprintf(
            'Plugin manager "%s" expected an instance of type "%s", but "%s" was received',
            __CLASS__,
            $this->instanceOf,
            is_object($instance) ? get_class($instance) : gettype($instance)
        ));
    }

    /**
     * Validate the plugin
     *
     * Checks that the filter loaded is either a valid callback or an instance
     * of FilterInterface.
     *
     * @param  mixed $plugin
     * @return void
     * @throws Exception\RuntimeException if invalid
     */
    public function validatePlugin($plugin)
    {
        $this->validate($plugin);
    }

    public function dbAdapterInitializer($migration)
    {
        if (method_exists($migration, 'setDbAdapter')) {
            $migration->setDbAdapter($this->serviceLocator->get('Zend\Db\Adapter\Adapter'));
        }
    }

    public function consoleAdapterInitializer($migration)
    {
        if (method_exists($migration, 'setConsole')) {
            $migration->setConsole($this->serviceLocator->get('Console'));
        }
    }

    /**
     * Canonicalize name
     *
     * @param  string $name
     * @return string
     */
    protected function canonicalizeName($name)
    {
        if (isset($this->canonicalNames[$name])) {
            return $this->canonicalNames[$name];
        }

        return $this->canonicalNames[$name] = $name;
    }
}
