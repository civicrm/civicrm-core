<?php
namespace Civi\Api4\Import;

use Civi\Api4\Generic\DAOUpdateAction;

class Update extends DAOUpdateAction {

  /**
   * Update import table records.
   *
   * @param array $items
   * @return array
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  protected function updateRecords(array $items): array {
    $userJobID = str_replace('Import_', '', $this->_entityName);
    foreach ($items as &$item) {
      $item['user_job_id'] = (int) $userJobID;
    }
    return parent::updateRecords($items);
  }

}
