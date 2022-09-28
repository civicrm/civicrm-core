<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace api\v4\Entity;

use Civi\Api4\Setting;
use Civi\Api4\StatusPreference;
use Civi\Api4\System;
use api\v4\UnitTestCase;

/**
 * @group headless
 */
class SystemTest extends UnitTestCase {

  public function testSystemCheck() {
    $origEnv = \CRM_Core_Config::environment();
    $hooks = \CRM_Utils_Hook::singleton();
    $hooks->setHook('civicrm_check', [$this, 'hook_civicrm_check']);

    // Test on non-prod site
    Setting::set()->addValue('environment', 'Development')->setCheckPermissions(FALSE)->execute();

    StatusPreference::delete()->setCheckPermissions(FALSE)->addWhere('name', '=', 'checkLastCron')->execute();

    // Won't run on non-prod site without $includeDisabled
    $check = System::check()->addWhere('name', '=', 'checkLastCron')->execute();
    // Will have skipped our hook because name matched a core check
    $this->assertCount(0, $check);

    // This should only run the php check
    $check = System::check()->addWhere('name', '=', 'checkPhpVersion')->setIncludeDisabled(TRUE)->execute();
    // Hook should have been skipped because name clause was fulfilled
    $this->assertCount(1, $check);

    // Ensure cron check has not run
    $this->assertCount(0, StatusPreference::get(FALSE)->addWhere('name', '=', 'checkLastCron')->execute());

    // Will run on non-prod site with $includeDisabled.
    // Giving a more-specific name will run all checks with less-specific names too
    $check = System::check()->addWhere('name', '=', 'checkLastCronAbc')->setIncludeDisabled(TRUE)->execute()->indexBy('name');
    // Will have run our hook too because name wasn't an exact match
    $this->assertCount(2, $check);
    $this->assertEquals('Ok', $check['hook_civicrm_check']['title']);

    // We know the cron check has run because it would have left a record marked 'new'
    $record = StatusPreference::get(FALSE)->addWhere('name', '=', 'checkLastCron')->execute()->first();
    $this->assertEquals('new', $record['prefs']);

    // Restore env
    Setting::set()->addValue('environment', $origEnv)->setCheckPermissions(FALSE)->execute();
    $hooks->reset();
  }

  public function hook_civicrm_check(&$messages, $statusNames, $includeDisabled) {
    $messages[] = new \CRM_Utils_Check_Message(
      __FUNCTION__,
      'Hook running',
      'Ok',
      \Psr\Log\LogLevel::DEBUG
    );
  }

}
