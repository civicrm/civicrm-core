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
 * $Id$
 *
 */

/**
 * This class provides the common functionality for creating PDF letter for
 * one or a group of participant ids.
 */
class CRM_Event_Form_Task_PDFLetterCommon extends CRM_Contact_Form_Task_PDFLetterCommon {
  /**
   * process the form after the input has been submitted and validated
   * @todo this is horrible copy & paste code because there is so much risk of breakage
   * in fixing the existing pdfLetter classes to be suitably generic
   * @access public
   *
   * @return None
   */
  static function postProcess(&$form) {

    list($formValues, $categories, $html_message, $messageToken, $returnProperties) = self::processMessageTemplate($form);

    $skipOnHold = isset($form->skipOnHold) ? $form->skipOnHold : FALSE;
    $skipDeceased = isset($form->skipDeceased) ? $form->skipDeceased : TRUE;

    foreach ($form->_participantIds as $participantID) {

      $participant = civicrm_api3('participant', 'get', array('participant_id' => $participantID))['values'][$participantID];
      $event = civicrm_api3('event', 'get', array('id' => $eventid))['values'][$participant['event_id']];

      // get contact information

      $params = array('contact_id' => $participant['contact_id']);
      list($contact) = CRM_Utils_Token::getTokenDetails($params, $returnProperties, $skipOnHold, $skipDeceased, NULL, $messageToken, 'CRM_Contact_Form_Task_PDFLetterCommon'
      );

      if (civicrm_error($contact)) {
        $notSent[] = $contactId;
        continue;
      }

      $tokenHtml = CRM_Utils_Token::replaceContactTokens($html_message, $contact[$contactId], TRUE, $messageToken);
      $tokenHtml = CRM_Utils_Token::replaceEntityTokens('event', $event, $tokenHtml, $messageToken);
      $tokenHtml = CRM_Utils_Token::replaceHookTokens($tokenHtml, $contact[$contactId], $categories, TRUE);

      if (defined('CIVICRM_MAIL_SMARTY') && CIVICRM_MAIL_SMARTY) {
        $smarty = CRM_Core_Smarty::singleton();
        // also add the contact tokens to the template
        $smarty->assign_by_ref('contact', $contact);
        $smarty->assign_by_ref('event', $event);
        $tokenHtml = $smarty->fetch("string:$tokenHtml");
      }

      $html[] = $tokenHtml;
    }

    self::createActivities($form, $html_message, $form->_contactIds);

    CRM_Utils_PDF_Utils::html2pdf($html, "CiviLetter.pdf", FALSE, $formValues);

    $form->postProcessHook();

    CRM_Utils_System::civiExit(1);
  }

 
}
