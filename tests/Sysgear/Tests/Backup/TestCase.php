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

class TestCase extends \PHPUnit_Framework_TestCase
{
    protected function backupBasicCompany()
    {
        $lang = new Language(1, 'en_EN');
        $locale = new Locale(1, $lang);
        $company = new Company(1, 'rts', $locale);
        $role = new Role(1, 'admin', $company);
        $user = new User(1, 'piet', 'bf7s83s', $company);
        $company->employees[] = $user;
        $company->functions[] = $role;

        return $company;
    }

    protected function expectedBasicCompanyXml()
    {
        return '<?xml version="1.0" encoding="utf8"?>
<company Mclass="Sysgear\Tests\Backup\Company" Pid="i:1;" Pname="s:3:&quot;rts&quot;;">
  <locale Mclass="Sysgear\Tests\Backup\Locale" Pid="i:1;">
    <language Mclass="Sysgear\Tests\Backup\Language" Pid="i:1;" Piso639="s:5:&quot;en_EN&quot;;"/>
  </locale>
  <functions>
    <role Mclass="Sysgear\Tests\Backup\Role" Pid="i:1;" Pname="s:5:&quot;admin&quot;;">
      <members/>
      <company Mclass="Sysgear\Tests\Backup\Company"/>
    </role>
  </functions>
  <employees>
    <user Mclass="Sysgear\Tests\Backup\User" Pid="i:1;" Pname="s:4:&quot;piet&quot;;" Ppassword="s:7:&quot;bf7s83s&quot;;">
      <company Mclass="Sysgear\Tests\Backup\Company"/>
      <roles/>
    </user>
  </employees>
</company>';
    }
}