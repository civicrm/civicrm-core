<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * This class provides the functionality to email a group of contacts.
 */
class CRM_Contribute_Form_Task_Email extends CRM_Contribute_Form_Task {

  /**
   * Are we operating in "single mode", i.e. sending email to one
   * specific contact?
   *
   * @var boolean
   */
  public $_single = FALSE;

  public $_noEmails = FALSE;

  /**
   * All the existing templates in the system.
   *
   * @var array
   */
  public $_templates = NULL;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    CRM_Contact_Form_Task_EmailCommon::preProcessFromAddress($this);
    parent::preProcess();

    // we have all the contribution ids, so now we get the contact ids
    parent::setContactIDs();

    $this->assign('single', $this->_single);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    //enable form element
    $this->assign('emailTask', TRUE);

    CRM_Contact_Form_Task_EmailCommon::buildQuickForm($this);
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    CRM_Contact_Form_Task_EmailCommon::postProcess($this);
  }

  /**
   * List available tokens for this form.
   *
   * @return array
   */
  public function listTokens() {
    $tokens = CRM_Core_SelectValues::contactTokens();
    $tokens = array_merge(CRM_Core_SelectValues::contributionTokens(), $tokens);
    return $tokens;
  }

}
