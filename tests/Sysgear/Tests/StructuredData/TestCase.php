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

    protected function expectedBasicCompanyXml()
    {
        return '<?xml version="1.0" encoding="utf8"?>
<Company Mclass="Sysgear\Tests\StructuredData\Company" Pid="i:1;" Pname="s:3:&quot;rts&quot;;">
  <Locale Mclass="Sysgear\Tests\StructuredData\Locale" Pid="i:1;">
    <Language Mclass="Sysgear\Tests\StructuredData\Language" Pid="i:1;" Piso639="s:5:&quot;en_EN&quot;;"/>
  </Locale>
  <functions>
    <Role Mclass="Sysgear\Tests\StructuredData\Role" Pid="i:1;" Pname="s:5:&quot;admin&quot;;">
      <members>
        <User Mclass="Sysgear\Tests\StructuredData\User" Pid="i:1;" Pname="s:4:&quot;piet&quot;;" Ppassword="s:7:&quot;bf7s83s&quot;;">
          <Company Mclass="Sysgear\Tests\StructuredData\Company" Pid="i:1;" Pname="s:3:&quot;rts&quot;;"/>
          <roles/>
        </User>
      </members>
      <Company Mclass="Sysgear\Tests\StructuredData\Company" Pid="i:1;" Pname="s:3:&quot;rts&quot;;"/>
    </Role>
  </functions>
  <employees>
    <User Mclass="Sysgear\Tests\StructuredData\User" Pid="i:1;" Pname="s:4:&quot;piet&quot;;" Ppassword="s:7:&quot;bf7s83s&quot;;">
      <Company Mclass="Sysgear\Tests\StructuredData\Company" Pid="i:1;" Pname="s:3:&quot;rts&quot;;"/>
      <roles/>
    </User>
  </employees>
</Company>';
    }
}