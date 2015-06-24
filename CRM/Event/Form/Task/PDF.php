<?php

/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.6                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id: PDF.php 45499 2013-02-08 12:31:05Z kurund $
 */

/**
 * This class provides the functionality to create PDF letter for a group of
 * participants or a single participant.
 */
class CRM_Event_Form_Task_PDF extends CRM_Event_Form_Task {

  /**
   * Are we operating in "single mode", i.e. printing letter to one
   * specific participant?
   *
   * @var boolean
   */
  public $_single = FALSE;

  /**
   * All the existing templates in the system.
   *
   * @var array
   */
  public $_templates = NULL;
  public $_cid = NULL;
  public $_activityId = NULL;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    parent::preProcess();
    CRM_Contact_Form_Task_PDFLetterCommon::preProcess($this);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    // We have all the participant ids, so now we get the contact ids.
    $this->setContactIDs();
    CRM_Contact_Form_Task_PDFLetterCommon::buildQuickForm($this);
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    CRM_Event_Form_Task_PDFLetterCommon::postProcess($this);
  }

  /**
   * Set default values for the form.
   *
   * @return void
   */
  public function setDefaultValues() {
    return CRM_Contact_Form_Task_PDFLetterCommon::setDefaultValues();
  }

  /**
   * List available tokens for this form.
   *
   * @return array
   */
  public function listTokens() {
    $tokens = CRM_Core_SelectValues::contactTokens();
    $tokens = array_merge(CRM_Core_SelectValues::eventTokens(), $tokens);
    // unset contact_email and contact_phone tokens.
    // These are location_email and location_contact
    // Should be cleaned up after ActionSchedule token replacement cleanup.
    unset($tokens['{event.contact_email}']);
    unset($tokens['{event.contact_phone}']);
    $customEventTokens = CRM_CORE_BAO_CustomField::getFields('Event');

    foreach ($customEventTokens as $customEventTokenKey => $customEventTokenValue) {
      $tokens["{event.custom_customEventTokenKey}"] = $customEventTokenValue['label'] . '::' . $customEventTokenValue['groupTitle'];
    }
    return $tokens;
  }

}
