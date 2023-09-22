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

namespace Civi\Api4\Action\CustomValue;

/**
 * Update one or more records with new values. Use the where clause to select them.
 */
class Update extends \Civi\Api4\Generic\DAOUpdateAction {
  use \Civi\Api4\Generic\Traits\CustomValueActionTrait;

  /**
   * Ensure entity_id is returned by getBatchRecords()
   * @return string[]
   */
  protected function getSelect() {
    return ['id', 'entity_id'];
  }

}
