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
        return;
        $tool = new BackupTool(new XmlExporter(), new XmlImporter());
        $export = $tool->backup($this->backupBasicCompany());
var_dump($export->formatOutput(true)->toString()); return;
        $this->assertEquals($this->expectedBasicCompanyXml(), $export->formatOutput(true)->toString());
    }

    public function testRestore()
    {
        return;
        $company = new Company();
        $tool = new BackupTool(new XmlExporter(), new XmlImporter());
        $export = $tool->backup($company);

        // Assert that the company to restore is empty.
        $this->assertEquals("<?xml version=\"1.0\" encoding=\"utf8\"?>\n<company Mclass=\"Sysgear\\Tests\\Backup\\Company\">" .
        	"<functions/><employees/></company>", $export->formatOutput(false)->toString());

        // Assert formatted XML structure.
        $this->assertEquals("<?xml version=\"1.0\" encoding=\"utf8\"?>\n<company Mclass=\"Sysgear\\Tests\\Backup\\Company\">" .
        	"\n  <functions/>\n  <employees/>\n</company>", $export->formatOutput(true)->toString());

        // Remove old backup tool instance and create new one to restore the company to expectedBasicCompany.
        unset($tool);
        $importer = new XmlImporter();
        $importer->fromString($this->expectedBasicCompanyXml());
        $tool = new BackupTool(new XmlExporter(), $importer);

        // Restore company.
        $tool->restore($company);
        var_dump($company);
        
//        $this->assertEquals($this->expectBasicCompanyXml(), $export->toString());
    }
}