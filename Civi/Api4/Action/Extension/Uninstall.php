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

namespace Civi\Api4\Action\Extension;

/**
 * Uninstall one or more extensions (disabling them first if enabled).
 */
class Uninstall extends AbstractExtensionBatchAction {

  /**
   * @inheritDoc
   */
  protected function processExtensions(\CRM_Extension_Manager $manager, array $keys): void {
    $notUninstallable = array_intersect($keys, \CRM_Extension_System::singleton()->getMapper()->getKeysByTag('component'));
    if ($notUninstallable) {
      throw new \CRM_Extension_Exception('Cannot uninstall extensions which are tagged as components: ' . implode(', ', $notUninstallable));
    }

    $statuses = $manager->getStatuses();
    $enabledKeys = array_filter($keys, fn($key) => in_array($statuses[$key] ?? NULL, [
      \CRM_Extension_Manager::STATUS_INSTALLED,
      \CRM_Extension_Manager::STATUS_INSTALLED_MISSING,
    ], TRUE));
    if ($enabledKeys) {
      $manager->disable($enabledKeys);
    }

    $manager->uninstall($keys);
  }

}
