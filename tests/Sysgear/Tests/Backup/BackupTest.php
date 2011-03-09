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
    /**
     * Test company simple backup.
     */
    public function testCompanyBackup()
    {
        $comp = $this->basicCompany();
        $tool = new BackupTool(new XmlExporter(), new XmlImporter(), array('datetime' => false));
        $export = $tool->backup($comp);

        $this->assertEquals($this->expectedBasicCompanyXml($comp),
            $export->formatOutput(true)->__toString());
    }

	/**
     * Test user simple backup.
     */
    public function testUserBackup()
    {
        $user = $this->basicUser();
        $tool = new BackupTool(new XmlExporter(), new XmlImporter(), array('datetime' => false));
        $export = $tool->backup($user);

        $this->assertEquals($this->expectedBasicUserXml($user->getEmployer()),
            $export->formatOutput(true)->__toString());
    }

    /**
     * Test backup of company with users who instruct to ignore some properties.
     */
    public function testIgnoreSomeUserPropertiesBackup()
    {
        $user = $this->ignoreSomeUserPropertiesUser();
        $tool = new BackupTool(new XmlExporter(), new XmlImporter(), array('datetime' => false));
        $export = $tool->backup($user);

        $this->assertEquals($this->expectedIgnoreSomeUserPropertiesXml($user->getEmployer()),
            $export->formatOutput(true)->__toString());
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

        $this->assertEquals($this->expectedDoNotScanAndIgnoreSomeUserPropertiesXml($user->getEmployer()),
            $export->formatOutput(true)->__toString());
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

        $this->assertEquals($this->expectedInheritedBasicCompanyXml($comp, $onlyImplementor),
            (string) $export->formatOutput(true));
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

        $this->assertEquals($this->expectedInheritedBasicCompanyXml($comp, $onlyImplementor),
            (string) $export->formatOutput(true));
    }

    /**
     * Test xml export formatting.
     */
    public function testXmlExporterFormat()
    {
        $company = new Company();
        $objHash = spl_object_hash($company);
        $tool = new BackupTool(new XmlExporter(), new XmlImporter(), array('datetime' => false));
        $export = $tool->backup($company);

        // Assert that the company to restore is empty.
        $this->assertEquals("<?xml version=\"1.0\" encoding=\"utf8\"?>\n<backup><metadata/><content>" .
        	"<Company type=\"object\" class=\"Sysgear\\Tests\\Backup\\Company\" id=\"{$objHash}\">" .
        	"<functions type=\"array\"/><employees type=\"array\"/>" .
        	"</Company></content></backup>", $export->formatOutput(false)->__toString());

        // Assert formatted XML structure.
        $this->assertEquals("<?xml version=\"1.0\" encoding=\"utf8\"?>\n<backup>\n  <metadata/>\n  <content>" .
        	"\n    <Company type=\"object\" class=\"Sysgear\\Tests\\Backup\\Company\" id=\"{$objHash}\">" 
            ."\n      <functions type=\"array\"/>\n      <employees type=\"array\"/>\n    </Company>\n  </content>\n</backup>",
            $export->formatOutput(true)->__toString());
    }

    /**
     * Test restoring the object from XML.
     */
    public function testRestoreFromXml()
    {
        // Restore company.
        $dummyHashes = $this->basicCompany();
        $importer = new XmlImporter();
        $importer->fromString($this->expectedBasicCompanyXml($dummyHashes));
        $tool = new BackupTool(new XmlExporter(), $importer);
        $company = $tool->restore(new Company());

        // Assert relations.
        $hash1 = spl_object_hash($company);
        $hash2 = spl_object_hash($company->functions[0]->company);
        $this->assertEquals($hash1, $hash2);

        // Assert protected & private properties.
        $this->assertEquals('rts', $company->getName());
        $this->assertEquals('piet', $company->getEmployee(0)->getName());

        // Assert properties.
        $this->assertFalse(isset($company->locale->language->name));
    }
}