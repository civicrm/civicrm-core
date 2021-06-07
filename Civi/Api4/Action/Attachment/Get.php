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
 */


namespace Civi\Api4\Action\Attachment;

use Civi\Api4\Generic\Result;
use Civi\Api4\Query\Api4SelectQuery;

/**
 * Get the names & docblocks of all APIv4 entities.
 *
 * Scans for api entities in core, enabled components & enabled extensions.
 *
 * Also includes pseudo-entities from multi-record custom groups.
 */
class Get extends \Civi\Api4\Generic\DAOGetAction {

  /**
   * @param \Civi\Api4\Generic\Result $result
   */
  protected function getObjects(Result $result) {
    $getCount = in_array('row_count', $this->getSelect());
    $onlyCount = $this->getSelect() === ['row_count'];

    if (!$onlyCount) {
      $query = new Api4SelectQuery($this);
      $rows = $query->run();
      \CRM_Utils_API_HTMLInputCoder::singleton()->decodeRows($rows);
      foreach ($rows as $key => $row) {
        $rows[$key] = \CRM_Core_BAO_Attachment::formatResult($row, $this->getCheckPermissions(), $this->getSelect());
      }
      $result->exchangeArray($rows);
      // No need to fetch count if we got a result set below the limit
      if (!$this->getLimit() || count($rows) < $this->getLimit()) {
        $result->rowCount = count($rows) + $this->getOffset();
        $getCount = FALSE;
      }
    }
    if ($getCount) {
      $query = new Api4SelectQuery($this);
      $result->rowCount = $query->getCount();
    }
  }

}
