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
use Sysgear\StructuredData\Restorer\BackupRestorer;
use Sysgear\Backup\BackupTool;

require_once 'fixtures/Language.php';
require_once 'fixtures/Locale.php';
require_once 'fixtures/Company.php';
require_once 'fixtures/Role.php';
require_once 'fixtures/User.php';

class RestoreTest extends TestCase
{
    /**
     * Test merging complete nodes, assume these as complete.
     */
    public function testMergeCompleteAsCompleteWithoutBackupTool()
    {
        $merger = $this->getMock('Sysgear\\Merger\\MergerInterface', array(
        	'merge', 'find', 'flush', 'getMandatoryProperties'), array(), 'TestMerger1');

        $merger->expects($this->exactly(3))->method('merge')->will($this->returnArgument(0));
        $merger->expects($this->exactly(0))->method('find');
        $merger->expects($this->exactly(0))->method('flush');
        $merger->expects($this->exactly(0))->method('getMandatoryProperties');

        $tool = new BackupTool(new XmlExporter(), new XmlImporter());
        $tool->setRestorerOption('merger', $merger);
        $tool->setRestorerOption('mergeMode', BackupRestorer::MERGE_ASSUME_COMPLETE);
        $tool->readFile(__DIR__ . '/fixtures/user_complete.xml');
        $tool->restore();
    }

    /**
     * Test merging complete nodes, assume these as complete (backup tool default).
     * And let the backup tool merge the root node and flush the changes.
     */
    public function testMergeCompleteAsComplete()
    {
        $merger = $this->getMock('Sysgear\\Merger\\MergerInterface', array(
        	'merge', 'find', 'flush', 'getMandatoryProperties'), array(), 'TestMerger2');

        $merger->expects($this->exactly(4))->method('merge')->will($this->returnArgument(0));
        $merger->expects($this->exactly(0))->method('find');
        $merger->expects($this->exactly(1))->method('flush');
        $merger->expects($this->exactly(0))->method('getMandatoryProperties');

        $tool = new BackupTool(new XmlExporter(), new XmlImporter());
        $tool->setOption('merger', $merger);
        $tool->readFile(__DIR__ . '/fixtures/user_complete.xml');
        $tool->restore();
    }

    /**
     * Test merging incomplete nodes, assume these as complete (backup tool default).
     * Causing the restorer to request the merger to find similar notes to complete
     * the to be merged node with.
     */
    public function testMergeIncompleteAsComplete()
    {
        $merger = $this->getMock('Sysgear\\Merger\\MergerInterface', array(
        	'merge', 'find', 'flush', 'getMandatoryProperties'), array(), 'TestMerger3');

        $merge = function($obj) {
            if ($obj instanceof User) {
                return $obj;
            } else {
                return null;
            }
        };

        $find = function($role) {
            $role->company = new Company(4, 'testComp');
            return $role;
        };

        $merger->expects($this->exactly(2))->method('merge')->will($this->returnCallback($merge));
        $merger->expects($this->exactly(1))->method('find')->will($this->returnCallback($find));
        $merger->expects($this->exactly(1))->method('flush');
        $merger->expects($this->exactly(0))->method('getMandatoryProperties');

        $tool = new BackupTool(new XmlExporter(), new XmlImporter());
        $tool->setOption('merger', $merger);
        $tool->readFile(__DIR__ . '/fixtures/user_incomplete.xml');
        $restoredObject = $tool->restore();

        $roles = $restoredObject->getRoles();
        $this->assertEquals('testComp', $roles[0]->company->getName());
    }
}