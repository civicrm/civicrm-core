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
 * (e.g. by using `EventSubscriberInterface`).
 *
 * Notable events used by Behaviors include `civi.afform.validate`, `civi.afform.prefill` and `civi.afform.submit`.
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
   * Optional template for configuring the behavior in the AfformGuiEditor
   *
   * @return string|null
   */
  public static function getTemplate(): ?string {
    return NULL;
  }

  /**
   * Default mode. If set then mode will not be de-selectable.
   *
   * @return string|null
   */
  public static function getDefaultMode(): ?string {
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

  public static function getAttributes(): array {
    return [
      static::getKey() => 'text',
    ];
  }

}
