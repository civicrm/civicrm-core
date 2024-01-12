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
 */
class CRM_Contact_Form_Task_Result extends CRM_Contact_Form_Task {

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    $session = CRM_Core_Session::singleton();

    //this is done to unset searchRows variable assign during AddToHousehold and AddToOrganization
    $this->set('searchRows', '');

    $context = $this->get('context');
    if (in_array($context, ['smog', 'amtg'])) {
      $urlParams = 'reset=1&force=1&context=smog&gid=';
      $urlParams .= ($context == 'smog') ? $this->get('gid') : $this->get('amtgID');
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/group/search', $urlParams));
      return;
    }

    $ssID = $this->get('ssID');

    if ($this->_action == CRM_Core_Action::BASIC) {
      $fragment = 'search';
    }
    elseif ($this->_action == CRM_Core_Action::PROFILE) {
      $fragment = 'search/builder';
    }
    elseif ($this->_action == CRM_Core_Action::ADVANCED) {
      $fragment = 'search/advanced';
    }
    else {
      $fragment = 'search/custom';
    }

    $path = 'force=1';
    if (isset($ssID)) {
      $path .= "&reset=1&ssID={$ssID}";
    }
    if (!CRM_Contact_Form_Search::isSearchContext($context)) {
      $context = 'search';
    }
    $path .= "&context=$context";

    //set the user context for redirection of task actions
    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $this);
    if (CRM_Utils_Rule::qfKey($qfKey)) {
      $path .= "&qfKey=$qfKey";
    }

    $url = CRM_Utils_System::url('civicrm/contact/' . $fragment, $path);
    $session->replaceUserContext($url);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addButtons([
      [
        'type' => 'done',
        'name' => ts('Done'),
        'isDefault' => TRUE,
      ],
    ]);
  }

}
