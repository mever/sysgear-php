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

use Sysgear\Tests\StructuredData\TestCase;
use Sysgear\Tests\StructuredData;
use Sysgear\StructuredData\Restorer\BackupRestorer;
use Sysgear\StructuredData\NodeCollection;
use Sysgear\StructuredData\NodeProperty;
use Sysgear\StructuredData\Node;

class BackupRestorerTest extends TestCase
{
    public static $debug = false;

    public function testRestoreConstructorWithMandatoryParams()
    {
        $methods = $this->createClassBackupableInterface();
        $methods[] = 'public function __construct($mandatory) {$this->number = $mandatory;}';
        $className = $this->createClass(array(
            'public $number = 3',
            'public $string = \'abc\'',
            'public $default = \'still here\'',
            'public $null'), array('Sysgear\Backup\BackupableInterface'),
            $methods
        );

        $objNode = new Node('object', 'Object');
        $objNode->setMetadata('class', $className);
        $objNode->setProperty('number', new NodeProperty('integer', 4));
        $objNode->setProperty('string', new NodeProperty('integer', 123));
        $objNode->setProperty('null', new NodeProperty('string', 'not null'));

        $restorer = new BackupRestorer();
        $object = $restorer->restore($objNode);

        $assertObj = new $className(8);
        $this->assertEquals(8, $assertObj->number);
        $assertObj->number = 4;
        $assertObj->string = 123;
        $assertObj->null = 'not null';

        $this->assertEquals($assertObj, $object);
    }

    /**
     * Test merging incomplete nodes, assume these as incomplete.
     */
    public function testMergeIncompleteAsIncomplete()
    {
        if (BackupRestorerTest::$debug) {
            echo "\n-{ ".__FUNCTION__." }-----------------------\n";
        }

        // emulate storage: a user without roles
        $storedRole = new StructuredData\Role();
        $storedRoleCompany = new StructuredData\Company(444257, 'roleCompany');
        $storedRole->company = $storedRoleCompany;

        $storedUser = new StructuredData\User();
        $storedUserCompany = new StructuredData\Company(135673, 'userCompany');
        $storedUser->employer = $storedUserCompany;

        // emulate merger: methods
        $state1 = 0;
        $that = $this;
        $getMandatoryProperties = function($obj) use (&$state1){
            if (BackupRestorerTest::$debug) {
                echo 'getMandatoryProperties: ' . get_class($obj) . " ($state1)\n";
            }
            switch ($state1++) {
            case 0:  return array('company');   // $obj = Role
            case 1:  return array('employer');  // $obj = User
            default: return array();
            }
        };

        $state2 = 0;
        $find = function($obj) use (&$state2, $storedRole, $storedRoleCompany, $storedUser, $that) {
            if (BackupRestorerTest::$debug) {echo 'find: ' . get_class($obj) . " ($state2)\n";}
            switch ($state2++) {
            case 0:    // $obj = Role
                $that->assertEquals(2, $obj->id);
                $obj = $storedRole;
                break;

            case 1:    // $obj = User
                $that->assertEquals(1, $obj->id);
                $obj = $storedUser;
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
            'merge', 'find', 'flush', 'getMandatoryProperties', 'getObjectId'));
        $merger->expects($this->exactly(2))->method('merge')->will($this->returnCallback($merge));
        $merger->expects($this->exactly(2))->method('find')->will($this->returnCallback($find));
        $merger->expects($this->exactly(1))->method('flush');
        $merger->expects($this->exactly(2))->method('getMandatoryProperties')
            ->will($this->returnCallback($getMandatoryProperties));

        // restore user
        $restorer = new BackupRestorer();
        if (BackupRestorerTest::$debug) {
            $merger->expects($this->exactly(2))->method('getObjectId')->will($this->returnValue('test stub'));
            $restorer->setOption('logger', function($lvl, $line) {
                for ($i=0; $i<$lvl; $i++) {
                    echo "  ";
                }
                echo "{$line}\n";
            });
        }
        $restorer->setOption('merger', $merger);
        $restorer->setOption('mergeMode', BackupRestorer::MERGE_ASSUME_INCOMPLETE);
        $user = $restorer->restore($this->getUserIncomplete());

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
        $storedCompany = new StructuredData\Company(135673, 'userCompany');
        $storedCompany->locale = new StructuredData\Locale(234782);

        // emulate merger: methods
        $that = $this;
        $state1 = 0;
        $getMandatoryProperties = function($obj) use (&$state1) {
            if (BackupRestorerTest::$debug) {
                echo 'getMandatoryProperties: ' . get_class($obj) . " ($state1)\n";
            }
            switch ($state1++) {
            case 0:    return array('locale');  // $obj = Company (from User)
            case 1:    return array('name');    // $obj = Company (from Role, missing "name" field)
            default:   return array();
            }
        };

        $state2 = 0;
        $find = function($obj) use (&$state2, $that, $storedCompany) {
            if (BackupRestorerTest::$debug) {echo 'find: ' . get_class($obj) . " ($state2)\n";}
            switch ($state2++) {
            case 0:    // $obj = Company (from User)
                $that->assertEquals(12, $obj->id);
                $obj = $storedCompany;
                break;

            case 1:    // $obj = Company (from Role)
                $that->assertEquals(14, $obj->id);
                $obj = $storedCompany;
                break;
            }

            return $obj;
        };

        // check for successfull merge
        $state3 = 0;
        $merge = function($obj) use (&$state3, $that) {
            if (BackupRestorerTest::$debug) {echo 'merge: ' . get_class($obj) . " ($state3)\n";}
            switch ($state3++) {
            case 0: $that->assertEquals(12, $obj->id); break; // $obj = Company (from User)
            case 1: $that->assertEquals(14, $obj->id);
                $that->assertEquals('userCompany', $obj->name); break; // $obj = Company (from Role)
            case 2: $that->assertEquals(13, $obj->id); break; // $obj = Role 13
            case 3: $that->assertEquals(11, $obj->id); break; // $obj = User 11
            }
            return $obj;
        };


        // mock merger
        $merger = $this->getMock('Sysgear\\Merger\\MergerInterface', array(
            'merge', 'find', 'flush', 'getMandatoryProperties', 'getObjectId'));
        $merger->expects($this->exactly(4))->method('merge')->will($this->returnCallback($merge));
        $merger->expects($this->exactly(2))->method('find')->will($this->returnCallback($find));
        $merger->expects($this->exactly(1))->method('flush');
        $merger->expects($this->exactly(4))->method('getMandatoryProperties')
            ->will($this->returnCallback($getMandatoryProperties));

        // create test nodes

        $restorer = new BackupRestorer();
        if (BackupRestorerTest::$debug) {
            $merger->expects($this->exactly(2))->method('getObjectId')->will($this->returnValue('test stub'));
            $restorer->setOption('logger', function($lvl, $line) {
                for ($i=0; $i<$lvl; $i++) {
                    echo "  ";
                }
                echo "{$line}\n";
            });
        }

        $restorer->setOption('merger', $merger);
        $restorer->setOption('mergeMode', BackupRestorer::MERGE_ASSUME_INCOMPLETE);
        $user = $restorer->restore($this->getUserComplete());

        // assert company from file
        $this->assertEquals(14, $user->roles[0]->company->id);
        $this->assertTrue($storedCompany !== $user->employer);
        $this->assertTrue($storedCompany->locale !== $user->employer->locale);
        $this->assertEquals($storedCompany->locale->id, $user->employer->locale->id);
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
        $getMandatoryProperties = function($obj) use (&$state1) {
            if (BackupRestorerTest::$debug) {
                echo 'getMandatoryProperties: ' . get_class($obj) . " ($state1)\n";
            }
            switch ($state1++) {
            case 0:    return array('employer');  // $obj = User
            default:   return array();
            }
        };

        $state2 = 0;
        $find = function($obj) use (&$state2, $storedUserCompany) {
            if (BackupRestorerTest::$debug) {echo 'find: ' . get_class($obj) . " ($state2)\n";}
            switch ($state2++) {
            case 0:    // $obj = User
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
            switch ($state3++) {
            case 0: $that->assertEquals(1, $obj->id); return null; // $obj = User 1, failed = missing employer

            // $obj = User; this fails because the User has a mandatory employer field.
            case 1: return null;
            }
            return $obj;
        };


        // mock merger
        $merger = $this->getMock('Sysgear\\Merger\\MergerInterface', array(
            'merge', 'find', 'flush', 'getMandatoryProperties', 'getObjectId'));
        $merger->expects($this->exactly(2))->method('merge')->will($this->returnCallback($merge));
        $merger->expects($this->exactly(1))->method('find')->will($this->returnCallback($find));
        $merger->expects($this->exactly(1))->method('flush');
        $merger->expects($this->exactly(1))->method('getMandatoryProperties')
            ->will($this->returnCallback($getMandatoryProperties));

        // restore incomplete user
        $restorer = new BackupRestorer();
        if (BackupRestorerTest::$debug) {
            $merger->expects($this->exactly(1))->method('getObjectId')->will($this->returnValue('test stub'));
            $restorer->setOption('logger', function($lvl, $line) {
                for ($i=0; $i<$lvl; $i++) {
                    echo "  ";
                }
                echo "{$line}\n";
            });
        }
        $restorer->setOption('merger', $merger);
        $restorer->setOption('mergeMode', BackupRestorer::MERGE_ASSUME_COMPLETE);
        $user = $restorer->restore($this->getUserIncomplete());

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
            'merge', 'find', 'flush', 'getMandatoryProperties', 'getObjectId'));

        // check for successfull merge
        $state1 = 0;
        $that = $this;
        $merge = function($obj) use (&$state1, $that) {
            if (BackupRestorerTest::$debug) {echo 'merge: ' . get_class($obj) . " ($state1)\n";}
            switch ($state1++) {
            case 0: $that->assertEquals(11, $obj->id); break; // $obj = User 11
            }
            return $obj;
        };

        $merger->expects($this->exactly(1))->method('merge')->will($this->returnCallback($merge));
        $merger->expects($this->exactly(1))->method('flush');

        $restorer = new BackupRestorer();
        if (BackupRestorerTest::$debug) {
            $restorer->setOption('logger', function($lvl, $line) {
                for ($i=0; $i<$lvl; $i++) {
                    echo "  ";
                }
                echo "{$line}\n";
            });
        }
        $restorer->setOption('merger', $merger);
        $user = $restorer->restore($this->getUserComplete());
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
        $storedUserCompany = new StructuredData\Company(22222222, 'userCompany');

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
            'merge', 'find', 'flush', 'getMandatoryProperties', 'getObjectId'));
        $merger->expects($this->exactly(3))->method('merge')->will($this->returnCallback($merge));
        $merger->expects($this->exactly(1))->method('find')->will($this->returnCallback($find));
        $merger->expects($this->exactly(1))->method('flush');
        $merger->expects($this->exactly(3))->method('getMandatoryProperties')
            ->will($this->returnCallback($getMandatoryProperties));

        // run restorer
        $restorer = new BackupRestorer();
        if (BackupRestorerTest::$debug) {
            $merger->expects($this->exactly(1))->method('getObjectId')->will($this->returnValue('test stub'));
            $restorer->setOption('logger', function($lvl, $line) {
                for ($i=0; $i<$lvl; $i++) {
                    echo "  ";
                }
                echo "{$line}\n";
            });
        }
        $restorer->setOption('merger', $merger);
        $restorer->setOption('mergeMode', BackupRestorer::MERGE_ASSUME_INCOMPLETE);
        $user = $restorer->restore($this->getUserReferenced());

        // assert user
        $this->assertEquals(22, $user->roles[0]->company->id);
        $this->assertTrue($user->employer !== $user->roles[0]->company);
        $this->assertEquals('userCompany', $user->employer->name);
        $this->assertEquals('userCompany', $user->roles[0]->company->name);
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
        $getMandatoryProperties = function($obj) use (&$state1) {
            if (BackupRestorerTest::$debug) {
                echo 'getMandatoryProperties: ' . get_class($obj) . " ($state1)\n";
            }
            switch ($state1++) {
            case 0:    return array('name');      // $obj = Company (from User)
            default:   return array();
            }
        };

        $state2 = 0;
        $find = function($obj) use (&$state2, $storedUserCompany) {
            if (BackupRestorerTest::$debug) {echo 'find: ' . get_class($obj) . " ($state2)\n";}
            switch ($state2++) {
            case 1:    // $obj = Company
                $obj = $storedUserCompany;
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

            // $obj = Company; this fails because the Company has a mandatory "name" field.
            case 0: return null;
            }

            return $obj;
        };


        // mock merger
        $merger = $this->getMock('Sysgear\\Merger\\MergerInterface', array(
            'merge', 'find', 'flush', 'getMandatoryProperties', 'getObjectId'));
        $merger->expects($this->exactly(2))->method('merge')->will($this->returnCallback($merge));
        $merger->expects($this->exactly(1))->method('find')->will($this->returnCallback($find));
        $merger->expects($this->exactly(1))->method('flush');
        $merger->expects($this->exactly(1))->method('getMandatoryProperties')
            ->will($this->returnCallback($getMandatoryProperties));

        $restorer = new BackupRestorer();
        if (BackupRestorerTest::$debug) {
            $merger->expects($this->exactly(1))->method('getObjectId')->will($this->returnValue('test stub'));
            $restorer->setOption('logger', function($lvl, $line) {
                for ($i=0; $i<$lvl; $i++) {
                    echo "  ";
                }
                echo "{$line}\n";
            });
        }
        $restorer->setOption('merger', $merger);
        $user = $restorer->restore($this->getUserReferenced());

        // assert restored user
        $this->assertEquals(22, $user->roles[0]->company->id);
        $this->assertEquals('', $user->roles[0]->company->name);
        $this->assertTrue($user->employer === $user->roles[0]->company);
        $this->assertTrue($storedUserCompany !== $user->employer);
        $this->assertEquals(23, $user->roles[0]->id);
    }
}