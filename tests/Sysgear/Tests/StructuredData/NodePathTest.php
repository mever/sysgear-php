<?php

/*
 * This file is part of the Sysgear package.
*
* (c) Martijn Evers <mevers47@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

// \Ncompany\Cfunctions\4\Nadmin\Cmembers\2\Nuser\Vname

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

        $this->assertEquals('\\Ncompany\\Cfunctions\\4\\Nadmin\\Cmembers\\2\\Nuser\\Vname', (string) $path);
    }

    public function testAdd_escaping()
    {
        $path = new NodePath();
        $path->add(NodePath::NODE, 'company');
        $path->add(NodePath::COLLECTION, 'fun\\Nctions');
        $path->add(NodePath::NODE, 'admin', 4);

        $this->assertEquals('\\Ncompany\\Cfun\\\\Nctions\\4\\Nadmin', (string) $path);
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

        $this->assertEquals('\\Ncompany\\Cfunctions\\0\\Nadmin\\Cmembers\\2\\Nuser\\Vname', (string) $path);
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