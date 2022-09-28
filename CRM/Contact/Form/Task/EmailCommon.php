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
 * This class provides the common functionality for sending email to
 * one or a group of contact ids. This class is reused by all the search
 * components in CiviCRM (since they all have send email as a task)
 */
class CRM_Contact_Form_Task_EmailCommon {

  /**
   * Pre Process Form Addresses to be used in Quickform
   *
   * @param CRM_Core_Form $form
   * @param bool $bounce determine if we want to throw a status bounce.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function preProcessFromAddress(&$form, $bounce = TRUE) {
    if (!isset($form->_single)) {
      // @todo ensure this is already set.
      $form->_single = FALSE;
    }

    $form->_emails = [];

    // @TODO remove these line and to it somewhere more appropriate. Currently some classes (e.g Case
    // are having to re-write contactIds afterwards due to this inappropriate variable setting
    // If we don't have any contact IDs, use the logged in contact ID
    $form->_contactIds = $form->_contactIds ?: [CRM_Core_Session::getLoggedInContactID()];

    $fromEmailValues = CRM_Core_BAO_Email::getFromEmail();

    if ($bounce) {
      if (empty($fromEmailValues)) {
        CRM_Core_Error::statusBounce(ts('Your user record does not have a valid email address and no from addresses have been configured.'));
      }
    }

    $form->_emails = $fromEmailValues;
    $defaults = [];
    $form->_fromEmails = $fromEmailValues;
    if (!Civi::settings()->get('allow_mail_from_logged_in_contact')) {
      $defaults['from_email_address'] = current(CRM_Core_BAO_Domain::getNameAndEmail(FALSE, TRUE));
    }
    if (is_numeric(key($form->_fromEmails))) {
      // Add signature
      $defaultEmail = civicrm_api3('email', 'getsingle', ['id' => key($form->_fromEmails)]);
      $defaults = [];
      if (!empty($defaultEmail['signature_html'])) {
        $defaults['html_message'] = '<br/><br/>--' . $defaultEmail['signature_html'];
      }
      if (!empty($defaultEmail['signature_text'])) {
        $defaults['text_message'] = "\n\n--\n" . $defaultEmail['signature_text'];
      }
    }
    $form->setDefaults($defaults);
  }

  /**
   * Form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $dontCare
   * @param array $self
   *   Additional values form 'this'.
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $dontCare, $self) {
    $errors = [];
    $template = CRM_Core_Smarty::singleton();

    if (isset($fields['html_message'])) {
      $htmlMessage = str_replace(["\n", "\r"], ' ', $fields['html_message']);
      $htmlMessage = str_replace('"', '\"', $htmlMessage);
      $template->assign('htmlContent', $htmlMessage);
    }

    //Added for CRM-1393
    if (!empty($fields['saveTemplate']) && empty($fields['saveTemplateName'])) {
      $errors['saveTemplateName'] = ts("Enter name to save message template");
    }

    return empty($errors) ? TRUE : $errors;
  }

}
