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

class BackupTest extends TestCase
{
    public function testBackup()
    {
        $tool = new BackupTool(new XmlExporter(), new XmlImporter(), array('datetime' => false));
        $export = $tool->backup($this->backupBasicCompany());

        $this->assertEquals($this->expectedBasicCompanyXml(), $export->formatOutput(true)->toString());
    }

    public function testXmlExporterFormat()
    {
        $company = new Company();
        $tool = new BackupTool(new XmlExporter(), new XmlImporter(), array('datetime' => false));
        $export = $tool->backup($company);

        // Assert that the company to restore is empty.
        $this->assertEquals("<?xml version=\"1.0\" encoding=\"utf8\"?>\n<backup><metadata/><content>" .
        	"<Company type=\"object\" class=\"Sysgear\\Tests\\Backup\\Company\"><functions type=\"array\"/><employees type=\"array\"/>" .
        	"</Company></content></backup>", $export->formatOutput(false)->toString());

        // Assert formatted XML structure.
        $this->assertEquals("<?xml version=\"1.0\" encoding=\"utf8\"?>\n<backup>\n  <metadata/>\n  <content>" .
        	"\n    <Company type=\"object\" class=\"Sysgear\\Tests\\Backup\\Company\">\n      <functions type=\"array\"/>" .
        	"\n      <employees type=\"array\"/>\n    </Company>\n  </content>\n</backup>",
            $export->formatOutput(true)->toString());
    }

    public function testRestore()
    {
        // Restore company.
        $importer = new XmlImporter();
        $importer->fromString($this->expectedBasicCompanyXml());
        $tool = new BackupTool(new XmlExporter(), $importer);
        $company = $tool->restore(new Company());

        // Assert relations.
        $hash1 = spl_object_hash($company);
        $hash2 = spl_object_hash($company->functions[0]->company);
        $this->assertEquals($hash1, $hash2);

        // Assert protected & private properties.
        $this->assertEquals('rts', $company->getName());
        $this->assertEquals('piet', $company->getEmployee(0)->getName());
    }
}