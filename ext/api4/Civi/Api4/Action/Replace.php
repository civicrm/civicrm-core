<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
namespace Civi\Api4\Action;
use Civi\Api4\Generic\Result;

/**
 * Given a set of records, will appropriately update the database.
 *
 * @method $this setRecords(array $records) Array of records.
 * @method $this addRecord($record) Add a record to update.
 */
class Replace extends Get {

  /**
   * Array of records.
   *
   * @required
   * @var array
   */
  protected $records = [];

  /**
   * Array of select elements
   *
   * @required
   * @var array
   */
  protected $select = ['id'];

  /**
   * @inheritDoc
   */
  public function _run(Result $result) {
    // First run the parent action (get)
    parent::_run($result);

    $toDelete = (array) $result->indexBy('id');
    $saved = [];

    // Save all items
    foreach ($this->records as $idx => $record) {
      $saved[] = $this->writeObject($record);
      if (!empty($record['id'])) {
        unset($toDelete[$record['id']]);
      }
    }

    if ($toDelete) {
      civicrm_api4($this->getEntity(), 'Delete', ['where' => [['id', 'IN', array_keys($toDelete)]]]);
    }
    $result->deleted = array_keys($toDelete);
    $result->exchangeArray($saved);
  }

  /**
   * @inheritDoc
   */
  public function getParamInfo($param = NULL) {
    $info = parent::getParamInfo($param);
    if (!$param) {
      // This action doesn't actually let you select fields.
      unset($info['select']);
    }
    return $info;
  }

}
