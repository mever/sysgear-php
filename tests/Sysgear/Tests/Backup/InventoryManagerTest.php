<?php

/*
 * This file is part of the Sysgear package.
 *
 * (c) Martijn Evers <mevers47@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sysgear\Tests\Backup;

use Sysgear\StructuredData\NodePath;
use Sysgear\Backup\InventoryManager;
use Sysgear\Filter\Collection;
use Sysgear\Filter\Expression;
use Sysgear\Operator;

class InventoryManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $inventory = new InventoryManager();
    }


    /**
     * Testing include & exclude preseeding (with nodes)
     */

    public function testIsAllowed_node()
    {
        $path = new NodePath();
        $path->add(NodePath::NODE, 'company');

        $inventory = new InventoryManager();
        $this->assertTrue($inventory->isAllowed($path));
    }

    public function testIsAllowed_node_include_false()
    {
        $path = new NodePath();
        $path->add(NodePath::NODE, 'company');

        $inventory = new InventoryManager();
        $inventory->getIncludeList()->add(new Expression('no match', null));
        $this->assertFalse($inventory->isAllowed($path));
    }

    public function testIsAllowed_node_include_true()
    {
        $path = new NodePath();
        $path->add(NodePath::NODE, 'company');

        $inventory = new InventoryManager();
        $inventory->getIncludeList()->add(new Expression((string) $path, null));
        $this->assertTrue($inventory->isAllowed($path));
    }

    public function testIsAllowed_node_exclude_false()
    {
        $path = new NodePath();
        $path->add(NodePath::NODE, 'company');

        $inventory = new InventoryManager();
        $inventory->getExcludeList()->add(new Expression('no match', null));
        $this->assertTrue($inventory->isAllowed($path));
    }

    public function testIsAllowed_node_exclude_true()
    {
        $path = new NodePath();
        $path->add(NodePath::NODE, 'company');

        $inventory = new InventoryManager();
        $inventory->getExcludeList()->add(new Expression((string) $path, null));
        $this->assertFalse($inventory->isAllowed($path));
    }


    /**
     * Test collections
     */

    public function testIsAllowed_col()
    {
        $path = new NodePath();
        $path->add(NodePath::COLLECTION, 'members');

        $inventory = new InventoryManager();
        $this->assertTrue($inventory->isAllowed($path));
    }

    public function testIsAllowed_col_include()
    {
        $path = new NodePath();
        $path->add(NodePath::COLLECTION, 'members');

        $inventory = new InventoryManager();
        $inventory->getIncludeList()->add(new Expression((string) $path, null));
        $this->assertTrue($inventory->isAllowed($path));
    }

    public function testIsAllowed_col_exclude()
    {
        $path = new NodePath();
        $path->add(NodePath::COLLECTION, 'members');

        $inventory = new InventoryManager();
        $inventory->getExcludeList()->add(new Expression((string) $path, null));
        $this->assertFalse($inventory->isAllowed($path));
    }


    /**
     * Test values
     */

    public function testIsAllowed_value()
    {
        $path = new NodePath();
        $path->add(NodePath::VALUE, 'name');

        $inventory = new InventoryManager();
        $this->assertTrue($inventory->isAllowed($path));
    }

    public function testIsAllowed_value_include()
    {
        $path = new NodePath();
        $path->add(NodePath::VALUE, 'name');

        $inventory = new InventoryManager();
        $inventory->getIncludeList()->add(new Expression((string) $path, null));
        $this->assertTrue($inventory->isAllowed($path));
    }

    public function testIsAllowed_value_exclude()
    {
        $path = new NodePath();
        $path->add(NodePath::VALUE, 'name');

        $inventory = new InventoryManager();
        $inventory->getExcludeList()->add(new Expression((string) $path, null));
        $this->assertFalse($inventory->isAllowed($path));
    }


    /**
     * Test mixed path
     */

    public function testIsAllowed_mixedPath()
    {
        $path = new NodePath();
        $path->add(NodePath::NODE, 'company');
        $path->add(NodePath::COLLECTION, 'employees');
        $path->add(NodePath::NODE, 'user', 1);
        $path->add(NodePath::VALUE, 'name');

        $inventory = new InventoryManager();
        $inventory->getExcludeList()->add(new Expression((string) $path, null));
        $this->assertFalse($inventory->isAllowed($path));
    }


    /**
     * Test conditions (no value)
     */

    public function testIsAllowed_andExclude()
    {
        $path = new NodePath();
        $path->add(NodePath::NODE, 'company');
        $path->add(NodePath::COLLECTION, 'employees');
        $path->add(NodePath::NODE, 'user', 1);
        $path->add(NodePath::VALUE, 'name');

        $inventory = new InventoryManager();
        $inventory->getExcludeList()->add(new Expression('no match', null));
        $inventory->getExcludeList()->add(new Expression((string) $path, null));
        $this->assertTrue($inventory->isAllowed($path));
    }

    public function testIsAllowed_orExclude()
    {
        $path = new NodePath();
        $path->add(NodePath::NODE, 'company');
        $path->add(NodePath::COLLECTION, 'employees');
        $path->add(NodePath::NODE, 'user', 1);
        $path->add(NodePath::VALUE, 'name');

        $inventory = new InventoryManager();
        $inventory->getExcludeList()->setType(Collection::TYPE_OR);
        $inventory->getExcludeList()->add(new Expression('no match', null));
        $inventory->getExcludeList()->add(new Expression((string) $path, null));
        $this->assertFalse($inventory->isAllowed($path));
    }

    public function testIsAllowed_orValueNested()
    {
        $path = new NodePath();
        $path->add(NodePath::NODE, 'company');
        $path->add(NodePath::COLLECTION, 'employees');
        $path->add(NodePath::NODE, 'user', 1);
        $path->add(NodePath::VALUE, 'name');

        $collection = new Collection(array(
            new Expression('no match', null),
            new Expression('no match2', null),
            new Expression((string) $path, null)
        ));

        $collection->setType(Collection::TYPE_OR);

        $inventory = new InventoryManager();
        $inventory->getExcludeList()->add(new Expression('\\Ncompany', null));
        $inventory->getExcludeList()->add($collection);
        $this->assertFalse($inventory->isAllowed($path));
    }


    /**
     * Test conditions (with value)
     */

    public function testIsAllowed_condition_equalOperation()
    {
        $path = new NodePath();
        $path->add(NodePath::NODE, 'company');
        $path->add(NodePath::COLLECTION, 'employees');
        $path->add(NodePath::NODE, 'user', 1);
        $path->add(NodePath::VALUE, 'name');

        $inventory = new InventoryManager();
        $inventory->getExcludeList()->add(new Expression('\\Ncompany\\Cemployees\\1Nuser\\Vname', 'hello world'));
        $this->assertTrue($inventory->isAllowed($path, 'lo wo'));
        $this->assertFalse($inventory->isAllowed($path, 'hello world'));
    }

    public function testIsAllowed_condition_likeOperation()
    {
        $path = new NodePath();
        $path->add(NodePath::NODE, 'company');
        $path->add(NodePath::COLLECTION, 'employees');
        $path->add(NodePath::NODE, 'user', 1);
        $path->add(NodePath::VALUE, 'name');

        $inventory = new InventoryManager();
        $inventory->getExcludeList()->add(
            new Expression('\\Ncompany\\Cemployees\\1Nuser\\Vname', 'hello world', Operator::LIKE));

        $this->assertFalse($inventory->isAllowed($path, 'lo wo'));
        $this->assertFalse($inventory->isAllowed($path, 'hello world'));
    }
}