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

namespace Civi\Api4\Action\Contribution;

/**
 * Code shared by Contribution create/update/save actions
 */
trait ContributionSaveTrait {

  /**
   * @inheritDoc
   */
  protected function write(array $items) {
    foreach ($items as &$item) {
      // Required by Contribution BAO
      $item['skipCleanMoney'] = TRUE;
      if ($this->getActionName() === 'create' && isset($item['contribution_status_id'])
        && $item['contribution_status_id'] !== \CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending'))
      {
        throw new \CRM_Core_Exception('It is not supported to call Contribution::create with a status other than Pending. Use the Payment::create API to create a payment and update the status');
      }
    }
    return parent::write($items);
  }

}
