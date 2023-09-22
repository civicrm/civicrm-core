<?php

namespace Civi\Core;

/**
 * Interface HookInterface
 * @package Civi\Core
 *
 * This interface allows CRM_BAO classes to subscribe to hooks.
 * Simply create an eponymous hook function (e.g. `hook_civicrm_post()`).
 *
 * ```
 * class CRM_Foo_BAO_Bar implements \Civi\Core\HookInterface {
 *   public static function hook_civicrm_post($op, $objectName, $objectId, &$objectRef) {
 *     echo "Running hook_civicrm_post\n";
 *   }
 * }
 * ```
 *
 * Similarly, to subscribe using Symfony-style listeners, create function with the 'on_' prefix:
 *
 * ```
 * class CRM_Foo_BAO_Bar implements \Civi\Core\HookInterface {
 *   public static function on_civi_api_authorize(AuthorizeEvent $e): void {
 *     echo "Running civi.api.authorize\n";
 *   }
 *   public static function on_hook_civicrm_post(PostEvent $e): void {
 *     echo "Running hook_civicrm_post\n";
 *   }
 * }
 * ```
 *
 * If you need more advanced registration abilities, consider using `Civi::dispatcher()`
 * or `EventDispatcherInterface`.
 *
 * @serviceTags event_subscriber
 * @see \Civi\Core\Event\EventScanner::findListeners
 */
interface HookInterface {
}
