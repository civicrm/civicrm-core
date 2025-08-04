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

use Civi\Api4\Generic\Result;
use Civi\Api4\Import;
use Civi\Api4\UserJob;

/**
 * Code shared by Import Save/Update actions.
 *
 * @method getCheckPermissions()
 */
trait ImportProcessTrait {

  /**
   * Get the parser for the import
   *
   * @return \CRM_Import_Parser|\CRM_Contribute_Import_Parser_Contribution
   *
   * @throws \CRM_Core_Exception
   */
  protected function getParser(int $userJobID) {
    $userJob = UserJob::get($this->getCheckPermissions())
      ->addWhere('id', '=', $userJobID)
      ->addSelect('job_type')
      ->execute()
      ->first();
    $parserClass = NULL;
    foreach (\CRM_Core_BAO_UserJob::getTypes() as $userJobType) {
      if ($userJob['job_type'] === $userJobType['id']) {
        $parserClass = $userJobType['class'];
      }
    }
    /** @var \CRM_Import_Parser|\CRM_Contribute_Import_Parser_Contribution $parser */
    $parser = new $parserClass();
    $parser->setUserJobID($userJobID);
    $parser->init();
    return $parser;
  }

  /**
   * Get the selected import rows.
   *
   * @param \Civi\Api4\Generic\Result $result
   *
   * @throws \CRM_Core_Exception
   */
  protected function getImportRows(Result $result): void {
    $userJobID = (int) str_replace('Import_', '', $this->_entityName);
    $this->addSelect('*');
    $importFields = array_keys((array) Import::getFields($userJobID, $this->getCheckPermissions())
      ->addSelect('name')
      ->addWhere('name', 'NOT LIKE', '_%')
      ->execute()
      ->indexBy('name'));
    $importFields[] = '_id';
    $importFields[] = '_entity_id';
    $importFields[] = '_status';
    $this->setSelect($importFields);
    parent::_run($result);
    foreach ($result as &$row) {
      if ($row['_entity_id']) {
        // todo - how should we handle this? Skip, exception. At this case
        // it is non ui accessible so this is good for now.
        throw new \CRM_Core_Exception('Row already imported');
      }
      // Push ID to the end as the get has moved it to the front & order matters here.
      $rowID = $row['_id'];
      unset($row['_id'], $row['_entity_id']);
      $row['_id'] = $rowID;
    }
  }

}
