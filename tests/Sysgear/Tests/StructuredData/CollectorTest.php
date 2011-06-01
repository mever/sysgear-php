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
        $collector = new Collector\SimpleObjectCollector();
        $collector->fromObject($this->backupBasicCompany());

        $exporter = new XmlExporter();
        $exporter->setDom($collector->getDom());

        $this->assertEquals($this->expectedBasicCompanyXml(), rtrim((string) $exporter->formatOutput(true)));
    }

    protected function expectedBasicCompanyXml()
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<Company id="1" name="rts">
  <Locale id="1">
    <Language id="1" iso639="en_EN"/>
  </Locale>
  <functions>
    <Role id="1" name="admin">
      <members>
        <User id="1" name="piet" password="bf7s83s">
          <Company id="1" name="rts"/>
          <roles/>
        </User>
      </members>
      <Company id="1" name="rts"/>
    </Role>
  </functions>
  <employees>
    <User id="1" name="piet" password="bf7s83s">
      <Company id="1" name="rts"/>
      <roles/>
    </User>
  </employees>
</Company>';
    }
}