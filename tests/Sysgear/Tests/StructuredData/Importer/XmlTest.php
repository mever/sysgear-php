<?php

/*
 * This file is part of the Sysgear package.
 *
 * (c) Martijn Evers <martijn4evers@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sysgear\Tests\StructuredData\Importer;

use Sysgear\Tests\StructuredData\TestCase;
use Sysgear\StructuredData\Importer\XmlImporter;
use Sysgear\StructuredData\NodeCollection;
use Sysgear\StructuredData\NodeProperty;
use Sysgear\StructuredData\Node;

class XmlTest extends TestCase
{
    public function testCompile_userComplete_withMetaTypes()
    {
        $importer = new XmlImporter();
        $importer->fromString('<?xml version="1.0" encoding="UTF-8"?>
<User xmlns:xlink="http://www.w3.org/1999/xlink" type="object" meta-type="object" class="\Sysgear\Tests\StructuredData\User">
  <id type="integer" value="11" meta-type="property"/>
  <name type="string" value="test" meta-type="property"/>
  <password type="string" value="$1$irVZosm9$eYSZynm/kUm1e6ja3YIya1" meta-type="property"/>
  <employer type="object" meta-type="object" class="\Sysgear\Tests\StructuredData\Company">
    <id type="integer" value="12" meta-type="property"/>
    <name type="string" value="rts" meta-type="property"/>
  </employer>
  <roles type="list" meta-type="collection">
    <Role type="object" meta-type="object" class="\Sysgear\Tests\StructuredData\Role">
      <id type="integer" value="13" meta-type="property"/>
      <name type="string" value="superAdmin123" meta-type="property"/>
      <company type="object" meta-type="object" class="\Sysgear\Tests\StructuredData\Company">
        <id type="integer" value="14" meta-type="property"/>
      </company>
    </Role>
  </roles>
</User>');

        $company = new Node('object', 'company');
        $company->setMetadata('class', '\Sysgear\Tests\StructuredData\Company');
        $company->setProperty('id', new NodeProperty('integer', 14));

        $employer = new Node('object', 'employer');
        $employer->setMetadata('class', '\Sysgear\Tests\StructuredData\Company');
        $employer->setProperty('id', new NodeProperty('integer', 12));
        $employer->setProperty('name', new NodeProperty('string', 'rts'));

        $role = new Node('object', 'Role');
        $role->setMetadata('class', '\Sysgear\Tests\StructuredData\Role');
        $role->setProperty('id', new NodeProperty('integer', 13));
        $role->setProperty('name', new NodeProperty('string', 'superAdmin123'));
        $role->setProperty('company', $company);

        $roles = new NodeCollection(array($role));

        $node = new Node('object', 'User');
        $node->setMetadata('class', '\Sysgear\Tests\StructuredData\User');
        $node->setProperty('id', new NodeProperty('integer', 11));
        $node->setProperty('name', new NodeProperty('string', 'test'));
        $node->setProperty('password', new NodeProperty('string', '$1$irVZosm9$eYSZynm/kUm1e6ja3YIya1'));
        $node->setProperty('employer', $employer);
        $node->setProperty('roles', $roles);

        $this->assertEquals($node, $importer->getNode());
    }

    public function testCompile_userComplete_withReferences()
    {
        $importer = new XmlImporter();
        $importer->fromString('<?xml version="1.0" encoding="UTF-8"?>
<User xmlns:xlink="http://www.w3.org/1999/xlink" type="object" meta-type="object" class="\Sysgear\Tests\StructuredData\User">
  <id type="integer" value="11" meta-type="property"/>
  <name type="string" value="test" meta-type="property"/>
  <password type="string" value="$1$irVZosm9$eYSZynm/kUm1e6ja3YIya1" meta-type="property"/>
  <employer type="object" meta-type="object" class="\Sysgear\Tests\StructuredData\Company">
    <id type="integer" value="12" meta-type="property"/>
    <name type="string" value="rts" meta-type="property"/>
  </employer>
  <roles type="list" meta-type="collection">
    <Role type="object" meta-type="object" class="\Sysgear\Tests\StructuredData\Role">
      <id type="integer" value="13" meta-type="property"/>
      <name type="string" value="superAdmin123" meta-type="property"/>
      <company xlink:href="#element(/1/4)"/>
    </Role>
  </roles>
</User>');

        $employer = new Node('object', 'employer');
        $employer->setMetadata('class', '\Sysgear\Tests\StructuredData\Company');
        $employer->setProperty('id', new NodeProperty('integer', 12));
        $employer->setProperty('name', new NodeProperty('string', 'rts'));

        $role = new Node('object', 'Role');
        $role->setMetadata('class', '\Sysgear\Tests\StructuredData\Role');
        $role->setProperty('id', new NodeProperty('integer', 13));
        $role->setProperty('name', new NodeProperty('string', 'superAdmin123'));
        $role->setProperty('company', $employer);

        $roles = new NodeCollection(array($role));

        $node = new Node('object', 'User');
        $node->setMetadata('class', '\Sysgear\Tests\StructuredData\User');
        $node->setProperty('id', new NodeProperty('integer', 11));
        $node->setProperty('name', new NodeProperty('string', 'test'));
        $node->setProperty('password', new NodeProperty('string', '$1$irVZosm9$eYSZynm/kUm1e6ja3YIya1'));
        $node->setProperty('employer', $employer);
        $node->setProperty('roles', $roles);

        $this->assertEquals($node, $importer->getNode());
    }

    /**
     * @expectedException \Sysgear\StructuredData\Importer\ImporterException
     * @expectedExceptionMessage Could not determine node type ''
     */
    public function testCompile_cannotFindMetaType()
    {
        $importer = new XmlImporter();
        $importer->fromString('<?xml version="1.0" encoding="UTF-8"?>
<User xmlns:xlink="http://www.w3.org/1999/xlink" type="object" class="\Sysgear\Tests\StructuredData\User">
  <id type="integer" value="11"/>
  <name type="string" value="test"/>
  <password type="string" value="$1$irVZosm9$eYSZynm/kUm1e6ja3YIya1"/>
</User>');
    }

    /**
     * @expectedException \Sysgear\StructuredData\Importer\ImporterException
     * @expectedExceptionMessage Could not determine node type 'blaaat'
     */
    public function testCompile_wrongFindMetaType()
    {
        $importer = new XmlImporter();
        $importer->fromString('<?xml version="1.0" encoding="UTF-8"?>
<User xmlns:xlink="http://www.w3.org/1999/xlink" type="object"
        meta-type="blaaat" class="\Sysgear\Tests\StructuredData\User">
  <id type="integer" value="11"/>
  <name type="string" value="test"/>
  <password type="string" value="$1$irVZosm9$eYSZynm/kUm1e6ja3YIya1"/>
</User>');
    }
}