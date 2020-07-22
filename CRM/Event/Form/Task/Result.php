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

/**
 * Used for displaying results
 *
 *
 */
class CRM_Event_Form_Task_Result extends CRM_Event_Form_Task {

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   */
  public function preProcess() {
    $session = CRM_Core_Session::singleton();

    //this is done to unset searchRows variable assign during AddToHousehold and AddToOrganization
    $this->set('searchRows', '');

    $ssID = $this->get('ssID');

    $path = 'force=1';
    if (isset($ssID)) {
      $path .= "&reset=1&ssID={$ssID}";
    }
    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $this);
    if (CRM_Utils_Rule::qfKey($qfKey)) {
      $path .= "&qfKey=$qfKey";
    }

    $url = CRM_Utils_System::url('civicrm/event/search', $path);
    $session->replaceUserContext($url);
    CRM_Utils_System::redirect($url);
  }

}
