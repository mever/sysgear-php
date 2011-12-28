<?php

/*
 * This file is part of the Sysgear package.
 *
 * (c) Martijn Evers <martijn4evers@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sysgear\Tests\Backup;

use Sysgear\StructuredData\Exporter\XmlExporter;
use Sysgear\StructuredData\Importer\XmlImporter;
use Sysgear\Backup\BackupTool;

require_once 'fixtures/Company.php';

class RestoreTest extends TestCase
{
    /**
     * @expectedException \Sysgear\Backup\Exception
     * @expectedExceptionMessage The backup has no content to restore.
     */
    public function testNoContent()
    {
        $importer = new XmlImporter();
        $importer->fromString('<?xml version="1.0" encoding="UTF-8"?>
<backup xmlns:xlink="http://www.w3.org/1999/xlink" type="container" meta-type="object">
  <date type="rfc1123" value="Wed, 28 Dec 2011 14:09:25 +0100" meta-type="property"/>
</backup>');

        $tool = new BackupTool(new XmlExporter(), $importer);
        $tool->restore();
    }

    public function testRestore()
    {
       $importer = new XmlImporter();
       $importer->fromString('<?xml version="1.0" encoding="UTF-8"?>
<backup xmlns:xlink="http://www.w3.org/1999/xlink" meta-type="object" type="container">'
  /* -removed: date field */ .'
  <content type="object" meta-type="object" class="Sysgear\Tests\Backup\Company">
    <id type="integer" value="1" meta-type="property"/>
    <name type="string" value="rts" meta-type="property"/>
    <locale type="object" meta-type="object" class="Sysgear\Tests\Backup\Locale">
      <id type="integer" value="1" meta-type="property"/>
      <language type="object" meta-type="object" class="Sysgear\Tests\Backup\Language">
        <id type="integer" value="1" meta-type="property"/>
        <iso639 type="string" value="en_EN" meta-type="property"/>
      </language>
    </locale>
    <functions type="list" meta-type="collection">
      <Role type="object" meta-type="object" class="Sysgear\Tests\Backup\Role">
        <id type="integer" value="1" meta-type="property"/>
        <name type="string" value="admin" meta-type="property"/>
        <members type="list" meta-type="collection">
          <User type="object" meta-type="object" class="Sysgear\Tests\Backup\User">
            <id type="integer" value="1" meta-type="property"/>
            <name type="string" value="piet" meta-type="property"/>
            <password type="string" value="bf7s83s" meta-type="property"/>
            <employer xlink:href="#element(/1/1)"/>
            <roles type="list" meta-type="collection"/>
            <sessions type="list" meta-type="collection"/>
          </User>
        </members>
        <company xlink:href="#element(/1/1)"/>
      </Role>
    </functions>
    <employees type="list" meta-type="collection">
      <User xlink:href="#element(/1/1/4/1/3/1)"/>
    </employees>
  </content>
</backup>');

       $tool = new BackupTool(new XmlExporter(), $importer);
       $company = $tool->restore(array());
       $this->assertEquals($this->basicCompany(), $company);
    }
}