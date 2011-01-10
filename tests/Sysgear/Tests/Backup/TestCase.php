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
<backup>
  <metadata/>
  <content>
    <Company type="object" class="Sysgear\Tests\Backup\Company">
      <id type="integer" value="1"/>
      <name type="string" value="rts"/>
      <Locale type="object" class="Sysgear\Tests\Backup\Locale">
        <id type="integer" value="1"/>
        <Language type="object" class="Sysgear\Tests\Backup\Language">
          <id type="integer" value="1"/>
          <iso639 type="string" value="en_EN"/>
        </Language>
      </Locale>
      <functions type="array">
        <Role type="object" class="Sysgear\Tests\Backup\Role">
          <id type="integer" value="1"/>
          <name type="string" value="admin"/>
          <members type="array">
            <User type="object" class="Sysgear\Tests\Backup\User">
              <id type="integer" value="1"/>
              <name type="string" value="piet"/>
              <password type="string" value="bf7s83s"/>
              <Company type="object" class="Sysgear\Tests\Backup\Company" primaryProperty="id" reference="1"/>
              <roles type="array"/>
            </User>
          </members>
          <Company type="object" class="Sysgear\Tests\Backup\Company" primaryProperty="id" reference="1"/>
        </Role>
      </functions>
      <employees type="array">
        <User type="object" class="Sysgear\Tests\Backup\User">
          <id type="integer" value="1"/>
          <name type="string" value="piet"/>
          <password type="string" value="bf7s83s"/>
          <Company type="object" class="Sysgear\Tests\Backup\Company" primaryProperty="id" reference="1"/>
          <roles type="array"/>
        </User>
      </employees>
    </Company>
  </content>
</backup>';
    }
}