<?php
/**
 * @author Andrey N. Mokhov aka Andi <github at mokhov.com>
 * @license https://opensource.org/licenses/mit-license.php MIT (see the LICENSE file)
 * @copyright Copyright (c) 2016 Andrey N. Mokhov
 * Date: 28.03.2016
 * Time: 0:56
 * Project: andrey-mokhov/anelegan-db
 */

namespace Anelegan\Db\Sql\Platform;

use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Platform\Platform as ZendPlatform;

class Platform extends ZendPlatform {
    public function __construct(AdapterInterface $adapter)
    {
        parent::__construct($adapter);
        $this->decorators['mysql'] = (new Mysql\Mysql())->getDecorators();
        $this->decorators['postgresql'] = (new PostgreSQL\PostgreSQL())->getDecorators();
    }
}
