<?php

namespace Civi\Test;

/**
 * Interface HookInterface
 * @package Civi\Test
 *
 * This interface allows you to subscribe to hooks as part of the test.
 * Simply create an eponymous hook function (e.g. `hook_civicrm_post()`).
 *
 * ```
 * class MyTest extends \PHPUnit_Framework_TestCase implements \Civi\Test\HookInterface {
 *   public function hook_civicrm_post($op, $objectName, $objectId, &$objectRef) {
 *     echo "Running hook_civicrm_post\n";
 *   }
 * }
 * ```
 *
 * Similarly, to subscribe using Symfony-style listeners, create function with the 'on_' prefix:
 *
 * ```
 * class MyTest extends \PHPUnit_Framework_TestCase implements \Civi\Test\HookInterface {
 *   public function on_civi_api_authorize(AuthorizeEvent $e): void {
 *     echo "Running civi.api.authorize\n";
 *   }
 *   public function on_hook_civicrm_post(GenericHookEvent $e): void {
 *     echo "Running hook_civicrm_post\n";
 *   }
 * }
 * ```
 *
 * At time of writing, there are a few limitations in how HookInterface is handled
 * by CiviTestListener:
 *
 *  - The test must execute in-process (aka HeadlessInterface; aka CIVICRM_UF==UnitTests).
 *    End-to-end tests (multi-process tests) are not supported.
 *  - Early bootstrap hooks (e.g. hook_civicrm_config) are not supported.
 *  - This does not support priorities or registering multiple listeners.
 *
 * If you need more advanced registration abilities, consider using `Civi::dispatcher()`
 * or `EventDispatcherInterface`.
 *
 * @see CiviTestListener
 */
interface HookInterface {
}
