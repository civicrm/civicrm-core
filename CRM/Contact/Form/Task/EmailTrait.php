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
 * This class provides the common functionality for tasks that send emails.
 */
trait CRM_Contact_Form_Task_EmailTrait {

  /**
   * Are we operating in "single mode", i.e. sending email to one
   * specific contact?
   *
   * @var bool
   */
  public $_single = FALSE;

  public $_noEmails = FALSE;

  /**
   * All the existing templates in the system.
   *
   * @var array
   */
  public $_templates;

  /**
   * Store "to" contact details.
   * @var array
   */
  public $_toContactDetails = [];

  /**
   * Store all selected contact id's, that includes to, cc and bcc contacts
   * @var array
   */
  public $_allContactIds = [];

  /**
   * Store only "to" contact ids.
   * @var array
   */
  public $_toContactIds = [];

  /**
   * Store only "cc" contact ids.
   * @var array
   */
  public $_ccContactIds = [];

  /**
   * Store only "bcc" contact ids.
   *
   * @var array
   */
  public $_bccContactIds = [];

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {
    // Suppress form might not be required but perhaps there was a risk some other  process had set it to TRUE.
    $this->assign('suppressForm', FALSE);
    $this->assign('emailTask', TRUE);

    CRM_Contact_Form_Task_EmailCommon::buildQuickForm($this);
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function postProcess() {
    CRM_Contact_Form_Task_EmailCommon::postProcess($this);
  }

}
