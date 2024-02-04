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
   * This doesn't really do much - use part should be transferred back to caller
   * and noisy deprecation added.
   *
   * @param \CRM_Contribute_Form_Task_Invoice $form
   * @param bool $bounce determine if we want to throw a status bounce.
   *
   * @deprecated
   *
   * @throws \CRM_Core_Exception
   */
  public static function preProcessFromAddress(&$form, $bounce = TRUE) {
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
    if (is_numeric(key($form->_fromEmails))) {
      $emailID = (int) key($form->_fromEmails);
      $defaults = CRM_Core_BAO_Email::getEmailSignatureDefaults($emailID);
    }
    if (!Civi::settings()->get('allow_mail_from_logged_in_contact')) {
      $defaults['from_email_address'] = CRM_Core_BAO_Domain::getFromEmail();
    }
    $form->setDefaults($defaults);
  }

  /**
   * Form rule.
   *
   * @param array $fields
   *   The input form values.
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule(array $fields) {
    CRM_Core_Error::deprecatedFunctionWarning('no replacement');
    $errors = [];
    //Added for CRM-1393
    if (!empty($fields['saveTemplate']) && empty($fields['saveTemplateName'])) {
      $errors['saveTemplateName'] = ts('Enter name to save message template');
    }
    return empty($errors) ? TRUE : $errors;
  }

}
