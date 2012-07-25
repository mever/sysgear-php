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
        return $this->getMock($class, $methods, array(), '', false);
    }

    /**
     * Get property value.
     *
     * @param object $object
     * @param string $name
     * @return mixed
     */
    protected function getProp($object, $name)
    {
        $refProperty = new \ReflectionProperty($object, $name);
        $refProperty->setAccessible(true);
        return $refProperty->getValue($object);
    }

    /**
     * Set property value.
     *
     * @param object $object
     * @param string $name
     * @param mixed $value
     */
    protected function setProp($object, $name, $value)
    {
        $refProperty = new \ReflectionProperty($object, $name);
        $refProperty->setAccessible(true);
        return $refProperty->setValue($object, $value);
    }

    /**
     * Execute a protected or private method for unit testing.
     *
     * @param object $object
     * @param string $method
     * @param mixed $var...
     * @return mixed
     */
    protected function exec($object, $method)
    {
        $refMethod = new \ReflectionMethod($object, $method);
        $refMethod->setAccessible(true);
        $arguments = func_get_args();
        array_shift($arguments);
        array_shift($arguments);

        return $refMethod->invokeArgs($object, $arguments);
    }

    /**
     * Wrap a mock object into a serializable wrapper.
     *
     * @param \PHPUnit_Framework_MockObject_MockObject $mock
     * @return \Serializable
     */
    protected function getSerializableMock(\PHPUnit_Framework_MockObject_MockObject $mock, array $interfaces = null)
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
                "\treturn call_user_func_array(array(\$this->mock, __FUNCTION__), \$arguments);\n}\n";
        }

        // build serializable class
        $className = 'Serialized_' . $mockedClassname;
        if (! class_exists($className)) {
            $class = "
            class {$className} implements " . join(', ', $interfaces) . " {\n".
                "protected \$mock;\n".
                "protected static \$instances = array();\n".
                "public function __construct(PHPUnit_Framework_MockObject_MockObject \$mock) {\n".
                    "\t\$this->mock = \$mock;\n".
                "}\npublic function serialize() {\n".
                    "\t\$id = (string) count(self::\$instances);\n".
                    "\tself::\$instances[\$id] = \$this->mock;\n".
                    "\treturn \$id;\n}".
                "\npublic function unserialize(\$id) {\n".
                    "\t\$this->mock = self::\$instances[\$id];\n".
                "}\npublic function __call(\$name, \$arguments) {\n".
                    "\treturn call_user_func_array(array(\$this->mock, \$name), \$arguments);\n".
                "}\n" . join('', $methods) . "\n".
            "}";

            eval($class);
        }

        return new $className($mock);
    }
}