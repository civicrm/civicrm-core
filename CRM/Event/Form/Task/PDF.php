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
    // I think the most important thing the two lines below do, is returning
    // $html_message. But it also takes care of saving a new template.
    $formValues = $this->controller->exportValues($this->getName());

    // This creates a dependency to CRM_Contact_Form_TAsk_PDFLetterCommon.
    // Not sure how bad this is...
    list($formValues, $categories, $html_message, $messageToken, $returnProperties) = CRM_Contact_Form_Task_PDFLetterCommon::processMessageTemplate($formValues);

    $skipOnHold = isset($this->skipOnHold) ? $this->skipOnHold : FALSE;
    $skipDeceased = isset($this->skipDeceased) ? $this->skipDeceased : TRUE;

    foreach ($this->_participantIds as $participantID) {
      $participant_result = civicrm_api3('Participant', 'get', array(
        'id' => $participantID,
        'api.Event.getsingle' => array('id' => '$value.event_id'),
      ));
      $participant = CRM_Utils_Array::first($participant_result['values']);
      $event = $participant['api.Event.getsingle'];

      // get contact information
      // Use 'getTokenDetails' so that hook_civicrm_tokenValues is called.
      $contactId = $participant['contact_id'];
      $tokenDetails = CRM_Utils_Token::getTokenDetails(array($contactId),
        $returnProperties,
        $skipOnHold,
        $skipDeceased,
        NULL,
        $messageToken,
        'CRM_Event_Form_Task_PDF'
      );

      if (empty(CRM_Utils_Array::first($tokenDetails))) {
        continue;
      }
      $contact = CRM_Utils_Array::first(CRM_Utils_Array::first($tokenDetails));

      $tokenHtml = CRM_Utils_Token::replaceContactTokens($html_message, $contact, TRUE, $messageToken);
      $tokenHtml = CRM_Utils_Token::replaceEntityTokens('event', $event, $tokenHtml, $messageToken);
      $tokenHtml = CRM_Utils_Token::replaceEntityTokens('participant', $participant, $tokenHtml, $messageToken);
      $tokenHtml = CRM_Utils_Token::replaceHookTokens($tokenHtml, $contact, $categories, TRUE);

      if (defined('CIVICRM_MAIL_SMARTY') && CIVICRM_MAIL_SMARTY) {
        $smarty = CRM_Core_Smarty::singleton();
        // also add the contact tokens to the template
        $smarty->assign_by_ref('contact', $contact);
        $smarty->assign_by_ref('event', $event);
        $smarty->assign_by_ref('participant', $participant);
        $tokenHtml = $smarty->fetch("string:$tokenHtml");
      }

      $html[] = $tokenHtml;
    }

    CRM_Contact_Form_Task_PDFLetterCommon::createActivities($this, $html_message, $this->_contactIds);

    CRM_Utils_PDF_Utils::html2pdf($html, "CiviLetter.pdf", FALSE, $formValues);

    $this->postProcessHook();

    CRM_Utils_System::civiExit(1);
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
    $customEventTokens = CRM_Core_BAO_CustomField::getFields('Event');

    foreach ($customEventTokens as $customEventTokenKey => $customEventTokenValue) {
      $tokens["{event.custom_$customEventTokenKey}"] = $customEventTokenValue['label'] . '::' . $customEventTokenValue['groupTitle'];
    }
    $tokens = array_merge(CRM_Core_SelectValues::participantTokens(), $tokens);
    unset($tokens['{participant.template_title}']);
    unset($tokens['{participant.fee_label}']);
    unset($tokens['{participant.default_role_id}']);
    return $tokens;
  }

}
