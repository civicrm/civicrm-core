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
 * $Id$
 *
 */


namespace Civi\Api4\Generic;

/**
 * Create or update one or more $ENTITIES.
 *
 * If creating more than one $ENTITY with similar values, use the `defaults` param.
 *
 * Set `reload` if you need the api to return complete records for each saved $ENTITY.
 */
class DAOSaveAction extends AbstractSaveAction {
  use Traits\DAOActionTrait;

  /**
   * @inheritDoc
   */
  public function _run(Result $result) {
    foreach ($this->records as &$record) {
      $record += $this->defaults;
      if (empty($record['id'])) {
        $this->fillDefaults($record);
      }
    }
    $this->validateValues();

    $resultArray = $this->writeObjects($this->records);

    $result->exchangeArray($resultArray);
  }

}
