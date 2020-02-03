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
 * Create a new $ENTITY from supplied values.
 *
 * This action will create 1 new $ENTITY.
 * It cannot be used to update existing $ENTITIES; use the `Update` or `Replace` actions for that.
 */
class DAOCreateAction extends AbstractCreateAction {
  use Traits\DAOActionTrait;

  /**
   * @inheritDoc
   */
  public function _run(Result $result) {
    $this->validateValues();
    $params = $this->values;
    $this->fillDefaults($params);

    $resultArray = $this->writeObjects([$params]);

    $result->exchangeArray($resultArray);
  }

  /**
   * @throws \API_Exception
   */
  protected function validateValues() {
    if (!empty($this->values['id'])) {
      throw new \API_Exception('Cannot pass id to Create action. Use Update action instead.');
    }
    parent::validateValues();
  }

}
