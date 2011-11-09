<?php

namespace Sysgear\Tests\StructuredData\Collector;

use Sysgear\StructuredData\Collector\BackupCollector;
use Sysgear\StructuredData\Restorer\BackupRestorer;

class BackupCollectorTest extends \PHPUnit_Framework_TestCase
{
    public function testContruct()
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

    public function testOption_className()
    {
        $className = $this->createClass(array(
            'public $number = 3',
            'public $string = \'abc\'',
            'public $null'), array('Sysgear\Backup\BackupableInterface'),
            $this->createClassBackupableInterface()
        );

        $object = new $className();
        $collector = new BackupCollector();
        $collector->setOption('className', 'blaat');
        $collector->fromObject($object);

        $node = $collector->getNode();
        $this->assertEquals('object', $node->getType());
        $this->assertEquals('blaat', $node->getName());
        $this->assertInstanceOf('Sysgear\\StructuredData\\Node', $node);
        $this->assertEquals(array('class' => 'blaat'), $node->getMetadata());
    }

    public function testOption_className_fq()
    {
        $className = $this->createClass(array(
            'public $number = 3',
            'public $string = \'abc\'',
            'public $null'), array('Sysgear\Backup\BackupableInterface'),
            $this->createClassBackupableInterface()
        );

        $object = new $className();
        $collector = new BackupCollector();
        $collector->setOption('className', '\Nowhere\Blaat');
        $collector->fromObject($object);

        $node = $collector->getNode();
        $this->assertEquals('object', $node->getType());
        $this->assertEquals('Blaat', $node->getName());
        $this->assertInstanceOf('Sysgear\\StructuredData\\Node', $node);
        $this->assertEquals(array('class' => '\Nowhere\Blaat'), $node->getMetadata());
    }

    public function testOption_onlyImplementor()
    {
        $className = $this->createClass(
            array('public $col'), array('Sysgear\Backup\BackupableInterface'),
            $this->createClassBackupableInterface()
        );

        // TODO: finish this
    }

    protected function getClassName($object)
    {
        $fullClassname = is_string($object) ? $object : get_class($object);
        $pos = strrpos($fullClassname, '\\');
        return (false === $pos) ? $fullClassname : substr($fullClassname, $pos + 1);
    }

    protected function createClassBackupableInterface()
    {
        $ns = '\\Sysgear\\StructuredData\\Collector\\BackupCollector';
        $m1 = "public function collectStructedData({$ns} \$col, array \$options = array())\n".
        	'{$col->fromObject($this, $options);}';

        $ns = '\\Sysgear\\StructuredData\\Restorer\\BackupRestorer';
        $m2 = "public function restoreStructedData({$ns} \$res)\n".
        	'{$remaining = $res->toObject($this);'."\n".
        	'foreach ($remaining as $name => $value) {$this->{$name} = $value;}}';

        return array($m1, $m2);
    }

    protected function createClass(array $properties = array(),
        array $interfaces = array(), array $methods = array())
    {
        // get classname
        $count = 0;
        $className = 'Generated_' . $count;
        while (class_exists(__CLASS__ . '\\' .$className)) {
            $count++;
            $className = 'Generated_' . $count;
        }

        // get interfaces
        $implements = (! $interfaces) ? '' : ' implements \\' . join(', \\', $interfaces);

        // build class code
        $code = 'namespace ' . __CLASS__ . ";\n";
        $code .= "class {$className}{$implements} {\n";
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