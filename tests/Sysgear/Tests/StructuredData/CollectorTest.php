<?php

/*
 * This file is part of the Sysgear package.
 *
 * (c) Martijn Evers <martijn4evers@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sysgear\Tests\StructuredData;

use Sysgear\StructuredData\Exporter\XmlExporter;
use Sysgear\StructuredData\Collector;

class CollectorTest extends TestCase
{
    public function testSimpleCollector()
    {
        $collector = new Collector\SimpleCollector();
        $collector->fromObject($this->backupBasicCompany());

        $exporter = new XmlExporter();
        $exporter->setDom($collector->getDom());

        $this->assertEquals($this->expectedBasicCompanyXml(), rtrim((string) $exporter->formatOutput(true)));
    }
}