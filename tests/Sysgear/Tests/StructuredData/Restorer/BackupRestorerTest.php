<?php

/*
 * This file is part of the Sysgear package.
 *
 * (c) Martijn Evers <martijn4evers@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sysgear\Tests\StructuredData\Restorer;

use Sysgear\Tests\StructuredData\TestCase;
use Sysgear\Tests\StructuredData;
use Sysgear\StructuredData\Restorer\BackupRestorer;
use Sysgear\StructuredData\NodeCollection;
use Sysgear\StructuredData\NodeProperty;
use Sysgear\StructuredData\Node;

class BackupRestorerTest extends TestCase
{
    public function testRestoreConstructorWithMandatoryParams()
    {
        $methods = $this->createClassBackupableInterface();
        $methods[] = 'public function __construct($mandatory) {$this->number = $mandatory;}';
        $className = $this->createClass(array(
            'public $number = 3',
            'public $string = \'abc\'',
            'public $default = \'still here\'',
            'public $null'), array('Sysgear\Backup\BackupableInterface'),
            $methods
        );

        $objNode = new Node('object', 'Object');
        $objNode->setMetadata('class', $className);
        $objNode->setProperty('number', new NodeProperty('integer', 4));
        $objNode->setProperty('string', new NodeProperty('integer', 123));
        $objNode->setProperty('null', new NodeProperty('string', 'not null'));

        $restorer = new BackupRestorer();
        $object = $restorer->restore($objNode);

        $assertObj = new $className(8);
        $this->assertEquals(8, $assertObj->number);
        $assertObj->number = 4;
        $assertObj->string = 123;
        $assertObj->null = 'not null';

        $this->assertEquals($assertObj, $object);
    }
}