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

        $restorer = new DoctrineRestorer(array('mergeMode' => DoctrineRestorer::MM_INSERT));
        $restorer->restore($node);
    }

    public function testGetRecord_attributeFields()
    {
        $metadata = $this->getMock('Doctrine\ORM\Mapping\ClassMetadata', array('getColumnName'));
        $metadata->expects($this->exactly(2))->method('getColumnName')->will($this->returnArgument(0));

        $restorer = $this->getMock('Sysgear\StructuredData\Restorer\DoctrineRestorer',
            array('getMetadata', 'processRecord'));
        $restorer->expects($this->once())->method('getMetadata')->will($this->returnValue($metadata));
        $restorer->expects($this->once())->method('processRecord');

        $node = new Node('object', 'user');
        $node->setProperty('name', new NodeProperty('string', 'jan'));
        $node->setProperty('age', new NodeProperty('integer', 24));
        $record = self::getMethod($restorer, 'createRecord')->invokeArgs($restorer, array($node));

        $this->assertEquals(array('name' => 'jan', 'age' => 24), $record);
    }

    public function testCreateRecord_owning_foreignFields()
    {
        $employerNode = new Node('object', 'company');
        $employerNode->setProperty('id', new NodeProperty('integer', 4));

        $mapping = array(
            'isOwningSide' => true, 'joinColumns' => array(
                array('name' => 'employer_id', 'referencedColumnName' => 'id')
            )
        );

        $metadata = $this->getMock('Doctrine\ORM\Mapping\ClassMetadata', array(
            'getAssociationMapping', 'getColumnName'));
        $metadata->expects($this->exactly(1))->method('getAssociationMapping')->will($this->returnValue($mapping));
        $metadata->expects($this->exactly(1))->method('getColumnName')->will($this->returnArgument(0));

        $restorer = $this->getMock('Sysgear\StructuredData\Restorer\DoctrineRestorer',
            array('getMetadata', 'processRecord'));
        $restorer->expects($this->exactly(2))->method('getMetadata')->will($this->returnValue($metadata));
        $restorer->expects($this->exactly(2))->method('processRecord');

        $node = new Node('object', 'user');
        $node->setProperty('employer', $employerNode);
        $record = self::getMethod($restorer, 'createRecord')->invokeArgs($restorer, array($node));

        $this->assertEquals(array('employer_id' => 4), $record);
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