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

class BackupTest extends TestCase
{
    protected function removeDateLine($string)
    {
        $lines = explode("\n", $string);
        unset($lines[2]);
        return join("\n", $lines);
    }

    /**
     * Test company simple backup.
     */
    public function testCompanyBackup_exportAsXml()
    {
        $comp = $this->basicCompany();
        $tool = new BackupTool(new XmlExporter(), new XmlImporter());
        $export = $tool->backup($comp);
        $export->setMetaTypeField(null);
        $string = $this->removeDateLine($export->formatOutput(true)->__toString());

        $this->assertEquals('<?xml version="1.0" encoding="UTF-8"?>
<backup xmlns:xlink="http://www.w3.org/1999/xlink" type="container">'
  /* -removed: date field */ .'
  <content type="object" class="Sysgear\Tests\Backup\Company">
    <id type="integer" value="1"/>
    <name type="string" value="rts"/>
    <locale type="object" class="Sysgear\Tests\Backup\Locale">
      <id type="integer" value="1"/>
      <language type="object" class="Sysgear\Tests\Backup\Language">
        <id type="integer" value="1"/>
        <iso639 type="string" value="en_EN"/>
      </language>
    </locale>
    <functions type="list">
      <Role type="object" class="Sysgear\Tests\Backup\Role" key="i;0">
        <id type="integer" value="1"/>
        <name type="string" value="admin"/>
        <members type="list">
          <User type="object" class="Sysgear\Tests\Backup\User" key="i;0">
            <id type="integer" value="1"/>
            <name type="string" value="piet"/>
            <password type="string" value="bf7s83s"/>
            <employer xlink:href="#element(/1/2)"/>
            <roles type="list"/>
            <sessions type="list"/>
          </User>
        </members>
        <company xlink:href="#element(/1/2)"/>
      </Role>
    </functions>
    <employees type="list">
      <User xlink:href="#element(/1/2/4/1/3/1)"/>
    </employees>
  </content>
</backup>', $string);
    }

    /**
     * Test user simple backup.
     */
    public function testUserBackup_exportAsXml()
    {
        $user = $this->basicUser();
        $tool = new BackupTool(new XmlExporter(), new XmlImporter());
        $export = $tool->backup($user);
        $export->setMetaTypeField(null);
        $string = $this->removeDateLine($export->formatOutput(true)->__toString());

        $this->assertEquals('<?xml version="1.0" encoding="UTF-8"?>
<backup xmlns:xlink="http://www.w3.org/1999/xlink" type="container">'
  /* -removed: date field */ .'
  <content type="object" class="Sysgear\Tests\Backup\User" key="i;0">
    <id type="integer" value="1"/>
    <name type="string" value="piet"/>
    <password type="string" value="bf7s83s"/>
    <employer type="object" class="Sysgear\Tests\Backup\Company">
      <id type="integer" value="1"/>
      <name type="string" value="rts"/>
      <locale type="object" class="Sysgear\Tests\Backup\Locale">
        <id type="integer" value="1"/>
        <language type="object" class="Sysgear\Tests\Backup\Language">
          <id type="integer" value="1"/>
          <iso639 type="string" value="en_EN"/>
        </language>
      </locale>
      <functions type="list"/>
      <employees type="list">
        <User xlink:href="#element(/1/2)"/>
      </employees>
    </employer>
    <roles type="list"/>
    <sessions type="list"/>
  </content>
</backup>', $string);
    }

    /**
     * Test backup of company with users who instruct to ignore some properties.
     */
    public function testIgnoreSomeUserPropertiesBackup()
    {
        $user = $this->ignoreSomeUserPropertiesUser();
        $tool = new BackupTool(new XmlExporter(), new XmlImporter(), array('datetime' => false));
        $export = $tool->backup($user);
        $export->setMetaTypeField(null);
        $string = $this->removeDateLine($export->formatOutput(true)->__toString());

        $this->assertEquals('<?xml version="1.0" encoding="UTF-8"?>
<backup xmlns:xlink="http://www.w3.org/1999/xlink" type="container">'
  /* -removed: date field */ .'
  <content type="object" class="Sysgear\Tests\Backup\IgnorePropertiesUser">
    <id type="integer" value="1"/>
    <roles type="list"/>
    <sessions type="list"/>
  </content>
</backup>', $string);
    }

    /**
     * Test backup of company with users who instruct to ignore
     * and don't scan some properties.
     */
    public function testDoNotScanAndIgnoreSomeUserPropertiesBackup()
    {
        $user = $this->doNotScanAndIgnoreSomeUserPropertiesUser();
        $tool = new BackupTool(new XmlExporter(), new XmlImporter(), array('datetime' => false));
        $export = $tool->backup($user);
        $export->setMetaTypeField(null);
        $string = $this->removeDateLine($export->formatOutput(true)->__toString());

        $this->assertEquals('<?xml version="1.0" encoding="UTF-8"?>
<backup xmlns:xlink="http://www.w3.org/1999/xlink" type="container">'
  /* -removed: date field */ .'
  <content type="object" class="Sysgear\Tests\Backup\DoNotScanAndIgnorePropertiesUser">
    <id type="integer" value="1"/>
    <employer type="object" class="Sysgear\Tests\Backup\Company">
      <id type="integer" value="1"/>
      <name type="string" value="rts"/>
    </employer>
    <roles type="list">
      <Role type="object" class="Sysgear\Tests\Backup\Role" key="i;0">
        <id type="integer" value="1"/>
        <name type="string" value="admin"/>
      </Role>
    </roles>
  </content>
</backup>', $string);
    }

    /**
     * Test backup with company object wrapped by a proxy.
     */
    public function testProxyCompanyBackup()
    {
        $onlyImplementor = false;
        $comp = $this->inheritedBasicCompany();
        $tool = new BackupTool(new XmlExporter(), new XmlImporter(), array('datetime' => false));
        $export = $tool->backup($comp, array('onlyImplementor' => $onlyImplementor));
        $export->setMetaTypeField(null);
        $string = $this->removeDateLine($export->formatOutput(true)->__toString());

        $this->assertEquals('<?xml version="1.0" encoding="UTF-8"?>
<backup xmlns:xlink="http://www.w3.org/1999/xlink" type="container">'
  /* -removed: date field */ .'
  <content type="object" class="Sysgear\Tests\Backup\ProxyCompany">
    <shouldBeIgnored1 type="boolean" value="1"/>
    <shouldBeIgnored2 type="boolean" value="1"/>
    <id type="integer" value="1"/>
    <name type="string" value="rts"/>
    <locale type="object" class="Sysgear\Tests\Backup\Locale">
      <id type="integer" value="1"/>
      <language type="object" class="Sysgear\Tests\Backup\Language">
        <id type="integer" value="1"/>
        <iso639 type="string" value="en_EN"/>
      </language>
    </locale>
    <functions type="list">
      <Role type="object" class="Sysgear\Tests\Backup\Role" key="i;0">
        <id type="integer" value="1"/>
        <name type="string" value="admin"/>
        <members type="list">
          <User type="object" class="Sysgear\Tests\Backup\User" key="i;0">
            <id type="integer" value="1"/>
            <name type="string" value="piet"/>
            <password type="string" value="bf7s83s"/>
            <employer xlink:href="#element(/1/2)"/>
            <roles type="list"/>
            <sessions type="list"/>
          </User>
        </members>
        <company xlink:href="#element(/1/2)"/>
      </Role>
    </functions>
    <employees type="list">
      <User xlink:href="#element(/1/2/6/1/3/1)"/>
    </employees>
  </content>
</backup>', $string);
    }

    /**
     * Test backup with company object wrapped by a proxy but with
     * onlyImplementor directive on.
     */
    public function testOnlyImplementorBackup()
    {
        $onlyImplementor = true;
        $comp = $this->inheritedBasicCompany();
        $tool = new BackupTool(new XmlExporter(), new XmlImporter(), array('datetime' => false));
        $export = $tool->backup($comp, array('onlyImplementor' => $onlyImplementor));
        $export->setMetaTypeField(null);
        $string = $this->removeDateLine($export->formatOutput(true)->__toString());

        $this->assertEquals('<?xml version="1.0" encoding="UTF-8"?>
<backup xmlns:xlink="http://www.w3.org/1999/xlink" type="container">'
  /* -removed: date field */ .'
  <content type="object" class="Sysgear\Tests\Backup\Company">
    <id type="integer" value="1"/>
    <name type="string" value="rts"/>
    <locale type="object" class="Sysgear\Tests\Backup\Locale">
      <id type="integer" value="1"/>
      <language type="object" class="Sysgear\Tests\Backup\Language">
        <id type="integer" value="1"/>
        <iso639 type="string" value="en_EN"/>
      </language>
    </locale>
    <functions type="list">
      <Role type="object" class="Sysgear\Tests\Backup\Role" key="i;0">
        <id type="integer" value="1"/>
        <name type="string" value="admin"/>
        <members type="list">
          <User type="object" class="Sysgear\Tests\Backup\User" key="i;0">
            <id type="integer" value="1"/>
            <name type="string" value="piet"/>
            <password type="string" value="bf7s83s"/>
            <employer xlink:href="#element(/1/2)"/>
            <roles type="list"/>
            <sessions type="list"/>
          </User>
        </members>
        <company xlink:href="#element(/1/2)"/>
      </Role>
    </functions>
    <employees type="list">
      <User xlink:href="#element(/1/2/4/1/3/1)"/>
    </employees>
  </content>
</backup>', $string);
    }
}