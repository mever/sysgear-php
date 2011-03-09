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
        $company->addEmployee($user);
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
        $company->addEmployee($user);
        $company->functions[] = $role;
        return $company;
    }

    protected function expectedInheritedBasicCompanyXml(Company $comp, $onlyImplementor)
    {
        $compHash = spl_object_hash($comp);
        $className = 'ProxyCompany';
        $extraProperties = '<shouldBeIgnored1 type="boolean" value="1"/>' .
            "\n      <shouldBeIgnored2 type=\"boolean\" value=\"1\"/>\n      ";

        if ($onlyImplementor) {
            $className = 'Company';
            $extraProperties = '';
        }
        return '<?xml version="1.0" encoding="utf8"?>
<backup>
  <metadata/>
  <content>
    <'.$className.' type="object" class="Sysgear\\Tests\\Backup\\'.$className.'" id="'.$compHash.'">
      '.$extraProperties.'<id type="integer" value="1"/>
      <name type="string" value="rts"/>
      <locale type="object" class="Sysgear\\Tests\\Backup\\Locale" id="'.spl_object_hash($comp->locale).'">
        <id type="integer" value="1"/>
        <language type="object" class="Sysgear\\Tests\Backup\\Language" id="'.spl_object_hash($comp->locale->language).'">
          <id type="integer" value="1"/>
          <iso639 type="string" value="en_EN"/>
        </language>
      </locale>
      <functions type="array">
        <Role type="object" class="Sysgear\\Tests\\Backup\\Role" id="'.spl_object_hash($comp->functions[0]).'">
          <id type="integer" value="1"/>
          <name type="string" value="admin"/>
          <members type="array">
            <User type="object" class="Sysgear\\Tests\\Backup\\User" id="'.spl_object_hash($comp->functions[0]->members[0]).'">
              <id type="integer" value="1"/>
              <name type="string" value="piet"/>
              <password type="string" value="bf7s83s"/>
              <employer type="object" class="Sysgear\\Tests\\Backup\\'.$className.'" ref="'.$compHash.'"/>
              <roles type="array"/>
            </User>
          </members>
          <company type="object" class="Sysgear\\Tests\\Backup\\'.$className.'" ref="'.$compHash.'"/>
        </Role>
      </functions>
      <employees type="array">
        <User type="object" class="Sysgear\\Tests\\Backup\\User" id="'.spl_object_hash($comp->getEmployee(0)).'">
          <id type="integer" value="1"/>
          <name type="string" value="piet"/>
          <password type="string" value="bf7s83s"/>
          <employer type="object" class="Sysgear\\Tests\\Backup\\'.$className.'" ref="'.$compHash.'"/>
          <roles type="array"/>
        </User>
      </employees>
    </'.$className.'>
  </content>
</backup>';
    }

    protected function expectedBasicCompanyXml(Company $comp = null)
    {
        $empty = (null === $comp) ? 'N/A' : null;
        $compHash = $empty ?: spl_object_hash($comp);
        return '<?xml version="1.0" encoding="utf8"?>
<backup>
  <metadata/>
  <content>
    <Company type="object" class="Sysgear\\Tests\\Backup\\Company" id="'.$compHash.'">
      <id type="integer" value="1"/>
      <name type="string" value="rts"/>
      <locale type="object" class="Sysgear\\Tests\\Backup\\Locale" id="'.($empty ?: spl_object_hash($comp->locale)).'">
        <id type="integer" value="1"/>
        <language type="object" class="Sysgear\\Tests\\Backup\\Language" id="'.($empty ?: spl_object_hash($comp->locale->language)).'">
          <id type="integer" value="1"/>
          <iso639 type="string" value="en_EN"/>
        </language>
      </locale>
      <functions type="array">
        <Role type="object" class="Sysgear\\Tests\\Backup\\Role" id="'.($empty ?: spl_object_hash($comp->functions[0])).'">
          <id type="integer" value="1"/>
          <name type="string" value="admin"/>
          <members type="array">
            <User type="object" class="Sysgear\\Tests\\Backup\\User" id="'.($empty ?: spl_object_hash($comp->functions[0]->members[0])).'">
              <id type="integer" value="1"/>
              <name type="string" value="piet"/>
              <password type="string" value="bf7s83s"/>
              <employer type="object" class="Sysgear\\Tests\\Backup\\Company" ref="'.$compHash.'"/>
              <roles type="array"/>
            </User>
          </members>
          <company type="object" class="Sysgear\\Tests\\Backup\\Company" ref="'.$compHash.'"/>
        </Role>
      </functions>
      <employees type="array">
        <User type="object" class="Sysgear\\Tests\\Backup\\User" id="'.($empty ?: spl_object_hash($comp->getEmployee(0))).'">
          <id type="integer" value="1"/>
          <name type="string" value="piet"/>
          <password type="string" value="bf7s83s"/>
          <employer type="object" class="Sysgear\\Tests\\Backup\\Company" ref="'.$compHash.'"/>
          <roles type="array"/>
        </User>
      </employees>
    </Company>
  </content>
</backup>';
    }
}