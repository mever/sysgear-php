<?php

namespace Sysgear\Symfony\Bundle\ServiceBundle\Adapter;

use Zend\Json\Server\Server;
use Zend\Json\Server\Error;
use Zend\Server\Reflection;
use Zend\Server\Method;

class Jsonrpc extends Server
{
    public $errorCallback;

    /**
     * Indicate fault response
     *
     * @param  string $fault
     * @param  int $code
     * @return false
     */
    public function fault($fault = null, $code = 404, $data = null)
    {
        if (is_callable($this->errorCallback)) {
            $error = call_user_func($this->errorCallback, $fault, $code, $data);
        } else {
            $error = new Error($fault, $code, $data);
        }

        $this->getResponse()->setError($error);
        return $error;
    }

    /**
     * Build a method signature
     *
     * @param  \Zend\Server\Reflection\AbstractFunction $reflection
     * @param  null|string|object $class
     * @return \Zend\Server\Method\Definition
     * @throws \Zend\Server\Exception on duplicate entry
     */
    protected function _buildSignature(Reflection\AbstractFunction $reflection, $class = null)
    {
        $ns         = $reflection->getNamespace();
        $name     = substr($reflection->getName(), 0, -6);    // Subtract Action postfix
        $method     = empty($ns) ? $name : $ns . '.' . $name;

        if (!$this->_overwriteExistingMethods && $this->_table->hasMethod($method)) {
            throw new Exception('Duplicate method registered: ' . $method);
        }

        $definition = new Method\Definition();
        $definition->setName($method)
                   ->setCallback($this->_buildCallback($reflection))
                   ->setMethodHelp($reflection->getDescription())
                   ->setInvokeArguments($reflection->getInvokeArguments());

        foreach ($reflection->getPrototypes() as $proto) {
            $prototype = new Method\Prototype();
            $prototype->setReturnType($this->_fixType($proto->getReturnType()));
            foreach ($proto->getParameters() as $parameter) {
                $param = new Method\Parameter(array(
                    'type'     => $this->_fixType($parameter->getType()),
                    'name'     => $parameter->getName(),
                    'optional' => $parameter->isOptional(),
                ));
                if ($parameter->isDefaultValueAvailable()) {
                    $param->setDefaultValue($parameter->getDefaultValue());
                }
                $prototype->addParameter($param);
            }
            $definition->addPrototype($prototype);
        }
        if (is_object($class)) {
            $definition->setObject($class);
        }
        $this->_table->addMethod($definition);
        return $definition;
    }

    /**
     * Register a class with the server
     *
     * @param  string $class
     * @param  string $namespace Ignored
     * @param  mixed $argv Ignored
     * @return Zend\JSON\Server
     */
    public function setClass($class, $namespace = '', $argv = null)
    {
        $argv = null;
        if (3 < func_num_args()) {
            $argv = func_get_args();
            $argv = array_slice($argv, 3);
        }

        $reflection = Reflection\Reflection::reflectClass($class, $argv, $namespace);
        foreach ($reflection->getMethods() as $method) {
            if ('Action' !== substr($method->getName(), -6)) {
                continue;
            }
            $definition = $this->_buildSignature($method, $class);
            $this->_addMethodServiceMap($definition);
        }
        return $this;
    }
}