<?php

/*
 * This file is part of the Sysgear package.
 *
 * (c) Martijn Evers <martijn4evers@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sysgear\Tests\StructuredData\Exporter;

use Sysgear\Tests\StructuredData\TestCase;
use Sysgear\StructuredData\Exporter\XmlExporter;

class XmlTest extends TestCase
{
    public function testCompile_userComplete()
    {
        $exporter = new XmlExporter();
        $exporter->setNode($this->getUserComplete());
        $exporter->formatOutput(true);
        $this->assertEquals('<?xml version="1.0" encoding="UTF-8"?>
<User xmlns:xlink="http://www.w3.org/1999/xlink" type="object" class="\Sysgear\Tests\StructuredData\User">
  <id type="integer" value="11"/>
  <name type="string" value="test"/>
  <password type="string" value="$1$irVZosm9$eYSZynm/kUm1e6ja3YIya1"/>
  <employer type="object" class="\Sysgear\Tests\StructuredData\Company">
    <id type="integer" value="12"/>
    <name type="string" value="rts"/>
  </employer>
  <roles type="array">
    <Role type="object" class="\Sysgear\Tests\StructuredData\Role">
      <id type="integer" value="13"/>
      <name type="string" value="superAdmin123"/>
      <company type="object" class="Sysgear\Tests\StructuredData\Company">
        <id type="integer" value="14"/>
      </company>
    </Role>
  </roles>
</User>', $exporter->__toString());
    }

    public function testCompile_userReferencedCompany()
    {
        $exporter = new XmlExporter();
        $exporter->setNode($this->getUserReferenced());
        $exporter->formatOutput(true);
        $this->assertEquals('<?xml version="1.0" encoding="UTF-8"?>
<User xmlns:xlink="http://www.w3.org/1999/xlink" type="object" class="\Sysgear\Tests\StructuredData\User">
  <id type="integer" value="21"/>
  <password type="string" value="$1$irVZosm9$eYSZynm/kUm1e6ja3YIya1"/>
  <employer type="object" class="Sysgear\Tests\StructuredData\Company">
    <id type="integer" value="22"/>
  </employer>
  <roles type="array">
    <Role type="object" class="\Sysgear\Tests\StructuredData\Role">
      <id type="integer" value="23"/>
      <name type="string" value="admin role"/>
      <company xlink:href="#element(/1/3)"/>
    </Role>
  </roles>
</User>', $exporter->__toString());
    }
}