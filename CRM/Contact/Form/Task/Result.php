<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
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
    if (in_array($context, array(
      'smog',
      'amtg',
    ))) {
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
    $this->addButtons(array(
        array(
          'type' => 'done',
          'name' => ts('Done'),
          'isDefault' => TRUE,
        ),
      )
    );
  }

}
