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
   * @var bool
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
    CRM_Contact_Form_Task_PDFLetterCommon::preProcess($this);
    parent::preProcess();

    // we have all the participant ids, so now we get the contact ids
    parent::setContactIDs();

    $this->assign('single', $this->_single);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    CRM_Contact_Form_Task_PDFLetterCommon::buildQuickForm($this);
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    CRM_Contact_Form_Task_PDFLetterCommon::postProcess($this);
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
    return $tokens;
  }

}
