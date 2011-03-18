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
    public function collectStructedData(BackupCollector $backupDataCollector)
    {
        $backupDataCollector->fromBackupable($this, array(
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
    public function collectStructedData(BackupCollector $backupDataCollector)
    {
        $backupDataCollector->fromBackupable($this, array(
            'doNotFollow' => array('employer', 'roles'),
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

    protected function getHashes(Company $comp = null)
    {
        $empty = (null === $comp) ? 'N/A' : null;

        $compHash = $empty ?: spl_object_hash($comp);
        $localeHash = $empty ?: spl_object_hash($comp->locale);
        $langHash = $empty ?: spl_object_hash($comp->locale->language);
        $userHash = $empty ?: spl_object_hash($comp->getEmployee(0));
        $roleHash = $empty ?: array_key_exists(0, $comp->functions) ?
            spl_object_hash($comp->functions[0]) : $empty;

        return array($compHash, $localeHash, $langHash, $userHash, $roleHash);
    }

    protected function expectedInheritedBasicCompanyXml(Company $comp, $onlyImplementor)
    {
        list($compHash, $localeHash, $langHash, $userHash, $roleHash) = $this->getHashes($comp);
        $className = 'ProxyCompany';
        $extraProperties = '<shouldBeIgnored1 type="boolean" value="1"/>' .
            "\n      <shouldBeIgnored2 type=\"boolean\" value=\"1\"/>\n      ";

        if ($onlyImplementor) {
            $className = 'Company';
            $extraProperties = '';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>
<backup>
  <metadata/>
  <content>
    <'.$className.' type="object" class="Sysgear\\Tests\\Backup\\'.$className.'" id="'.$compHash.'">
      '.$extraProperties.'<id type="integer" value="1"/>
      <name type="string" value="rts"/>
      <locale type="object" class="Sysgear\\Tests\\Backup\\Locale" id="'.$localeHash.'">
        <id type="integer" value="1"/>
        <language type="object" class="Sysgear\\Tests\Backup\\Language" id="'.$langHash.'">
          <id type="integer" value="1"/>
          <iso639 type="string" value="en_EN"/>
        </language>
      </locale>
      <functions type="array">
        <Role type="object" class="Sysgear\\Tests\\Backup\\Role" id="'.$roleHash.'">
          <id type="integer" value="1"/>
          <name type="string" value="admin"/>
          <members type="array">
            <User type="object" class="Sysgear\\Tests\\Backup\\User" id="'.$userHash.'">
              <id type="integer" value="1"/>
              <name type="string" value="piet"/>
              <password type="string" value="bf7s83s"/>
              <employer type="object" class="Sysgear\\Tests\\Backup\\'.$className.'" ref="'.$compHash.'"/>
              <roles type="array"/>
              <sessions type="array"/>
            </User>
          </members>
          <company type="object" class="Sysgear\\Tests\\Backup\\'.$className.'" ref="'.$compHash.'"/>
        </Role>
      </functions>
      <employees type="array">
        <User type="object" class="Sysgear\\Tests\\Backup\\User" ref="'.$userHash.'"/>
      </employees>
    </'.$className.'>
  </content>
</backup>';
    }

    protected function expectedBasicCompanyXml(Company $comp = null)
    {
        list($compHash, $localeHash, $langHash, $userHash, $roleHash) = $this->getHashes($comp);
        return '<?xml version="1.0" encoding="UTF-8"?>
<backup>
  <metadata/>
  <content>
    <Company type="object" class="Sysgear\\Tests\\Backup\\Company" id="'.$compHash.'">
      <id type="integer" value="1"/>
      <name type="string" value="rts"/>
      <locale type="object" class="Sysgear\\Tests\\Backup\\Locale" id="'.$localeHash.'">
        <id type="integer" value="1"/>
        <language type="object" class="Sysgear\\Tests\\Backup\\Language" id="'.$langHash.'">
          <id type="integer" value="1"/>
          <iso639 type="string" value="en_EN"/>
        </language>
      </locale>
      <functions type="array">
        <Role type="object" class="Sysgear\\Tests\\Backup\\Role" id="'.$roleHash.'">
          <id type="integer" value="1"/>
          <name type="string" value="admin"/>
          <members type="array">
            <User type="object" class="Sysgear\\Tests\\Backup\\User" id="'.$userHash.'">
              <id type="integer" value="1"/>
              <name type="string" value="piet"/>
              <password type="string" value="bf7s83s"/>
              <employer type="object" class="Sysgear\\Tests\\Backup\\Company" ref="'.$compHash.'"/>
              <roles type="array"/>
              <sessions type="array"/>
            </User>
          </members>
          <company type="object" class="Sysgear\\Tests\\Backup\\Company" ref="'.$compHash.'"/>
        </Role>
      </functions>
      <employees type="array">
        <User type="object" class="Sysgear\\Tests\\Backup\\User" ref="'.$userHash.'"/>
      </employees>
    </Company>
  </content>
</backup>';
    }

    protected function expectedBasicUserXml(Company $comp = null)
    {
        list($compHash, $localeHash, $langHash, $userHash) = $this->getHashes($comp);
        return '<?xml version="1.0" encoding="UTF-8"?>
<backup>
  <metadata/>
  <content>
    <User type="object" class="Sysgear\Tests\Backup\User" id="'.$userHash.'">
      <id type="integer" value="1"/>
      <name type="string" value="piet"/>
      <password type="string" value="bf7s83s"/>
      <employer type="object" class="Sysgear\Tests\Backup\Company" id="'.$compHash.'">
        <id type="integer" value="1"/>
        <name type="string" value="rts"/>
        <locale type="object" class="Sysgear\Tests\Backup\Locale" id="'.$localeHash.'">
          <id type="integer" value="1"/>
          <language type="object" class="Sysgear\Tests\Backup\Language" id="'.$langHash.'">
            <id type="integer" value="1"/>
            <iso639 type="string" value="en_EN"/>
          </language>
        </locale>
        <functions type="array"/>
        <employees type="array">
          <User type="object" class="Sysgear\Tests\Backup\User" ref="'.$userHash.'"/>
        </employees>
      </employer>
      <roles type="array"/>
      <sessions type="array"/>
    </User>
  </content>
</backup>';
    }

    protected function expectedIgnoreSomeUserPropertiesXml(Company $comp = null)
    {
        list($compHash, $localeHash, $langHash, $userHash) = $this->getHashes($comp);
        return '<?xml version="1.0" encoding="UTF-8"?>
<backup>
  <metadata/>
  <content>
    <IgnorePropertiesUser type="object" class="Sysgear\Tests\Backup\IgnorePropertiesUser" id="'.$userHash.'">
      <id type="integer" value="1"/>
      <roles type="array"/>
      <sessions type="array"/>
    </IgnorePropertiesUser>
  </content>
</backup>';
    }

    protected function expectedDoNotScanAndIgnoreSomeUserPropertiesXml(Company $comp = null)
    {
        list($compHash, $localeHash, $langHash, $userHash, $roleHash) = $this->getHashes($comp);
        return '<?xml version="1.0" encoding="UTF-8"?>
<backup>
  <metadata/>
  <content>
    <DoNotScanAndIgnorePropertiesUser type="object" class="Sysgear\Tests\Backup\DoNotScanAndIgnorePropertiesUser" id="'.$userHash.'">
      <id type="integer" value="1"/>
      <employer type="object" class="Sysgear\Tests\Backup\Company" id="'.$compHash.'">
        <id type="integer" value="1"/>
        <name type="string" value="rts"/>
      </employer>
      <roles type="array">
        <Role type="object" class="Sysgear\Tests\Backup\Role" id="'.$roleHash.'">
          <id type="integer" value="1"/>
          <name type="string" value="admin"/>
        </Role>
      </roles>
    </DoNotScanAndIgnorePropertiesUser>
  </content>
</backup>';
    }
}