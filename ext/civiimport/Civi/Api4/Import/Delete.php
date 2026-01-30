<?php
namespace Civi\Api4\Import;

use Civi\Api4\Generic\DAODeleteAction;

class Delete extends DAODeleteAction {

  protected function deleteObjects($items) {
    $userJobID = str_replace('Import_', '', $this->_entityName);
    foreach ($items as &$item) {
      $item['_user_job_id'] = (int) $userJobID;
    }
    return parent::deleteObjects($items);
  }

}
