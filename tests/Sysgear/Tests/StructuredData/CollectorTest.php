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

use Sysgear\StructuredData\Collector;

class CollectorTest extends TestCase
{
    public function testObjectCollector()
    {
        var_dump(serialize($this->backupBasicCompany()));
        
        $collector = new Collector\ObjectCollector();
        $collector->fromObject($this->backupBasicCompany());
        $doc = $collector->getDomDocument();
        $doc->formatOutput = true;
        $this->assertEquals($this->expectedBasicCompanyXml(), rtrim($doc->saveXML()));
    }
}