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

require_once 'fixtures/Language.php';
require_once 'fixtures/Locale.php';
require_once 'fixtures/Company.php';
require_once 'fixtures/Role.php';
require_once 'fixtures/User.php';

class ProxyCompany extends Company
{
    public $shouldBeIgnored1 = true;
    protected $shouldBeIgnored2 = true;
}

use Sysgear\StructuredData\Collector\BackupCollector;
class IgnorePropertiesUser extends User
{
    /**
     * {@inheritDoc}
     */
    public function collectStructedData(BackupCollector $backupDataCollector, array $options = array())
    {
        $backupDataCollector->fromObject($this, array(
            'ignore' => array('employer', 'password')));
    }
}

class DoNotScanAndIgnorePropertiesUser extends User
{
    public function addRole(Role $role)
    {
        $this->roles[] = $role;
    }

    /**
     * {@inheritDoc}
     */
    public function collectStructedData(BackupCollector $backupDataCollector, array $options = array())
    {
        $backupDataCollector->fromObject($this, array(
            'doNotDescent' => array('employer', 'roles'),
            'ignore' => array('password', 'sessions', 'name')));
    }
}

class TestCase extends \PHPUnit_Framework_TestCase
{
    protected function inheritedBasicCompany()
    {
        $lang = new Language(1, 'en_EN');
        $locale = new Locale(1, $lang);
        $company = new ProxyCompany(1, 'rts', $locale);
        $user = new User(1, 'piet', 'bf7s83s', $company);
        $role = new Role(1, 'admin', $company);
        $role->members[] = $user;
        $company->functions[] = $role;
        return $company;
    }

    protected function basicCompany()
    {
        $lang = new Language(1, 'en_EN');
        $locale = new Locale(1, $lang);
        $company = new Company(1, 'rts', $locale);
        $user = new User(1, 'piet', 'bf7s83s', $company);
        $role = new Role(1, 'admin', $company);
        $role->members[] = $user;
        $company->functions[] = $role;
        return $company;
    }

    protected function basicUser()
    {
        $lang = new Language(1, 'en_EN');
        $locale = new Locale(1, $lang);
        $company = new Company(1, 'rts', $locale);
        $user = new User(1, 'piet', 'bf7s83s', $company);
        return $user;
    }

    protected function ignoreSomeUserPropertiesUser()
    {
        $lang = new Language(1, 'en_EN');
        $locale = new Locale(1, $lang);
        $company = new Company(1, 'rts', $locale);
        $user = new IgnorePropertiesUser(1, 'piet', 'bf7s83s', $company);
        return $user;
    }

    protected function doNotScanAndIgnoreSomeUserPropertiesUser()
    {
        $lang = new Language(1, 'en_EN');
        $locale = new Locale(1, $lang);
        $company = new Company(1, 'rts', $locale);
        $user = new DoNotScanAndIgnorePropertiesUser(1, 'piet', 'bf7s83s', $company);
        $role = new Role(1, 'admin', $company);
        $role->members[] = $user;
        $company->functions[] = $role;
        $user->addRole($role);
        return $user;
    }
}