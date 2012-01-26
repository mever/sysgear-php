<?php

/*
 * This file is part of the Sysgear package.
*
* (c) Martijn Evers <mevers47@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Sysgear\Tests\StructuredData;

use Sysgear\StructuredData\NodePath;

class NodePathTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $path = new NodePath();
        $this->assertEquals('', (string) $path);
    }

    public function testAdd()
    {
        $path = new NodePath();
        $path->add(NodePath::NODE, 'company');
        $path->add(NodePath::COLLECTION, 'functions');
        $path->add(NodePath::NODE, 'admin', 4);
        $path->add(NodePath::COLLECTION, 'members');
        $path->add(NodePath::NODE, 'user', 2);
        $path->add(NodePath::VALUE, 'name');

        $this->assertEquals('\\Ncompany\\Cfunctions\\4Nadmin\\Cmembers\\2Nuser\\Vname', (string) $path);
    }

    public function testAdd_escaping()
    {
        $path = new NodePath();
        $path->add(NodePath::NODE, 'company');
        $path->add(NodePath::COLLECTION, 'fun\\Nctions');
        $path->add(NodePath::NODE, 'admin', 4);

        $this->assertEquals('\\Ncompany\\Cfun\\\\Nctions\\4Nadmin', (string) $path);
    }

    public function testAdd_missingIndexOnCol()
    {
        $path = new NodePath();
        $path->add(NodePath::NODE, 'company');
        $path->add(NodePath::COLLECTION, 'functions');
        $path->add(NodePath::NODE, 'admin');
        $path->add(NodePath::COLLECTION, 'members');
        $path->add(NodePath::NODE, 'user', 2);
        $path->add(NodePath::VALUE, 'name');

        $this->assertEquals('\\Ncompany\\Cfunctions\\0Nadmin\\Cmembers\\2Nuser\\Vname', (string) $path);
    }

    public function testChaining()
    {
        $path = new NodePath();
        $path->node('company')->col('functions')->node('admin')->col('members')->node('user', 2)->value('name');
        $this->assertEquals('\\Ncompany\\Cfunctions\\0Nadmin\\Cmembers\\2Nuser\\Vname', (string) $path);
    }

    public function testgetSegments()
    {
        $path = new NodePath();
        $path->add(NodePath::NODE, 'company');
        $path->add(NodePath::COLLECTION, 'fun\\Nctions');
        $path->add(NodePath::NODE, 'admin', 4);
        $path->add(NodePath::VALUE, 'name');

        $this->assertEquals(array(
            'Ncompany',
            'Cfun\\Nctions',
            '4Nadmin',
            'Vname'
        ), $path->getSegments());
    }

    public function testIn_full()
    {
        $path = new NodePath();
        $path->add(NodePath::NODE, 'company');
        $path->add(NodePath::COLLECTION, 'fun\\Nctions');
        $path->add(NodePath::NODE, 'admin', 4);
        $path->add(NodePath::VALUE, 'name');

        $path2 = new NodePath();
        $path2->add(NodePath::NODE, 'company');
        $path2->add(NodePath::COLLECTION, 'fun\\Nctions');
        $path2->add(NodePath::NODE, 'admin', 4);
        $path2->add(NodePath::VALUE, 'name');

        $this->assertTrue($path2->in($path));
    }

    public function testIn_full_false()
    {
        $path = new NodePath();
        $path->add(NodePath::NODE, 'company');
        $path->add(NodePath::COLLECTION, 'fun\\Nctions');
        $path->add(NodePath::NODE, 'admin', 4);
        $path->add(NodePath::VALUE, 'name');

        $path2 = new NodePath();
        $path2->add(NodePath::NODE, 'company');
        $path2->add(NodePath::COLLECTION, 'fun\\Nctions');
        $path2->add(NodePath::NODE, 'admin', 4);
        $path2->add(NodePath::VALUE, 'nam');

        $this->assertFalse($path2->in($path));
    }

    public function testIn_part()
    {
        $path = new NodePath();
        $path->add(NodePath::NODE, 'company');
        $path->add(NodePath::COLLECTION, 'fun\\Nctions');
        $path->add(NodePath::NODE, 'admin', 4);
        $path->add(NodePath::VALUE, 'name');

        $path2 = new NodePath();
        $path2->add(NodePath::NODE, 'company');
        $path2->add(NodePath::COLLECTION, 'fun\\Nctions');

        $this->assertTrue($path2->in($path));
    }

    public function testIn_part_false()
    {
        $path = new NodePath();
        $path->add(NodePath::NODE, 'company');
        $path->add(NodePath::COLLECTION, 'fun\\Nctions');
        $path->add(NodePath::NODE, 'admin', 4);
        $path->add(NodePath::VALUE, 'name');

        $path2 = new NodePath();
        $path2->add(NodePath::NODE, 'compAny');
        $path2->add(NodePath::COLLECTION, 'fun\\Nctions');

        $this->assertFalse($path2->in($path));
    }

    public function testClear()
    {
        $path = new NodePath();
        $path->add(NodePath::NODE, 'company');

        $this->assertEquals('\Ncompany', (string) $path);
        $path->clear();
        $this->assertEquals('', (string) $path);
    }
}