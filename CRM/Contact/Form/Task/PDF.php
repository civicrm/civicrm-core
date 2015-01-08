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
class CRM_Contact_Form_Task_PDF extends CRM_Contact_Form_Task {

  /**
   * all the existing templates in the system
   *
   * @var array
   */
  public $_templates = NULL;

  public $_single = NULL;

  public $_cid = NULL;

  public $_activityId = NULL;

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {

    $this->skipOnHold = $this->skipDeceased = FALSE;
    CRM_Contact_Form_Task_PDFLetterCommon::preProcess($this);

    // store case id if present
    $this->_caseId = CRM_Utils_Request::retrieve('caseid', 'Positive', $this, FALSE);

    // retrieve contact ID if this is 'single' mode
    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this, FALSE);

    if ($cid) {
      // this is true in non-search context / single mode
      // in search context 'id' is the default profile id for search display
      // CRM-11227
      $this->_activityId = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);
    }

    if ($cid) {
      CRM_Contact_Form_Task_PDFLetterCommon::preProcessSingle($this, $cid);
      $this->_single = TRUE;
      $this->_cid = $cid;
    }
    else {
      parent::preProcess();
    }
    $this->assign('single', $this->_single);
  }

  /**
   * This function sets the default values for the form. Relationship that in edit/view action
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return void
   */
  /**
   *
   */
  function setDefaultValues() {
    $defaults = array();
    if (isset($this->_activityId)) {
      $params = array('id' => $this->_activityId);
      CRM_Activity_BAO_Activity::retrieve($params, $defaults);
      $defaults['html_message'] = CRM_Utils_Array::value('details', $defaults);
    }
    $defaults = $defaults + CRM_Contact_Form_Task_PDFLetterCommon::setDefaultValues();
    return $defaults;
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
    CRM_Contact_Form_Task_PDFLetterCommon::postProcess($this);
  }
}

