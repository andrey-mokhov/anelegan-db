<?php
/**
 * @author Andrey N. Mokhov aka Andi <github at mokhov.com>
 * @license https://opensource.org/licenses/mit-license.php MIT (see the LICENSE file)
 * @copyright Copyright (c) 2016 Andrey N. Mokhov
 * Date: 12.04.2016
 * Time: 2:52
 * Project: Migration for Zend Framework
 */

namespace Anelegan\Db\Sql\Ddl\Column;


use Zend\Db\Sql\ExpressionInterface;

class DefaultValue implements ExpressionInterface
{
    protected $specification = 'DEFAULT %s';

    protected $value = null;

    /**
     * PHP 5 allows developers to declare constructor methods for classes.
     * Classes which have a constructor method call this method on each newly-created object,
     * so it is suitable for any initialization that the object may need before it is used.
     *
     * Note: Parent constructors are not called implicitly if the child class defines a constructor.
     * In order to run a parent constructor, a call to parent::__construct() within the child constructor is required.
     *
     * param [ mixed $args [, $... ]]
     * @param mixed $value
     * @link http://php.net/manual/en/language.oop5.decon.php
     */
    function __construct($value = null)
    {
        $this->value = $value;
    }


    /**
     *
     * @return array of array|string should return an array in the format:
     *
     * array (
     *    // a sprintf formatted string
     *    string $specification,
     *
     *    // the values for the above sprintf formatted string
     *    array $values,
     *
     *    // an array of equal length of the $values array, with either TYPE_IDENTIFIER or TYPE_VALUE for each value
     *    array $types,
     * )
     *
     */
    public function getExpressionData()
    {
        return [[
            $this->specification,
            [$this->value],
            [self::TYPE_VALUE]
        ]];
    }

}