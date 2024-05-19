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
 * State machine for managing different states of the Import process.
 */
class CRM_Contribute_StateMachine_ContributionPage extends CRM_Core_StateMachine {

  /**
   * Class constructor.
   *
   * @param CRM_Contribute_Controller_ContributionPage $controller
   * @param const|int $action
   *
   * @return CRM_Contribute_StateMachine_ContributionPage
   */
  public function __construct($controller, $action = CRM_Core_Action::NONE) {
    parent::__construct($controller, $action);

    $session = CRM_Core_Session::singleton();
    $session->set('singleForm', FALSE);

    $this->_pages = [
      'CRM_Contribute_Form_ContributionPage_Settings' => NULL,
      'CRM_Contribute_Form_ContributionPage_Amount' => NULL,
      'CRM_Member_Form_MembershipBlock' => NULL,
      'CRM_Contribute_Form_ContributionPage_ThankYou' => NULL,
      'CRM_Friend_Form_Contribute' => NULL,
      'CRM_PCP_Form_Contribute' => NULL,
      'CRM_Contribute_Form_ContributionPage_Custom' => NULL,
      'CRM_Contribute_Form_ContributionPage_Premium' => NULL,
      'CRM_Contribute_Form_ContributionPage_Widget' => NULL,
    ];

    if (!function_exists('tellafriend_civicrm_config')) {
      unset($this->_pages['CRM_Friend_Form_Contribute']);
    }
    if (!CRM_Core_Component::isEnabled('CiviMember')) {
      unset($this->_pages['CRM_Member_Form_MembershipBlock']);
    }

    $this->addSequentialPages($this->_pages);
  }

}
