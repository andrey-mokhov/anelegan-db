<?php
/**
 * @author Andrey N. Mokhov aka Andi <github at mokhov.com>
 * @license https://opensource.org/licenses/mit-license.php MIT (see the LICENSE file)
 * @copyright Copyright (c) 2016 Andrey N. Mokhov
 * Date: 12.04.2016
 * Time: 22:37
 * Project: Migration for Zend Framework
 */

namespace Anelegan\Db\Migration;


interface MigrationInterface
{
    /**
     * @return array
     */
    public function getDependencies();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return bool
     */
    public function setUp();

    /**
     * @return bool
     */
    public function tearDown();
}
