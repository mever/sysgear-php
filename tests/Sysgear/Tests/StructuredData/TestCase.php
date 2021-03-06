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

use Sysgear\StructuredData\NodeCollection;
use Sysgear\StructuredData\NodeProperty;
use Sysgear\StructuredData\Node;

require_once 'fixtures/Language.php';
require_once 'fixtures/Locale.php';
require_once 'fixtures/Company.php';
require_once 'fixtures/Role.php';
require_once 'fixtures/User.php';

class TestCase extends \PHPUnit_Framework_TestCase
{
    protected function getClassName($object)
    {
        $fullClassname = is_string($object) ? $object : get_class($object);
        $pos = strrpos($fullClassname, '\\');
        return (false === $pos) ? $fullClassname : substr($fullClassname, $pos + 1);
    }

    protected function createClassBackupableInterface()
    {
        $ns = '\\Sysgear\\StructuredData\\Collector\\BackupCollector';
        $m1 = "public function collectStructuredData({$ns} \$col, array \$options = array())\n".
            '{$col->fromObject($this, $options);}';

        $ns = '\\Sysgear\\StructuredData\\Restorer\\BackupRestorer';
        $m2 = "public function restoreStructuredData({$ns} \$res)\n".
            '{$remaining = $res->toObject($this);'."\n".
            'foreach ($remaining as $name => $value) {$this->{$name} = $value;}}';

        return array($m1, $m2);
    }

    protected function createClass(array $properties = array(),
        array $interfaces = array(), array $methods = array(), $extends = null)
    {
        // get classname
        $count = 0;
        $className = 'Generated_' . $count;
        while (class_exists(__CLASS__ . '\\' .$className)) {
            $count++;
            $className = 'Generated_' . $count;
        }

        // get extends and interfaces
        $extends = (null === $extends) ? '' : ' extends \\' . $extends;
        $implements = (! $interfaces) ? '' : ' implements \\' . join(', \\', $interfaces);

        // build class code
        $code = 'namespace ' . __CLASS__ . ";\n";
        $code .= "class {$className}{$implements}{$extends} {\n";
        foreach ($properties as $propertyLine) {
            $code .= "\t{$propertyLine};\n";
        }
        foreach ($methods as $methodLine) {
            $code .= "\t{$methodLine}\n";
        }
        $code .= "}";

        eval($code);
        return __CLASS__ . '\\' . $className;
    }

    protected function getUserIncomplete()
    {
        $roleNode = new Node('object', 'Role');
        $roleNode->setMetadata('class', '\Sysgear\Tests\StructuredData\Role');
        $roleNode->setProperty('id', new NodeProperty('integer', 2));
        $roleNode->setProperty('name', new NodeProperty('string', 'superAdmin123'));
        // company > missing

        $userNode = new Node('object', 'User');
        $userNode->setMetadata('class', '\Sysgear\Tests\StructuredData\User');
        $userNode->setProperty('id', new NodeProperty('integer', 1));
        $userNode->setProperty('name', new NodeProperty('string', 'test'));
        $userNode->setProperty('password', new NodeProperty('string', '$1$irVZosm9$eYSZynm/kUm1e6ja3YIya1'));
        $userNode->setProperty('roles', new NodeCollection(array($roleNode)));
        // employer > missing

        return $userNode;
    }

    protected function getUserComplete()
    {
        $companyNode = new Node('object', 'Company');
        $companyNode->setMetadata('class', 'Sysgear\Tests\StructuredData\Company');
        $companyNode->setProperty('id', new NodeProperty('integer', 14));

        $roleNode = new Node('object', 'Role');
        $roleNode->setMetadata('class', '\Sysgear\Tests\StructuredData\Role');
        $roleNode->setProperty('id', new NodeProperty('integer', 13));
        $roleNode->setProperty('name', new NodeProperty('string', 'superAdmin123'));
        $roleNode->setProperty('company', $companyNode);

        $employerNode = new Node('object', 'Employer');
        $employerNode->setMetadata('class', '\Sysgear\Tests\StructuredData\Company');
        $employerNode->setProperty('id', new NodeProperty('integer', 12));
        $employerNode->setProperty('name', new NodeProperty('string', 'rts'));

        $userNode = new Node('object', 'User');
        $userNode->setMetadata('class', '\Sysgear\Tests\StructuredData\User');
        $userNode->setProperty('id', new NodeProperty('integer', 11));
        $userNode->setProperty('name', new NodeProperty('string', 'test'));
        $userNode->setProperty('password', new NodeProperty('string', '$1$irVZosm9$eYSZynm/kUm1e6ja3YIya1'));
        $userNode->setProperty('employer', $employerNode);
        $userNode->setProperty('roles', new NodeCollection(array($roleNode)));

        return $userNode;
    }

    protected function getUserReferenced()
    {
        $companyNode = new Node('object', 'Company');
        $companyNode->setMetadata('class', 'Sysgear\Tests\StructuredData\Company');
        $companyNode->setProperty('id', new NodeProperty('integer', 22));

        $roleNode = new Node('object', 'Role');
        $roleNode->setMetadata('class', '\Sysgear\Tests\StructuredData\Role');
        $roleNode->setProperty('id', new NodeProperty('integer', 23));
        $roleNode->setProperty('name', new NodeProperty('string', 'admin role'));
        $roleNode->setProperty('company', $companyNode);

        $userNode = new Node('object', 'User');
        $userNode->setMetadata('class', '\Sysgear\Tests\StructuredData\User');
        $userNode->setProperty('id', new NodeProperty('integer', 21));
        $userNode->setProperty('password', new NodeProperty('string', '$1$irVZosm9$eYSZynm/kUm1e6ja3YIya1'));
        $userNode->setProperty('employer', $companyNode);
        $userNode->setProperty('roles', new NodeCollection(array($roleNode)));

        return $userNode;
    }

    protected function getCompanyReferenced()
    {
        $companyNode = new Node('object', 'Company');
        $companyNode->setMetadata('class', 'Sysgear\Tests\StructuredData\Company');
        $companyNode->setProperty('id', new NodeProperty('integer', 222));

        $userNode = new Node('object', 'User');
        $userNode->setMetadata('class', '\Sysgear\Tests\StructuredData\User');
        $userNode->setProperty('id', new NodeProperty('integer', 221));
        $userNode->setProperty('password', new NodeProperty('string', '$1$irVZosm9$eYSZynm/kUm1e6ja3YIya1'));
        $userNode->setProperty('employer', $companyNode);

        $userNode2 = new Node('object', 'User');
        $userNode2->setMetadata('class', '\Sysgear\Tests\StructuredData\User');
        $userNode2->setProperty('id', new NodeProperty('integer', 224));
        $userNode2->setProperty('password', new NodeProperty('string', '$1$irVZosm9$eYSZynm/kUm1e6ja3YIya1'));
        $userNode2->setProperty('employer', $companyNode);

        $roleNode = new Node('object', 'Role');
        $roleNode->setMetadata('class', '\Sysgear\Tests\StructuredData\Role');
        $roleNode->setProperty('id', new NodeProperty('integer', 223));
        $roleNode->setProperty('name', new NodeProperty('string', 'admin role'));
        $roleNode->setProperty('company', $companyNode);
        $roleNode->setProperty('members', new NodeCollection(array($userNode2, $userNode)));

        $roleNode2 = new Node('object', 'Role');
        $roleNode2->setMetadata('class', '\Sysgear\Tests\StructuredData\Role');
        $roleNode2->setProperty('id', new NodeProperty('integer', 225));

        $companyNode->setProperty('functions', new NodeCollection(array($roleNode2, $roleNode)));
        $companyNode->setProperty('employees', new NodeCollection(array($userNode, $userNode2)));
        return $companyNode;
    }
}