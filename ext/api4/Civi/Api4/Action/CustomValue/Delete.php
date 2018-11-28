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
namespace Civi\Api4\Action\CustomValue;
use Civi\Api4\Generic\Result;
use Civi\Api4\Utils\CoreUtil;

/**
 * Delete one or more items, based on criteria specified in Where param.
 */
class Delete extends Get {

  /**
   * @inheritDoc
   */
  public function _run(Result $result) {
    $defaults = $this->getParamDefaults();
    if ($defaults['where'] && !array_diff_key($this->where, $defaults['where'])) {
      throw new \API_Exception('Cannot delete with no "where" paramater specified');
    }
    // run the parent action (get) to get the list
    parent::_run($result);
    // Then act on the result
    $customTable = CoreUtil::getCustomTableByName($this->getCustomGroup());
    $ids = [];
    foreach ($result as $item) {
      \CRM_Utils_Hook::pre('delete', $this->getEntity(), $item['id'], \CRM_Core_DAO::$_nullArray);
      \CRM_Utils_SQL_Delete::from($customTable)
        ->where('id = #value')
        ->param('#value', $item['id'])
        ->execute();
      \CRM_Utils_Hook::post('delete', $this->getEntity(), $item['id'], \CRM_Core_DAO::$_nullArray);
      $ids[] = $item['id'];
    }

    $result->exchangeArray($ids);
    return $result;
  }

}
