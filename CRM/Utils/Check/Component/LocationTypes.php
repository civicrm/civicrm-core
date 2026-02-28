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
class CRM_Utils_Check_Component_LocationTypes extends CRM_Utils_Check_Component {

  /**
   * Display warning about invalid priceFields
   *
   * @return CRM_Utils_Check_Message[]
   * @throws \CRM_Core_Exception
   */
  public function checkPriceFields(): array {
    $messages = [];
    if (!CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_location_type WHERE is_active = 1 AND is_default = 1')) {
      $url = CRM_Utils_System::url('civicrm/admin/locationType', [
        'reset' => 1,
      ]);
      $msg = ts('Your site default location type does not exist or is disabled.')
        . " <a href='$url'>" . ts('Configure location types') . '</a>';
      $messages[] = CRM_Utils_Check_Message::error([
        'name' => __FUNCTION__,
        'message' => $msg,
        // Title: Location Type Misconfiguration
        'topic' => ts('Location Types'),
        'subtopic' => ts('Invalid default'),
        'icon' => 'fa-lock',
      ]);
    }
    return $messages;
  }

}
