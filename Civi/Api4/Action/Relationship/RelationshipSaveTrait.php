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

namespace Civi\Api4\Action\Relationship;

/**
 * @inheritDoc
 */
trait RelationshipSaveTrait {

  /**
   * @inheritDoc
   */
  protected function write(array $items) {
    $result = [];
    foreach ($items as $item) {
      try {
        $result[] = \CRM_Contact_BAO_Relationship::create($item);
      }
      catch (\CRM_Core_Exception $e) {
        if ($e->getErrorCode() === 'duplicate') {
          $result[] = $e->getErrorData();
        }
        else {
          throw $e;
        }
      }
    }
    return $result;
  }

  public function baoToArray($bao, $input) {
    if (is_array($bao)) {
      $bao['id'] = $bao['duplicate_id'] ?? NULL;
      return $bao;
    }
    return parent::baoToArray($bao, $input);
  }

}
