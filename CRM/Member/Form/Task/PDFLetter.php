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
 * $Id$
 *
 */

/**
 * This class provides the functionality to create PDF letter for a group of
 * contacts or a single contact.
 */
class CRM_Member_Form_Task_PDFLetter extends CRM_Member_Form_Task {

  /**
   * All the existing templates in the system.
   *
   * @var array
   */
  public $_templates = NULL;

  public $_single = NULL;

  public $_cid = NULL;

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   */
  public function preProcess() {
    $this->skipOnHold = $this->skipDeceased = FALSE;
    parent::preProcess();
    $this->setContactIDs();
    CRM_Contact_Form_Task_PDFLetterCommon::preProcess($this);
  }

  /**
   * Set defaults.
   * (non-PHPdoc)
   * @see CRM_Core_Form::setDefaultValues()
   */
  public function setDefaultValues() {
    return CRM_Contact_Form_Task_PDFLetterCommon::setDefaultValues();
  }

  /**
   * Build the form object.
   *
   *
   * @return void
   */
  public function buildQuickForm() {
    //enable form element
    $this->assign('suppressForm', FALSE);
    CRM_Contact_Form_Task_PDFLetterCommon::buildQuickForm($this);
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   *
   * @return void
   */
  public function postProcess() {
    // TODO: rewrite using contribution token and one letter by contribution
    $this->setContactIDs();
    $skipOnHold = isset($this->skipOnHold) ? $this->skipOnHold : FALSE;
    $skipDeceased = isset($this->skipDeceased) ? $this->skipDeceased : TRUE;
    CRM_Member_Form_Task_PDFLetterCommon::postProcessMembers(
      $this, $this->_memberIds, $skipOnHold, $skipDeceased, $this->_contactIds
    );
  }

  /**
   * List available tokens for this form.
   *
   * @return array
   */
  public function listTokens() {
    $tokens = CRM_Core_SelectValues::contactTokens();
    $tokens = array_merge(CRM_Core_SelectValues::membershipTokens(), $tokens);
    return $tokens;
  }

}
