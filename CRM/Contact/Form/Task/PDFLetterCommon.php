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
