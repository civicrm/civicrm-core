<?php

namespace Civi\Test;

/**
 * Interface HookInterface
 * @package Civi\Test
 *
 * This interface allows you to subscribe to hooks as part of the test.
 * Simply create an eponymous hook function (e.g. `hook_civicrm_post()`).
 *
 * @code
 * class MyTest extends \PHPUnit_Framework_TestCase implements \Civi\Test\HookInterface {
 *   public function hook_civicrm_post($op, $objectName, $objectId, &$objectRef) {
 *     echo "Running hook_civicrm_post\n";
 *   }
 * }
 * @endCode
 *
 * At time of writing, there are a few limitations in how HookInterface is handled
 * by CiviTestListener:
 *
 *  - The test must execute in-process (aka HeadlessInterface; aka CIVICRM_UF==UnitTests).
 *    End-to-end tests (multi-process tests) are not supported.
 *  - Early bootstrap hooks (e.g. hook_civicrm_config) are not supported.
 *
 * @see CiviTestListener
 */
interface HookInterface {
}
