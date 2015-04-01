<?php

/*
 * This file is part of the Sysgear package.
*
* (c) Martijn Evers <mevers47@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/


namespace Sysgear\Test;

use Sysgear\Util;

class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Mock a class without calling its constructor.
     *
     * @param string $class
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    public function mock($class, array $methods = null)
    {
        if (null !== $methods) {
            foreach ($this->getAbstractMethodNames($class) as $name) {
                if (! in_array($name, $methods, true)) {
                    $methods[] = $name;
                }
            }
        }

        return $this->getMock($class, $methods, array(), '', false);
    }

    /**
     * Get property value.
     *
     * @param object $object
     * @param string $name
     * @param string $class Set this if you need to access private members
     * @return mixed
     */
    public function getProp($object, $name, $class = null)
    {
        if (null === $class) {
            if ($object instanceof \PHPUnit_Framework_MockObject_MockObject) {
                $class = get_parent_class($object);
            } else {
                $class = get_class($object);
            }
        }

        $refProperty = new \ReflectionProperty($class, $name);
        $refProperty->setAccessible(true);
        return $refProperty->getValue($object);
    }

    /**
     * Set property value.
     *
     * @param object $object
     * @param string $name
     * @param mixed $value
     * @param string $class Set this if you need to access private members
     */
    public function setProp($object, $name, $value, $class = null)
    {
        if (null === $class) {
            if ($object instanceof \PHPUnit_Framework_MockObject_MockObject) {
                $class = get_parent_class($object);
            } else {
                $class = get_class($object);
            }
        }

        $refProperty = new \ReflectionProperty($class, $name);
        $refProperty->setAccessible(true);
        return $refProperty->setValue($object, $value);
    }

    /**
     * Execute a protected or private method for unit testing.
     *
     * When executing a private method supply
     * the $method as an array: array("class_name", "method_name").
     *
     * @param object $object
     * @param string|array $method
     * @param mixed $var...
     * @return mixed
     */
    public function exec($object, $method)
    {
        if (is_array($method)) {
            list($class, $method) = $method;
        } else {
            $class = $object;
        }

        $refMethod = new \ReflectionMethod($class, $method);
        $refMethod->setAccessible(true);
        $arguments = func_get_args();
        array_shift($arguments);
        array_shift($arguments);

        return $refMethod->invokeArgs($object, $arguments);
    }

    /**
     * Wrap a mock object into a serializable wrapper.
     *
     * When no interfaces are specified use the SPL Serializable interface.
     *
     * @param \PHPUnit_Framework_MockObject_MockObject $mock
     * @param $interfaces array
     * @param $extends string FQCN (Fully-Qualified Class Name)
     * @return \Serializable
     */
    public function getSerializableMock(\PHPUnit_Framework_MockObject_MockObject $mock, array $interfaces = null, $extends = null)
    {
        $mockedClassname = get_class($mock);
        if (null === $interfaces) {
            $interfaces = array('\Serializable');
        }

        // build methods to satisfy interfaces
        $methods = array();
        $refClass = new \ReflectionClass($mock);
        foreach ($refClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {

            $name = $method->getName();
            if (in_array($name, array('serialize', 'unserialize'), true)) {
                continue;
            }

            // build a parameter list for $method
            $params = array();
            foreach ($method->getParameters() as $param) {
                list($type) = explode(' ', substr($param, 26), 2);
                $type = ('$' === $type[0]) ? '' : (('array' === $type) ? 'array ' : "\\$type ");
                $paramStr = "{$type}\${$param->getName()}";

                if ($param->isOptional()) {
                    $paramStr .= " = " . var_export($param->getDefaultValue(), true);
                }
                $params[$param->getPosition()] = $paramStr;
            }

            ksort($params);
            $methods[] = "public function {$name}(" . join(',', $params) . ") {\n" .
                "\t\$arguments = func_get_args();\n".
                "\treturn call_user_func_array(array(\$this->__mock, __FUNCTION__), \$arguments);\n}\n";
        }

        // build serializable class
        $className = 'Serialized_' . $mockedClassname;
        if (! class_exists($className)) {
            $extends = (null === $extends) ? '' : " extends {$extends}";
            $class = "
            class {$className}{$extends} implements " . join(', ', $interfaces) . " {\n".
                "public \$__mock;\n".
                "protected static \$instances = array();\n".
                "public function serialize() {\n".
                    "\t\$id = (string) count(self::\$instances);\n".
                    "\tself::\$instances[\$id] = \$this->__mock;\n".
                    "\treturn \$id;\n}".
                "\npublic function unserialize(\$id) {\n".
                    "\t\$this->__mock = self::\$instances[\$id];\n".
                "}\npublic function __call(\$name, \$arguments) {\n".
                    "\treturn call_user_func_array(array(\$this->__mock, \$name), \$arguments);\n".
                "}\n" . join('', $methods) . "\n".
            "}";

            eval($class);
        }

        $instance = Util::createInstanceWithoutConstructor($className);
        $instance->__mock = $mock;
        return $instance;
    }

    /**
     * Find abstract method names.
     *
     * @param string $class
     * @return string[]
     */
    protected function getAbstractMethodNames($class)
    {
        $names = array();
        $reflClass = new \ReflectionClass($class);
        foreach ($reflClass->getMethods(\ReflectionMethod::IS_ABSTRACT) as $method) {
            $names[] = $method->getName();
        }

        return $names;
    }
}