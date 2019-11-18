<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */


namespace Civi\Api4\Generic;

use Civi\API\Exception\NotImplementedException;
use Civi\Api4\Utils\ActionUtil;

/**
 * Given a set of records, will appropriately update the database.
 *
 * @method $this setRecords(array $records) Array of records.
 * @method $this addRecord($record) Add a record to update.
 * @method array getRecords()
 * @method $this setDefaults(array $defaults) Array of defaults.
 * @method $this addDefault($name, $value) Add a default value.
 * @method array getDefaults()
 * @method $this setReload(bool $reload) Specify whether complete objects will be returned after saving.
 * @method bool getReload()
 */
class BasicReplaceAction extends AbstractBatchAction {

  /**
   * Array of records.
   *
   * Should be in the same format as returned by Get.
   *
   * @var array
   * @required
   */
  protected $records = [];

  /**
   * Array of default values.
   *
   * Will be merged into $records before saving.
   *
   * @var array
   */
  protected $defaults = [];

  /**
   * Reload records after saving.
   *
   * By default this api typically returns partial records containing only the fields
   * that were updated. Set reload to TRUE to do an additional lookup after saving
   * to return complete records.
   *
   * @var bool
   */
  protected $reload = FALSE;

  /**
   * @return \Civi\Api4\Result\ReplaceResult
   */
  public function execute() {
    return parent::execute();
  }

  /**
   * @inheritDoc
   */
  public function _run(Result $result) {
    $items = $this->getBatchRecords();

    // Copy defaults from where clause if the operator is =
    foreach ($this->where as $clause) {
      if (is_array($clause) && $clause[1] === '=') {
        $this->defaults[$clause[0]] = $clause[2];
      }
    }

    $idField = $this->getSelect()[0];
    $toDelete = array_diff_key(array_column($items, NULL, $idField), array_flip(array_filter(\CRM_Utils_Array::collect($idField, $this->records))));

    // Try to delegate to the Save action
    try {
      $saveAction = ActionUtil::getAction($this->getEntityName(), 'save');
      $saveAction
        ->setCheckPermissions($this->getCheckPermissions())
        ->setReload($this->reload)
        ->setRecords($this->records)
        ->setDefaults($this->defaults);
      $result->exchangeArray((array) $saveAction->execute());
    }
    // Fall back on Create/Update if Save doesn't exist
    catch (NotImplementedException $e) {
      foreach ($this->records as $record) {
        $record += $this->defaults;
        if (!empty($record[$idField])) {
          $result[] = civicrm_api4($this->getEntityName(), 'update', [
            'reload' => $this->reload,
            'where' => [[$idField, '=', $record[$idField]]],
            'values' => $record,
            'checkPermissions' => $this->getCheckPermissions(),
          ])->first();
        }
        else {
          $result[] = civicrm_api4($this->getEntityName(), 'create', [
            'values' => $record,
            'checkPermissions' => $this->getCheckPermissions(),
          ])->first();
        }
      }
    }

    if ($toDelete) {
      $result->deleted = (array) civicrm_api4($this->getEntityName(), 'delete', [
        'where' => [[$idField, 'IN', array_keys($toDelete)]],
        'checkPermissions' => $this->getCheckPermissions(),
      ]);
    }
  }

}
