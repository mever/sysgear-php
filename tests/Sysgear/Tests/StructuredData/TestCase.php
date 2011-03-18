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
        return '<?xml version="1.0" encoding="UTF-8"?>
<Company id="1" name="rts">
  <Locale id="1">
    <Language id="1" iso639="en_EN"/>
  </Locale>
  <functions>
    <Role id="1" name="admin">
      <members>
        <User id="1" name="piet" password="bf7s83s">
          <Company id="1" name="rts"/>
          <roles/>
        </User>
      </members>
      <Company id="1" name="rts"/>
    </Role>
  </functions>
  <employees>
    <User id="1" name="piet" password="bf7s83s">
      <Company id="1" name="rts"/>
      <roles/>
    </User>
  </employees>
</Company>';
    }
}