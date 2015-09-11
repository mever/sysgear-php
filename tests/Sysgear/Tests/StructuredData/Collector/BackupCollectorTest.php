<?php

namespace Sysgear\Tests\StructuredData\Collector;

use Sysgear\StructuredData\Collector\BackupCollector;
use Sysgear\StructuredData\Restorer\BackupRestorer;
use Sysgear\StructuredData\Node;
use Sysgear\StructuredData\NodePath;
use Sysgear\StructuredData\NodeProperty;
use Sysgear\StructuredData\NodeCollection;
use Sysgear\Backup\InventoryManager;
use Sysgear\Filter\Expression;
use Sysgear\Operator;

class BackupCollectorTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $collector = new BackupCollector();
        $this->assertNull($collector->getNode());
    }

    public function testFromObject_publicScalarProperties()
    {
        $className = $this->createClass(array(
            'public $number = 3',
            'public $string = \'abc\'',
            'public $null'), array('Sysgear\Backup\BackupableInterface'),
            $this->createClassBackupableInterface()
        );

        $object = new $className();
        $collector = new BackupCollector();
        $collector->fromObject($object);

        $node = $collector->getNode();
        $this->assertEquals('object', $node->getType());
        $this->assertEquals($this->getClassName($object), $node->getName());
        $this->assertInstanceOf('Sysgear\\StructuredData\\Node', $node);
        $this->assertEquals(array('class' => get_class($object)), $node->getMetadata());
        $props = $node->getProperties();
        $this->assertEquals(3, $props['number']->getValue());
        $this->assertEquals('abc', $props['string']->getValue());
    }

    public function testFromObject_protectedScalarProperties()
    {
        $className = $this->createClass(array(
            'protected $number = 3',
            'protected $string = \'abc\'',
            'protected $null'), array('Sysgear\Backup\BackupableInterface'),
            $this->createClassBackupableInterface()
        );

        $object = new $className();
        $collector = new BackupCollector();
        $collector->fromObject($object);

        $node = $collector->getNode();
        $this->assertEquals(array('class' => get_class($object)), $node->getMetadata());
        $props = $node->getProperties();
        $this->assertEquals(3, $props['number']->getValue());
        $this->assertEquals('abc', $props['string']->getValue());
    }

    public function testFromObject_privateScalarProperties()
    {
        $className = $this->createClass(array(
            'private $number = 3',
            'private $string = \'abc\'',
            'private $null'), array('Sysgear\Backup\BackupableInterface'),
            $this->createClassBackupableInterface()
        );

        $object = new $className();
        $collector = new BackupCollector();
        $collector->fromObject($object);

        $node = $collector->getNode();
        $this->assertEquals(array('class' => get_class($object)), $node->getMetadata());
        $props = $node->getProperties();
        $this->assertEquals(3, $props['number']->getValue());
        $this->assertEquals('abc', $props['string']->getValue());
    }

    public function testFromObject_nodeProperty()
    {
        $backupable = $this->createClass(array(
            'public $number = 123'), array('Sysgear\Backup\BackupableInterface'),
            $this->createClassBackupableInterface()
        );

        $className = $this->createClass(
            array('public $obj'), array('Sysgear\Backup\BackupableInterface'),
            $this->createClassBackupableInterface()
        );

        $object = new $className();
        $object->obj = new $backupable();

        $collector = new BackupCollector();
        $collector->fromObject($object);

        $props = $collector->getNode()->getProperties();
        $this->assertEquals(1, count($props));
        $props = $props['obj']->getProperties();
        $this->assertEquals('integer', $props['number']->getType());
        $this->assertEquals(123, $props['number']->getValue());
    }

    public function testFromObject_nodeCollectionProperty()
    {
        $backupable = $this->createClass(array(
            'public $number = 123'), array('Sysgear\Backup\BackupableInterface'),
            $this->createClassBackupableInterface()
        );

        $className = $this->createClass(
            array('public $col'), array('Sysgear\Backup\BackupableInterface'),
            $this->createClassBackupableInterface()
        );

        $object = new $className();
        $object->col = array(new $backupable(), new $backupable());

        $collector = new BackupCollector();
        $collector->fromObject($object);
        $props = $collector->getNode()->getProperties();
        $col = $props['col']->toArray();
        $this->assertEquals(2, count($col));
        $props = $col[1]->getProperties();
        $this->assertEquals('integer', $props['number']->getType());
        $this->assertEquals(123, $props['number']->getValue());
    }

    public function testFromObject_nodeCollectionPropertyRefs()
    {
        $backupable = $this->createClass(array(
            'public $number = 123'), array('Sysgear\Backup\BackupableInterface'),
            $this->createClassBackupableInterface()
        );

        $className = $this->createClass(
            array('public $col'), array('Sysgear\Backup\BackupableInterface'),
            $this->createClassBackupableInterface()
        );

        $item = new $backupable();

        $object = new $className();
        $object->col = array($item, $item);

        $collector = new BackupCollector();
        $collector->fromObject($object);
        $props = $collector->getNode()->getProperties();
        $col = $props['col']->toArray();

        $this->assertEquals(2, count($col));
        $props = $col[0]->getProperties();

        $this->assertEquals(123, $props['number']->getValue());
        $props['number']->setValue(456);

        $props = $col[1]->getProperties();
        $this->assertEquals('integer', $props['number']->getType());
        $this->assertEquals(456, $props['number']->getValue());

        $this->assertEquals($col[0], $col[1]);
    }

    public function testOption_onlyImplementer_disabled()
    {
        $supClassName = $this->createClass(
            array('public $col', 'public $col2 = "abc"'),
            array('Sysgear\Backup\BackupableInterface'),
            $this->createClassBackupableInterface()
        );

        $className = $this->createClass(array(
            'public $shouldBeIgnored1 = true',
            'protected $shouldBeIgnored2 = true'
        ), array(), array(), $supClassName);

        $object = new $className();
        $collector = new BackupCollector();
        $node = $collector->fromObject($object);

        $this->assertEquals(get_class($object), __CLASS__ . '\\' . $node->getName());
        $this->assertEquals(get_class($object), $node->getMeta('class'));
        $props = $node->getProperties();
        $this->assertEquals(3, count($props));
        $this->assertTrue(array_key_exists('shouldBeIgnored1', $props));
        $this->assertEquals('boolean', $props['shouldBeIgnored2']->getType());
        $this->assertTrue($props['shouldBeIgnored2']->getValue());
        $this->assertEquals('string', $props['col2']->getType());
        $this->assertEquals('abc', $props['col2']->getValue());
    }

    public function testOption_onlyImplementer_enabled()
    {
        $supClassName = $this->createClass(
            array('public $col', 'public $col2 = "abc"'),
            array('Sysgear\Backup\BackupableInterface'),
            $this->createClassBackupableInterface()
        );

        $className = $this->createClass(array(
            'public $shouldBeIgnored1 = true',
            'protected $shouldBeIgnored2 = true'
        ), array(), array(), $supClassName);

        $object = new $className();
        $collector = new BackupCollector(array('onlyImplementer' => true));
        $node = $collector->fromObject($object);

        $this->assertEquals(get_parent_class($object), __CLASS__ . '\\' . $node->getName());
        $this->assertEquals(get_parent_class($object), $node->getMeta('class'));
        $props = $node->getProperties();
        $this->assertEquals(1, count($props));
        $this->assertEquals('string', $props['col2']->getType());
        $this->assertEquals('abc', $props['col2']->getValue());
    }

    public function testOption_merge()
    {
        $className = $this->createClass(array('public $a', 'public $b = true', 'public $c = 123'),
            array('Sysgear\Backup\BackupableInterface'),
            $this->createClassBackupableInterface(array('mergeFields' => array('b'))));

        $object = new $className();
        $object->a = new $className();
        $collector = new BackupCollector(array('merge' => array('a')));
        $node = $collector->fromObject($object);

        $this->assertEquals('[' . BackupCollector::MERGE_FLAG . ',"a"]', $node->getMeta('merge'));

        $props = $node->getProperty('a')->getProperties();
        $this->assertCount(2, $props);
        $this->assertTrue($props['b']->getValue());
        $this->assertEquals(123, $props['c']->getValue());
    }

    public function testOption_mergeOnly()
    {
        $className = $this->createClass(array('public $a', 'public $b = true', 'public $c = 123'),
            array('Sysgear\Backup\BackupableInterface'),
            $this->createClassBackupableInterface(array('mergeFields' => array('b'))));

        $object = new $className();
        $object->a = new $className();
        $collector = new BackupCollector(array('mergeOnly' => array('a')));
        $node = $collector->fromObject($object);

        $this->assertEquals('[' . BackupCollector::MERGE_ONLY . ',"a"]', $node->getMeta('merge'));

        $props = $node->getProperty('a')->getProperties();
        $this->assertCount(1, $props);
        $this->assertTrue(reset($props)->getValue());
    }

    public function testOption_inventoryManager_disabled()
    {
        // build object graph
        $className = $this->createClass(array(
            'public $number = 3',
            'public $employer',
            'public $string = \'abc\'',
            'public $null'), array('Sysgear\Backup\BackupableInterface'),
            $this->createClassBackupableInterface()
        );

        $classNameComp = $this->createClass(array(
            'public $name = \'hello world\''
            ), array('Sysgear\Backup\BackupableInterface'),
            $this->createClassBackupableInterface()
        );

        $object = new $className();
        $object->employer = new $classNameComp();

        // collect!
        $collector = new BackupCollector();
        $collector->fromObject($object);

        // assert
        $node = $collector->getNode();

        $employerNode = new Node('object', $this->getClassName($object->employer));
        $employerNode->setMetadata('class', __CLASS__ . '\\' . $this->getClassName($object->employer));
        $employerNode->setProperty('name', new NodeProperty('string', 'hello world'));

        $expectedNode = new Node('object', $this->getClassName($object));
        $expectedNode->setMetadata('class', __CLASS__ . '\\' . $this->getClassName($object));
        $expectedNode->setProperty('number', new NodeProperty('integer', 3));
        $expectedNode->setProperty('employer', $employerNode);
        $expectedNode->setProperty('string', new NodeProperty('string', 'abc'));

        $this->assertEquals($expectedNode, $node);
    }

    public function testOption_inventoryManager_excludeValue()
    {
        // build object graph
        $className = $this->createClass(array(
            'public $number = 3',
            'public $string = \'abc\'',
            'public $null'), array('Sysgear\Backup\BackupableInterface'),
            $this->createClassBackupableInterface()
        );

        $object = new $className();

        // build path to exclude
        $path = new NodePath();
        $path->add(NodePath::NODE, $this->getClassName($object))->add(NodePath::VALUE, 'string');

        // configure inventory manager
        $inventoryManager = new InventoryManager();
        $inventoryManager->getExcludeList()->add(new Expression((string) $path, null, Operator::NOT_EQUAL));

        // collect!
        $collector = new BackupCollector();
        $collector->setOption('inventoryManager', $inventoryManager);
        $collector->fromObject($object);

        // assert
        $node = $collector->getNode();

        $expectedNode = new Node('object', $this->getClassName($object));
        $expectedNode->setMetadata('class', __CLASS__ . '\\' . $this->getClassName($object));
        $expectedNode->setProperty('number', new NodeProperty('integer', 3));

        $this->assertEquals($expectedNode, $node);
    }

    public function testOption_inventoryManager_excludeNode()
    {
        // build object graph
        $className = $this->createClass(array(
            'public $number = 3',
            'public $employer',
            'public $string = \'abc\'',
            'public $null'), array('Sysgear\Backup\BackupableInterface'),
            $this->createClassBackupableInterface()
        );

        $classNameComp = $this->createClass(array(
            'public $name = \'hello world\''
            ), array('Sysgear\Backup\BackupableInterface'),
            $this->createClassBackupableInterface()
        );

        $object = new $className();
        $object->employer = new $classNameComp();

        // build path to exclude
        $path = new NodePath();
        $path->add(NodePath::NODE, $this->getClassName($object))->add(NodePath::NODE, 'employer');

        // configure inventory manager
        $inventoryManager = new InventoryManager();
        $inventoryManager->getExcludeList()->add(new Expression((string) $path, null));

        // collect!
        $collector = new BackupCollector();
        $collector->setOption('inventoryManager', $inventoryManager);
        $collector->fromObject($object);

        // assert
        $node = $collector->getNode();

        $expectedNode = new Node('object', $this->getClassName($object));
        $expectedNode->setMetadata('class', __CLASS__ . '\\' . $this->getClassName($object));
        $expectedNode->setProperty('number', new NodeProperty('integer', 3));
        $expectedNode->setProperty('string', new NodeProperty('string', 'abc'));

        $this->assertEquals($expectedNode, $node);
    }

    public function testOption_inventoryManager_excludeItemFromCollection()
    {
        // build object graph
        $className = $this->createClass(array(
            'public $number = 3',
            'public $members = array()',
            'public $string = \'abc\'',
            'public $null'), array('Sysgear\Backup\BackupableInterface'),
            $this->createClassBackupableInterface()
        );

        $classNameUser1 = $this->createClass(array(
            'public $name = \'hello world 1\''
            ), array('Sysgear\Backup\BackupableInterface'),
            $this->createClassBackupableInterface()
        );

        $classNameUser2 = $this->createClass(array(
            'public $name = \'hello world 2\''
            ), array('Sysgear\Backup\BackupableInterface'),
            $this->createClassBackupableInterface()
        );

        $object = new $className();
        $object->members[] = new $classNameUser1();
        $object->members[] = new $classNameUser2();

        // build path to exclude
        $path = new NodePath();
        $path->add(NodePath::NODE, $this->getClassName($object))->add(NodePath::COLLECTION, 'members')
            ->add(NodePath::NODE, $this->getClassName($object->members[1]), 1);

        // configure inventory manager
        $inventoryManager = new InventoryManager();
        $inventoryManager->getExcludeList()->add(new Expression((string) $path, null));

        // collect!
        $collector = new BackupCollector();
        $collector->setOption('inventoryManager', $inventoryManager);
        $collector->fromObject($object);

        // assert
        $node = $collector->getNode();

        $user1Node = new Node('object', $this->getClassName($object->members[0]));
        $user1Node->setMetadata('class', __CLASS__ . '\\' . $this->getClassName($object->members[0]));
        $user1Node->setMetadata('key', 'i;0');
        $user1Node->setProperty('name', new NodeProperty('string', 'hello world 1'));

        $expectedNode = new Node('object', $this->getClassName($object));
        $expectedNode->setMetadata('class', __CLASS__ . '\\' . $this->getClassName($object));
        $expectedNode->setProperty('number', new NodeProperty('integer', 3));
        $expectedNode->setProperty('members', new NodeCollection(array($user1Node)));
        $expectedNode->setProperty('string', new NodeProperty('string', 'abc'));

        $this->assertEquals($expectedNode, $node);
    }

    public function testOption_inventoryManager_excludeMixed()
    {
        // build object graph
        $className = $this->createClass(array(
        'public $number = 3',
        'public $members = array()',
        'public $string = \'abc\'',
        'public $null'), array('Sysgear\Backup\BackupableInterface'),
        $this->createClassBackupableInterface()
        );

        $classNameUser1 = $this->createClass(array(
        'public $name = \'hello world 1\''
        ), array('Sysgear\Backup\BackupableInterface'),
        $this->createClassBackupableInterface()
        );

        $classNameUser2 = $this->createClass(array(
        'public $name = \'hello world 2\'',
        'public $hideMe = \'one two tree\''
        ), array('Sysgear\Backup\BackupableInterface'),
        $this->createClassBackupableInterface()
        );

        $object = new $className();
        $object->members[] = new $classNameUser1();
        $object->members[] = new $classNameUser2();

        // build path to exclude
        $path = new NodePath();
        $path->add(NodePath::NODE, $this->getClassName($object))
            ->add(NodePath::COLLECTION, 'members')
            ->add(NodePath::NODE, $this->getClassName($object->members[0]), 0);

        $path2 = new NodePath();
        $path2->add(NodePath::NODE, $this->getClassName($object))
            ->add(NodePath::COLLECTION, 'members')
            ->add(NodePath::NODE, $this->getClassName($object->members[1]), 1)
            ->add(NodePath::VALUE, 'hideMe');

        // configure inventory manager
        $inventoryManager = new InventoryManager();
        $inventoryManager->getExcludeList()->add(new Expression((string) $path, null));
        $inventoryManager->getExcludeList()->add(new Expression((string) $path2, 'two', Operator::LIKE));

        // collect!
        $collector = new BackupCollector();
        $collector->setOption('inventoryManager', $inventoryManager);
        $collector->fromObject($object);

        // assert
        $node = $collector->getNode();

        $user1Node = new Node('object', $this->getClassName($object->members[1]));
        $user1Node->setMetadata('class', __CLASS__ . '\\' . $this->getClassName($object->members[1]));
        $user1Node->setMetadata('key', 'i;1');
        $user1Node->setProperty('name', new NodeProperty('string', 'hello world 2'));

        $expectedNode = new Node('object', $this->getClassName($object));
        $expectedNode->setMetadata('class', __CLASS__ . '\\' . $this->getClassName($object));
        $expectedNode->setProperty('number', new NodeProperty('integer', 3));
        $expectedNode->setProperty('members', new NodeCollection(array($user1Node)));
        $expectedNode->setProperty('string', new NodeProperty('string', 'abc'));

        $this->assertEquals($expectedNode, $node);
    }

    protected function getClassName($object)
    {
        $fullClassname = is_string($object) ? $object : get_class($object);
        $pos = strrpos($fullClassname, '\\');
        return (false === $pos) ? $fullClassname : substr($fullClassname, $pos + 1);
    }

    protected function createClassBackupableInterface(array $restoreOptions = array())
    {
        $options = var_export($restoreOptions, true);
        $ns = '\\Sysgear\\StructuredData\\Collector\\BackupCollector';
        $m1 = "public function collectStructedData({$ns} \$col, array \$options = array())\n".
            "{\$col->fromObject(\$this, {$options});}";

        $ns = '\\Sysgear\\StructuredData\\Restorer\\BackupRestorer';
        $m2 = "public function restoreStructedData({$ns} \$res)\n".
            '{$remaining = $res->toObject($this);'."\n".
            'foreach ($remaining as $name => $value) {$this->{$name} = $value;}}';

        return array($m1, $m2);
    }

    protected function createClass(array $properties = array(),
        array $interfaces = array(), array $methods = array(), $extends = null)
    {
        // get classname
        $count = 0;
        $className = 'Generated_' . $count;
        while (class_exists(__CLASS__ . '\\' .$className)) {
            $count++;
            $className = 'Generated_' . $count;
        }

        // get extends and interfaces
        $extends = (null === $extends) ? '' : ' extends \\' . $extends;
        $implements = (! $interfaces) ? '' : ' implements \\' . join(', \\', $interfaces);

        // build class code
        $code = 'namespace ' . __CLASS__ . ";\n";
        $code .= "class {$className}{$implements}{$extends} {\n";
        foreach ($properties as $propertyLine) {
            $code .= "\t{$propertyLine};\n";
        }
        foreach ($methods as $methodLine) {
            $code .= "\t{$methodLine}\n";
        }
        $code .= "}";

        eval($code);
        return __CLASS__ . '\\' . $className;
    }
}