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

use Civi\Api4\Generic\DAOGetAction;
use Civi\Api4\Generic\Result;

class Import extends DAOGetAction {

  use ImportProcessTrait;

  /**
   * @throws \CRM_Core_Exception
   */
  public function _run(Result $result): void {
    $userJobID = (int) str_replace('Import_', '', $this->_entityName);
    $where = $this->where;
    $this->getImportRows($result);
    $parser = $this->getParser($userJobID);
    foreach ($result as $row) {
      if (!$parser->validateRow($row)) {
        continue;
      }
      $parser->import($row);
    }
    $parser->doPostImportActions();

    // Re-fetch the validated result with updated messages.
    $this->where = $where;
    $this->addSelect('*');
    parent::_run($result);
  }

}
