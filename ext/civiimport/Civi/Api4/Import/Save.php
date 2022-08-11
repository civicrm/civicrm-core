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

namespace Civi\Api4\Import;

use Civi\Api4\Generic\DAOSaveAction;
use Civi\Api4\Generic\Result;

class Save extends DAOSaveAction {

  /**
   * Import save action.
   *
   * This is copied from `DAOSaveAction` to add the user_job_id to the array & to
   * the reference to '_id' not 'id'.
   *
   * @inheritDoc
   */
  public function _run(Result $result): void {
    $userJobID = str_replace('Import_', '', $this->_entityName);
    $this->defaults['user_job_id'] = (int) $userJobID;
    parent::_run($result);
  }

}
