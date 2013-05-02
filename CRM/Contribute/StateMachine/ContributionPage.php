<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * State machine for managing different states of the Import process.
 *
 */
class CRM_Contribute_StateMachine_ContributionPage extends CRM_Core_StateMachine {

  /**
   * class constructor
   *
   * @param object  CRM_Contribute_Controller_ContributionPage
   * @param int     $action
   *
   * @return object CRM_Contribute_StateMachine_ContributionPage
   */
  function __construct($controller, $action = CRM_Core_Action::NONE) {
    parent::__construct($controller, $action);

    $session = CRM_Core_Session::singleton();
    $session->set('singleForm', FALSE);

    $config = CRM_Core_Config::singleton();

    $this->_pages = array(
      'CRM_Contribute_Form_ContributionPage_Settings' => NULL,
      'CRM_Contribute_Form_ContributionPage_Amount' => NULL,
      'CRM_Member_Form_MembershipBlock' => NULL,
      'CRM_Contribute_Form_ContributionPage_ThankYou' => NULL,
      'CRM_Friend_Form_Contribute' => NULL,
      'CRM_PCP_Form_Contribute' => NULL,
      'CRM_Contribute_Form_ContributionPage_Custom' => NULL,
      'CRM_Contribute_Form_ContributionPage_Premium' => NULL,
      'CRM_Contribute_Form_ContributionPage_Widget' => NULL,
    );

    if (!in_array("CiviMember", $config->enableComponents)) {
      unset($this->_pages['CRM_Member_Form_MembershipBlock']);
    }

    $this->addSequentialPages($this->_pages, $action);
  }
}

