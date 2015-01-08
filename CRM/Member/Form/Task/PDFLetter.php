<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class provides the functionality to create PDF letter for a group of
 * contacts or a single contact.
 */
class CRM_Member_Form_Task_PDFLetter extends CRM_Member_Form_Task {

  /**
   * all the existing templates in the system
   *
   * @var array
   */
  public $_templates = NULL;

  public $_single = NULL;

  public $_cid = NULL;

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    $this->skipOnHold = $this->skipDeceased = FALSE;
    parent::preProcess();
    $this->setContactIDs();
    CRM_Contact_Form_Task_PDFLetterCommon::preProcess($this);
  }

  /**
   * Set defaults
   * (non-PHPdoc)
   * @see CRM_Core_Form::setDefaultValues()
   */
  function setDefaultValues() {
    return  CRM_Contact_Form_Task_PDFLetterCommon::setDefaultValues();
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  public function buildQuickForm() {
    //enable form element
    $this->assign('suppressForm', FALSE);
    CRM_Contact_Form_Task_PDFLetterCommon::buildQuickForm($this);
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
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
   * list available tokens, at time of writing these were
   * {membership.id} => Membership ID
   * {membership.status} => Membership Status
   * {membership.type} => Membership Type
   * {membership.start_date} => Membership Start Date
   * {membership.join_date} => Membership Join Date
   * {membership.end_date} => Membership End Date
   * {membership.fee} => Membership Fee
   * @return Ambigous <NULL, multitype:string Ambigous <string, string> >
   */
  public function listTokens() {
    return CRM_Core_SelectValues::membershipTokens();
  }
}

