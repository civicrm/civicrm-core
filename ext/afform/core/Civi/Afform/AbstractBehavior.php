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

namespace Civi\Afform;

use Civi\Core\Service\AutoService;

/**
 * Base class for Afform Behaviors
 *
 * A Behavior is a collection of configuration and functionality for an entity on a form.
 * By implementing the following methods, the Behavior describes which entities it can act on,
 * and what modes it operates in.
 *
 * The mode selector will automatically appear in the Afform Gui, allowing the user to enable
 * behaviors by selecting a mode.
 *
 * To enact its functionality, a behavior class can listen to any Civi hook or event.
 * (the simplist way is by implementing `HookInterface`,
 * or for finer control of the order of events, try the `EventSubscriberInterface`).
 *
 * Notable events often used by Behaviors include `civi.afform.prefill` and `civi.afform.submit`.
 *
 * Note: Behavior classes can be in any namespace, but if you want the convenience of the
 * `afform-behavior-php` autoloader, they must be in `\Civi\Afform\Behavior`.
 */
abstract class AbstractBehavior extends AutoService implements BehaviorInterface {

  /**
   * Optional description of the behavior
   *
   * @return string|null
   */
  public static function getDescription():? string {
    return NULL;
  }

  /**
   * Dashed name, name of entity attribute for selected mode
   * @return string
   */
  public static function getKey():string {
    $behaviorName = substr(static::class, strrpos(static::class, '\\') + 1);
    return \CRM_Utils_String::convertStringToDash($behaviorName);
  }

}
