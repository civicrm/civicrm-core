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

namespace api\v4\Action;

use api\v4\Api4TestBase;
use Civi\Api4\MockArrayEntity;

/**
 * @group headless
 */
class ActionNameTest extends Api4TestBase {

  public function testActionCaseSensitive(): void {
    // This test checks that an action called via STATIC method will internally
    // be converted to the proper case.
    // First: ensure the static method DOES exist. If the class is ever refactored to change this,
    // then this test will no longer be testing what it thinks it's testing!
    $this->assertTrue(method_exists(MockArrayEntity::class, 'getFields'));

    // PHP is case-insensitive so this will work <sigh>
    $action = MOCKarrayENTITY::GETFiELDS();
    // Ensure case was converted internally by the action class
    $this->assertEquals('getFields', $action->getActionName());
    $this->assertEquals('MockArrayEntity', $action->getEntityName());
  }

  public function testActionCaseSensitiveViaMagicMethod(): void {
    // This test checks that an action called via MAGIC method will internally
    // be converted to the proper case.
    // First: ensure the static method does NOT exist. If the class is ever refactored to change this,
    // then this test will no longer be testing what it thinks it's testing!
    $this->assertFalse(method_exists(MockArrayEntity::class, 'doNothing'));

    // Try it with normal case
    $action = MockArrayEntity::doNothing();
    // Ensure case was converted internally by the action class
    $this->assertEquals('doNothing', $action->getActionName());
    $this->assertEquals('MockArrayEntity', $action->getEntityName());

    // PHP is case-insensitive so this will work <sigh>
    $action = moCKarrayENTIty::DOnothING();
    // Ensure case was converted internally by the action class
    $this->assertEquals('doNothing', $action->getActionName());
    $this->assertEquals('MockArrayEntity', $action->getEntityName());
  }

}
