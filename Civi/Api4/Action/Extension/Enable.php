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
 * Enable (or install) one or more extensions and their dependencies.
 */
class Enable extends AbstractExtensionBatchAction {

  /**
   * @inheritDoc
   */
  protected function processExtensions(\CRM_Extension_Manager $manager, array $keys): void {
    $statuses = $manager->getStatuses();
    // Skip already-installed extensions
    $toInstall = array_filter($keys, fn($key) => ($statuses[$key] ?? NULL) !== \CRM_Extension_Manager::STATUS_INSTALLED);
    if ($toInstall) {
      $manager->install($manager->findInstallRequirements($toInstall));
    }
  }

}
