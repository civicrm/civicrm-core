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
 * This class provides the common functionality for creating PDF letter for one or a group of contact ids.
 */
class CRM_Contact_Form_Task_PDFLetterCommon extends CRM_Core_Form_Task_PDFLetterCommon {

  protected static $tokenCategories;

  /**
   * @return array
   *   Array(string $machineName => string $label).
   */
  public static function getLoggingOptions() {
    return [
      'none' => ts('Do not record'),
      'multiple' => ts('Multiple activities (one per contact)'),
      'combined' => ts('One combined activity'),
      'combined-attached' => ts('One combined activity plus one file attachment'),
      // 'multiple-attached' <== not worth the work
    ];
  }

  /**
   * Build all the data structures needed to build the form.
   *
   * @deprecated
   *
   * @param CRM_Core_Form $form
   */
  public static function preProcess(&$form) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
    $defaults = [];
    $form->_fromEmails = CRM_Core_BAO_Email::getFromEmail();
    if (is_numeric(key($form->_fromEmails))) {
      $emailID = (int) key($form->_fromEmails);
      $defaults = CRM_Core_BAO_Email::getEmailSignatureDefaults($emailID);
    }
    if (!Civi::settings()->get('allow_mail_from_logged_in_contact')) {
      $defaults['from_email_address'] = current(CRM_Core_BAO_Domain::getNameAndEmail(FALSE, TRUE));
    }
    $form->setDefaults($defaults);
    $form->setTitle(ts('Print/Merge Document'));
  }

  /**
   * @deprecated
   * @param CRM_Core_Form $form
   * @param int $cid
   */
  public static function preProcessSingle(&$form, $cid) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
    $form->_contactIds = explode(',', $cid);
    // put contact display name in title for single contact mode
    if (count($form->_contactIds) === 1) {
      $form->setTitle(
        ts('Print/Merge Document for %1',
        [1 => CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $cid, 'display_name')])
      );
    }
  }

  /**
   * Get the categories required for rendering tokens.
   *
   * @deprecated
   *
   * @return array
   */
  protected static function getTokenCategories() {
    if (!isset(Civi::$statics[__CLASS__]['token_categories'])) {
      $tokens = [];
      CRM_Utils_Hook::tokens($tokens);
      Civi::$statics[__CLASS__]['token_categories'] = array_keys($tokens);
    }
    return Civi::$statics[__CLASS__]['token_categories'];
  }

  /**
   * Is the form in live mode (as opposed to being run as a preview).
   *
   * Returns true if the user has clicked the Download Document button on a
   * Print/Merge Document (PDF Letter) search task form, or false if the Preview
   * button was clicked.
   *
   * @param CRM_Core_Form $form
   *
   * @return bool
   *   TRUE if the Download Document button was clicked (also defaults to TRUE
   *     if the form controller does not exist), else FALSE
   *
   * @deprecated
   */
  protected static function isLiveMode($form) {
    // CRM-21255 - Hrm, CiviCase 4+5 seem to report buttons differently...
    $buttonName = $form->controller->getButtonName();
    $c = $form->controller->container();
    $isLiveMode = ($buttonName == '_qf_PDF_upload') || isset($c['values']['PDF']['buttons']['_qf_PDF_upload']);
    return $isLiveMode;
  }

}
