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

namespace api\v4;

use Civi\API\Exception\NotImplementedException;
use Civi\API\Request;
use Civi\Api4\Entity;
use Civi\Api4\Utils\CoreUtil;

/**
 * Scans every registered Api4 entity+action (core and installed
 * extensions) and checks that every param the action declares as
 * "required" is safe to read via its getter on a freshly-constructed,
 * nothing-set instance.
 *
 * A required param declared as a typed PHP property with no default value
 * (e.g. `protected int $foo;`) is uninitialized in that state, and PHP
 * throws a raw \Error ("must not be accessed before initialization") the
 * moment anything reads it - which is exactly what
 * Civi\Api4\Event\Subscriber\ValidateFieldsSubscriber does internally
 * (call the getter, check for NULL) to produce the API's normal
 * CRM_Core_Exception("Parameter ... is required."). That crash pre-empts
 * the friendly exception with a much less helpful internal error.
 *
 * @group headless
 */
class RequiredParamsSafeTest extends Api4TestBase {

  public function setUp(): void {
    // Enable all components so component-gated entities/actions
    // (Event, Campaign, etc) are included in the scan.
    \CRM_Core_BAO_ConfigSetting::enableAllComponents();
    parent::setUp();
  }

  public function testRequiredParamsAreSafeToReadBeforeBeingSet(): void {
    $failures = [];

    $entityNames = Entity::get(FALSE)->execute()->column('name');
    foreach ($entityNames as $entityName) {
      $entityClass = CoreUtil::getApiClass($entityName);
      if (!$entityClass) {
        continue;
      }
      try {
        $actionNames = $entityClass::getActions(FALSE)->execute()->column('name');
      }
      catch (\Throwable $e) {
        // Some entities (e.g. dynamic ones needing class_args) can't
        // enumerate their own actions generically - not what this test
        // is checking, so skip rather than fail.
        continue;
      }

      foreach ($actionNames as $actionName) {
        try {
          $action = Request::create($entityName, $actionName, ['version' => 4]);
        }
        catch (NotImplementedException $e) {
          continue;
        }
        catch (\Throwable $e) {
          continue;
        }

        foreach ($action->getParamInfo() as $param => $info) {
          if (empty($info['required'])) {
            continue;
          }
          $getter = 'get' . ucfirst($param);
          try {
            $action->$getter();
          }
          catch (\Error $e) {
            $failures[] = sprintf(
              '%s::%s() required param "%s" is not safely readable before being set (%s)',
              $entityName,
              $actionName,
              $param,
              $e->getMessage()
            );
          }
        }
      }
    }

    $this->assertEmpty($failures, "The following required params crash with a raw \\Error instead of the friendly \"Parameter ... is required.\" CRM_Core_Exception - give them a nullable type with an explicit `= NULL` default:\n" . implode("\n", $failures));
  }

}
