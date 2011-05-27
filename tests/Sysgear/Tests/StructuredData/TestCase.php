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

require_once 'fixtures/Language.php';
require_once 'fixtures/Locale.php';
require_once 'fixtures/Company.php';
require_once 'fixtures/Role.php';
require_once 'fixtures/User.php';

class TestCase extends \PHPUnit_Framework_TestCase
{
    protected function getIncompleteUser()
    {
        $role1 = new Role();
        $role1->id = 234;
        $role1->name = "guest";

        $user = new User();
        $user->id = 123;
        $user->name = "test";
        $user->password = '$1$irVZosm9$eYSZynm/kUm1e6ja3YIya1';
        $user->roles[] = $role1;

        return $user;
    }

    protected function backupBasicCompany()
    {
        $lang = new Language(1, 'en_EN');
        $locale = new Locale(1, $lang);
        $company = new Company(1, 'rts', $locale);
        $user = new User(1, 'piet', 'bf7s83s', $company);
        $role = new Role(1, 'admin', $company);
        $role->members[] = $user;
        $company->employees[] = $user;
        $company->functions[] = $role;

        return $company;
    }
}