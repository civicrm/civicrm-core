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

namespace Civi\Api4\Generic;

/**
 * Update one or more $ENTITY with new values.
 *
 * Use the `where` clause to bulk update multiple records,
 * or supply 'id' as a value to update a single record.
 */
class DAOUpdateAction extends AbstractUpdateAction {
  use Traits\DAOActionTrait;

  /**
   * @param array $items
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function updateRecords(array $items): array {
    return $this->writeObjects($items);
  }

}
