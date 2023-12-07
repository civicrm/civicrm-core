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

/**
 * This class contains functions for managed entities.
 */
class CRM_Core_BAO_Managed extends CRM_Core_DAO_Managed implements Civi\Core\HookInterface {

  /**
   * Scan core `civicrm/managed` directory for entity declarations.
   *
   * Note: This is similar to the `mgd-php` mixin for extensions, but slightly stricter:
   *  - It doesn't scan any directory outside `managed/`
   *  - It doesn't allow omitting `$params['version']`
   * TODO: Consider making a 2.0 version of the extension mixin using this code, for consistent strictness.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @implements CRM_Utils_Hook::managed
   */
  public static function on_hook_civicrm_managed(\Civi\Core\Event\GenericHookEvent $e) {
    if ($e->modules && !in_array('civicrm', $e->modules, TRUE)) {
      return;
    }
    $mgdFiles = CRM_Utils_File::findFiles(Civi::paths()->getPath('[civicrm.root]/managed'), '*.mgd.php');
    sort($mgdFiles);
    foreach ($mgdFiles as $file) {
      $declarations = include $file;
      foreach ($declarations as $declaration) {
        $e->entities[] = $declaration + ['module' => 'civicrm'];
      }
    }
  }

  /**
   * Callback for hook_civicrm_post().
   * @param \Civi\Core\Event\PostEvent $event
   */
  public static function on_hook_civicrm_post(\Civi\Core\Event\PostEvent $event) {
    // When an entity is deleted by the user, nullify 'entity_id' from corresponding Managed record
    // This tells the ManagedEntity system that the entity was manually deleted,
    // and should be recreated at the discretion of the `update` policy.
    if ($event->action === 'delete' && $event->id && self::isApi4ManagedType($event->entity)) {
      \Civi\Api4\Managed::update(FALSE)
        ->addWhere('entity_type', '=', $event->entity)
        ->addWhere('entity_id', '=', $event->id)
        ->addValue('entity_id', NULL)
        ->execute();
    }
    // When an entity is updated, update the timestamp in corresponding Managed record
    elseif ($event->action === 'edit' && $event->id && self::isApi4ManagedType($event->entity)) {
      if (!array_key_exists('entity_modified_date', self::getSupportedFields())) {
        // During upgrades this column may not exist yet
        return;
      }
      CRM_Core_DAO::executeQuery('UPDATE civicrm_managed SET entity_modified_date = CURRENT_TIMESTAMP WHERE entity_type = %1 AND entity_id = %2', [
        1 => [$event->entity, 'String'],
        2 => [$event->id, 'Integer'],
      ]);
    }
  }

  /**
   * @param string $entityName
   * @return bool
   */
  public static function isApi4ManagedType(string $entityName) {
    $type = \Civi\Api4\Utils\CoreUtil::getInfoItem($entityName, 'type');
    return $type && in_array('ManagedEntity', $type, TRUE);
  }

  /**
   * Options callback for `base_module`.
   * @return array
   */
  public static function getBaseModules(): array {
    $modules = [];
    foreach (CRM_Core_Module::getAll() as $module) {
      if ($module->is_active) {
        $modules[$module->name] = $module->label;
      }
    }
    return $modules;
  }

}
