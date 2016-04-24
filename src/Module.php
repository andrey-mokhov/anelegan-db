<?php
/**
 * @author Andrey N. Mokhov aka Andi <andrey at mokhov.com>
 * @license Proprietary Licenses
 * @copyright Copyright (c) 2016, Andrey N. Mokhov
 * Date: 24.04.2016
 * Time: 19:46
 * Project: zf2
 */

namespace Anelegan\Db;


use Zend\ModuleManager\Feature\ConfigProviderInterface;

class Module implements ConfigProviderInterface
{
    /**
     * Returns configuration to merge with application configuration
     *
     * @return array|\Traversable
     */
    public function getConfig()
    {
        return require __DIR__ . '/../config/module.config.php';
    }

}