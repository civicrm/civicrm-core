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
 * Disable one or more extensions.
 */
class Disable extends AbstractExtensionBatchAction {

  /**
   * @inheritDoc
   */
  protected function processExtensions(\CRM_Extension_Manager $manager, array $keys): void {
    $manager->disable($keys);
  }

}
