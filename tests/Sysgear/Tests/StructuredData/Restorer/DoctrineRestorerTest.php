<?php

/*
 * This file is part of the Sysgear package.
 *
 * (c) Martijn Evers <mevers47@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sysgear\Tests\StructuredData\Restorer;

use Sysgear\Tests\StructuredData\TestCase;
use Sysgear\StructuredData\Restorer\DoctrineRestorer;
use Sysgear\StructuredData\NodeCollection;
use Sysgear\StructuredData\NodeProperty;
use Sysgear\StructuredData\Node;

class DoctrineRestorerTest extends TestCase
{
    public function testConstructor()
    {
        $restorer = new DoctrineRestorer();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage No entity manager given
     */
    public function testRestore_noEntityManager()
    {
        $node = new Node('dummy');

        $restorer = new DoctrineRestorer();
        $restorer->restore($node);
    }

    /**
     * Return an accessible method.
     *
     * @param object|string $obj object or classname
     * @param string $name method name
     * @return \ReflectionMethod
     */
    protected static function getMethod($obj, $name)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getMockEntityManager($persistCount = 0)
    {
        $em = $this->getMock('\Doctrine\ORM\EntityManager', array('persist'), array(), '', false);
        $em->expects($this->exactly($persistCount))->method('persist')->will($this->returnValue(null));
        return $em;
    }
}