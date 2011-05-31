<?php

/*
 * This file is part of the Sysgear package.
 *
 * (c) Martijn Evers <martijn4evers@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sysgear\Tests\StructuredData\Restorer;

use Zend\Pdf\PdfParser\StructureParser;

use Sysgear\StructuredData\Restorer\BackupRestorer;
use Sysgear\Tests\StructuredData\TestCase;
use Sysgear\Tests\StructuredData;

class BackupRestorerTest extends TestCase
{
    public static $debug = false;

    public function testDefaultMergeMode()
    {
        $restorer = new BackupRestorer();
        $this->assertEquals(BackupRestorer::MERGE_ASSUME_COMPLETE, $restorer->getOption('mergerMode'));
    }

    /**
     * Test merging incomplete nodes, assume these as incomplete.
     */
    public function testMergeIncompleteAsIncomplete()
    {
        if (BackupRestorerTest::$debug) {
            echo "\n-{ ".__FUNCTION__." }-----------------------\n";
        }

        // emulate storage
        $storedRoleCompany = new StructuredData\Company(444257, 'roleCompany');
        $storedUserCompany = new StructuredData\Company(135673, 'userCompany');

        // emulate merger: methods
        $state1 = 0;
        $that = $this;
        $getMandatoryProperties = function($obj) use (&$state1){
            if (BackupRestorerTest::$debug) {
                echo 'getMandatoryProperties: ' . get_class($obj) . " ($state1)\n";
            }
            switch ($state1++) {
            case 0:    return array('company');   // $obj = Role
            case 1:    return array();            // $obj = Company (for Role)
            case 2:    return array('employer');  // $obj = User
            default:   return array();            // $obj = Company (for User)
            }
        };

        $state2 = 0;
        $find = function($obj) use (&$state2, $storedRoleCompany, $storedUserCompany, $that) {
            if (BackupRestorerTest::$debug) {echo 'find: ' . get_class($obj) . " ($state2)\n";}
            switch ($state2++) {
            case 0:    // $obj = Role
                $that->assertEquals(2, $obj->id);
                $obj->company = $storedRoleCompany;
                break;

            case 1:    // $obj = User
                $that->assertEquals(1, $obj->id);
                $obj->employer = $storedUserCompany;
                break;
            }

            return $obj;
        };

        // check for successfull merge
        $state3 = 0;
        $that = $this;
        $merge = function($obj) use (&$state3, $that) {
            if (BackupRestorerTest::$debug) {echo 'merge: ' . get_class($obj) . " ($state3)\n";}
            $state3++;
            return $obj;
        };

        // mock merger
        $merger = $this->getMock('Sysgear\\Merger\\MergerInterface', array(
        	'merge', 'find', 'flush', 'getMandatoryProperties'), array(), 'TestMerger1');
        $merger->expects($this->exactly(4))->method('merge')->will($this->returnCallback($merge));
        $merger->expects($this->exactly(2))->method('find')->will($this->returnCallback($find));
        $merger->expects($this->exactly(1))->method('flush');
        $merger->expects($this->exactly(4))->method('getMandatoryProperties')
            ->will($this->returnCallback($getMandatoryProperties));

        // run restorer
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadXML(file_get_contents(__DIR__ . '/../fixtures/user_incomplete.xml'));

        $restorer = new BackupRestorer();
        $restorer->setOption('merger', $merger);
        $restorer->setOption('mergeMode', BackupRestorer::MERGE_ASSUME_INCOMPLETE);
        $user = $restorer->restore($dom->getElementsByTagName('User')->item(0));

        // assert returned user
        $that->assertEquals(1, $user->id);
        $that->assertEquals(135673, $user->employer->id);
        $that->assertEquals('userCompany', $user->employer->name);
        $that->assertEquals('superAdmin123', $user->roles[0]->name);
        $that->assertEquals(444257, $user->roles[0]->company->id);
    }

    /**
     * Test merging complete nodes, assume these as incomplete.
     */
    public function testMergeCompleteAsIncomplete()
    {
        if (BackupRestorerTest::$debug) {
            echo "\n-{ ".__FUNCTION__." }-----------------------\n";
        }

        // emulate storage
        $storedUserCompany = new StructuredData\Company(135673, 'userCompany');

        // emulate merger: methods
        $state1 = 0;
        $getMandatoryProperties = function($obj) use (&$state1) {
            if (BackupRestorerTest::$debug) {
                echo 'getMandatoryProperties: ' . get_class($obj) . " ($state1)\n";
            }
            switch ($state1++) {
            case 0:    return array();       // $obj = Company (from User)
            case 1:    return array('name'); // $obj = Company (from Role, missing "name" field)
            default:   return array();
            }
        };

        $state2 = 0;
        $find = function($obj) use (&$state2, $storedUserCompany) {
            if (BackupRestorerTest::$debug) {echo 'find: ' . get_class($obj) . " ($state2)\n";}
            switch ($state2++) {
            case 0:    // $obj = Company (from Role)
                $obj->name = 'found name';
                break;
            }

            return $obj;
        };

        // check for successfull merge
        $state3 = 0;
        $that = $this;
        $merge = function($obj) use (&$state3, $that) {
            if (BackupRestorerTest::$debug) {echo 'merge: ' . get_class($obj) . " ($state3)\n";}
            switch ($state3++) {
            case 0: $that->assertEquals(12, $obj->id); break; // $obj = Company (from User)
            case 1: $that->assertEquals(14, $obj->id); break; // $obj = Company (from Role)
            case 2: $that->assertEquals(13, $obj->id); break; // $obj = Role 13
            case 3: $that->assertEquals(11, $obj->id); break; // $obj = User 11
            }
            return $obj;
        };


        // mock merger
        $merger = $this->getMock('Sysgear\\Merger\\MergerInterface', array(
        	'merge', 'find', 'flush', 'getMandatoryProperties'), array(), 'TestMerger2');
        $merger->expects($this->exactly(4))->method('merge')->will($this->returnCallback($merge));
        $merger->expects($this->exactly(1))->method('find')->will($this->returnCallback($find));
        $merger->expects($this->exactly(1))->method('flush');
        $merger->expects($this->exactly(4))->method('getMandatoryProperties')
            ->will($this->returnCallback($getMandatoryProperties));

        // run restorer
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadXML(file_get_contents(__DIR__ . '/../fixtures/user_complete.xml'));

        $restorer = new BackupRestorer();
        $restorer->setOption('merger', $merger);
        $restorer->setOption('mergeMode', BackupRestorer::MERGE_ASSUME_INCOMPLETE);
        $user = $restorer->restore($dom->getElementsByTagName('User')->item(0));

        // assert company from file
        $this->assertEquals(14, $user->roles[0]->company->id);
        $this->assertTrue($storedUserCompany !== $user->employer);
    }

    /**
     * Test merging incomplete nodes, assume these as complete.
     */
    public function testMergeIncompleteAsComplete()
    {
        if (BackupRestorerTest::$debug) {
            echo "\n-{ ".__FUNCTION__." }-----------------------\n";
        }

        // emulate storage
        $storedUserCompany = new StructuredData\Company(135673, 'userCompany');

        // emulate merger: methods
        $state1 = 0;
        $find = function($obj) use (&$state1, $storedUserCompany) {
            if (BackupRestorerTest::$debug) {echo 'find: ' . get_class($obj) . " ($state1)\n";}
            switch ($state1++) {
            case 0:    // $obj = User
                $obj->employer = $storedUserCompany;
                break;
            }

            return $obj;
        };

        // check for successfull merge
        $state2 = 0;
        $that = $this;
        $merge = function($obj) use (&$state2, $that) {
            if (BackupRestorerTest::$debug) {echo 'merge: ' . get_class($obj) . " ($state2)\n";}
            switch ($state2++) {
            case 0: $that->assertEquals(2, $obj->id); break; // $obj = Role 2

            // $obj = User; this fails because the User has a mandatory employer field.
            // Extra note: Role is missing company too, but we pretend in this test if
            // it is not mandatory by not returning null.
            case 1: return null;
            }
            return $obj;
        };


        // mock merger
        $merger = $this->getMock('Sysgear\\Merger\\MergerInterface', array(
        	'merge', 'find', 'flush', 'getMandatoryProperties'), array(), 'TestMerger3');
        $merger->expects($this->exactly(4))->method('merge')->will($this->returnCallback($merge));
        $merger->expects($this->exactly(1))->method('find')->will($this->returnCallback($find));
        $merger->expects($this->exactly(1))->method('flush');
        $merger->expects($this->exactly(0))->method('getMandatoryProperties');

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadXML(file_get_contents(__DIR__ . '/../fixtures/user_incomplete.xml'));

        $restorer = new BackupRestorer();
        $restorer->setOption('merger', $merger);
        $user = $restorer->restore($dom->getElementsByTagName('User')->item(0));

        // assert restored user
        $this->assertEquals(135673, $user->employer->id);
        $this->assertEquals('userCompany', $user->employer->name);
        $this->assertEquals(2, $user->roles[0]->id);
    }

    /**
     * Test merging complete nodes, assume these as complete.
     */
    public function testMergeCompleteAsComplete()
    {
        if (BackupRestorerTest::$debug) {
            echo "\n-{ ".__FUNCTION__." }-----------------------\n";
        }

        $merger = $this->getMock('Sysgear\\Merger\\MergerInterface', array(
        	'merge', 'find', 'flush', 'getMandatoryProperties'), array(), 'TestMerger4');

        // check for successfull merge
        $state1 = 0;
        $that = $this;
        $merge = function($obj) use (&$state1, $that) {
            if (BackupRestorerTest::$debug) {echo 'merge: ' . get_class($obj) . " ($state1)\n";}
            switch ($state1++) {
            case 0: $that->assertEquals(12, $obj->id); break; // $obj = Company 12
            case 1: $that->assertEquals(14, $obj->id); break; // $obj = Company 14
            case 2: $that->assertEquals(13, $obj->id); break; // $obj = Role 13
            case 3: $that->assertEquals(11, $obj->id); break; // $obj = User 11
            }
            return $obj;
        };

        $merger->expects($this->exactly(4))->method('merge')->will($this->returnCallback($merge));
        $merger->expects($this->exactly(0))->method('find');
        $merger->expects($this->exactly(1))->method('flush');
        $merger->expects($this->exactly(0))->method('getMandatoryProperties');

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadXML(file_get_contents(__DIR__ . '/../fixtures/user_complete.xml'));

        $restorer = new BackupRestorer();
        $restorer->setOption('merger', $merger);
        $restorer->restore($dom->getElementsByTagName('User')->item(0));
    }

    /**
     * Test merging referenced nodes, assume these as incomplete.
     */
    public function testMergeReferencedAsIncomplete()
    {
        if (BackupRestorerTest::$debug) {
            echo "\n-{ ".__FUNCTION__." }-----------------------\n";
        }

        // emulate storage
        $storedUserCompany = new StructuredData\Company(22, 'userCompany');

        // emulate merger: methods
        $state1 = 0;
        $getMandatoryProperties = function($obj) use (&$state1) {
            if (BackupRestorerTest::$debug) {
                echo 'getMandatoryProperties: ' . get_class($obj) . " ($state1)\n";
            }
            switch ($state1++) {
            case 0:    return array('name');      // $obj = Company (from User)
            case 1:    return array('company');   // $obj = Role
            case 2:    return array('employer');  // $obj = User
            default:   return array();
            }
        };

        $state2 = 0;
        $find = function($obj) use (&$state2, $storedUserCompany) {
            if (BackupRestorerTest::$debug) {echo 'find: ' . get_class($obj) . " ($state2)\n";}
            switch ($state2++) {
            case 0:    // $obj = Company
                $obj = $storedUserCompany;
                break;
            }

            return $obj;
        };

        // check for successfull merge
        $state3 = 0;
        $that = $this;
        $merge = function($obj) use (&$state3, $that, $storedUserCompany) {
            if (BackupRestorerTest::$debug) {echo 'merge: ' . get_class($obj) . " ($state3)\n";}
            switch ($state3++) {
            case 0: $that->assertEquals(22, $obj->id); break; // $obj = Company 22 (from User)
            case 1: $that->assertEquals(23, $obj->id); break; // $obj = Role 23
            }
            return $obj;
        };


        // mock merger
        $merger = $this->getMock('Sysgear\\Merger\\MergerInterface', array(
        	'merge', 'find', 'flush', 'getMandatoryProperties'), array(), 'TestMerger5');
        $merger->expects($this->exactly(3))->method('merge')->will($this->returnCallback($merge));
        $merger->expects($this->exactly(1))->method('find')->will($this->returnCallback($find));
        $merger->expects($this->exactly(1))->method('flush');
        $merger->expects($this->exactly(3))->method('getMandatoryProperties')
            ->will($this->returnCallback($getMandatoryProperties));

        // run restorer
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadXML(file_get_contents(__DIR__ . '/../fixtures/user_referenced.xml'));

        $restorer = new BackupRestorer();
        $restorer->setOption('merger', $merger);
        $restorer->setOption('mergeMode', BackupRestorer::MERGE_ASSUME_INCOMPLETE);
        $user = $restorer->restore($dom->getElementsByTagName('User')->item(0));

        // assert user
        $this->assertEquals(22, $user->roles[0]->company->id);
        $this->assertTrue($user->employer === $user->roles[0]->company);
        $this->assertEquals('userCompany', $user->employer->name);
    }

    /**
     * Test merging referenced nodes, assume these as complete.
     */
    public function testMergeReferencedAsComplete()
    {
        if (BackupRestorerTest::$debug) {
            echo "\n-{ ".__FUNCTION__." }-----------------------\n";
        }

        // emulate storage
        $storedUserCompany = new StructuredData\Company(null, 'userCompany-abc');

        // emulate merger: methods
        $state1 = 0;
        $find = function($obj) use (&$state1, $storedUserCompany) {
            if (BackupRestorerTest::$debug) {echo 'find: ' . get_class($obj) . " ($state1)\n";}
            switch ($state1++) {
            case 0:    // $obj = Company
                $obj = $storedUserCompany;
                break;

            case 1:    // $obj = User
                $obj->name = 'stored user name';
                break;
            }

            return $obj;
        };

        // check for successfull merge
        $state2 = 0;
        $that = $this;
        $merge = function($obj) use (&$state2, $that) {
            if (BackupRestorerTest::$debug) {echo 'merge: ' . get_class($obj) . " ($state2)\n";}
            switch ($state2++) {

            // $obj = Company; this fails because the Company has a mandatory name field.
            case 0: return null;

            // $obj = User; this fails because the User has a mandatory name field.
            case 2: return null;
            }
            return $obj;
        };


        // mock merger
        $merger = $this->getMock('Sysgear\\Merger\\MergerInterface', array(
        	'merge', 'find', 'flush', 'getMandatoryProperties'), array(), 'TestMerger6');
        $merger->expects($this->exactly(5))->method('merge')->will($this->returnCallback($merge));
        $merger->expects($this->exactly(2))->method('find')->will($this->returnCallback($find));
        $merger->expects($this->exactly(1))->method('flush');
        $merger->expects($this->exactly(0))->method('getMandatoryProperties');

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadXML(file_get_contents(__DIR__ . '/../fixtures/user_referenced.xml'));

        $restorer = new BackupRestorer();
        $restorer->setOption('merger', $merger);
        $user = $restorer->restore($dom->getElementsByTagName('User')->item(0));

        // assert restored user
        $this->assertTrue($storedUserCompany !== $user->employer);
        $this->assertTrue($user->employer === $user->roles[0]->company);
        $this->assertEquals(23, $user->roles[0]->id);
    }
}