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
 * This class generates form components for Site Url.
 */
class CRM_Admin_Form_Setting_Url extends CRM_Admin_Form_Setting {

  /**
   * Subset of settings on the page as defined using the legacy method.
   *
   * @var array
   *
   * @deprecated - do not add new settings here - the page to display
   * settings on should be defined in the setting metadata.
   */
  protected $_settings = [
    // @todo remove these, define any not yet defined in the setting metadata.
    'disable_core_css' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'defaultExternUrl' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'enableSSL' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'verifySSL' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'userFrameworkResourceURL' => CRM_Core_BAO_Setting::URL_PREFERENCES_NAME,
    'imageUploadURL' => CRM_Core_BAO_Setting::URL_PREFERENCES_NAME,
    'customCSSURL' => CRM_Core_BAO_Setting::URL_PREFERENCES_NAME,
    'extensionsURL' => CRM_Core_BAO_Setting::URL_PREFERENCES_NAME,
  ];

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->setTitle(ts('Settings - Resource URLs'));
    $this->addFormRule(['CRM_Admin_Form_Setting_Url', 'formRule']);

    parent::buildQuickForm();
  }

  /**
   * @param array $fields
   *
   * @return array|bool
   */
  public static function formRule($fields) {
    if (isset($fields['enableSSL']) &&
      $fields['enableSSL']
    ) {
      $config = CRM_Core_Config::singleton();
      $url = str_replace('http://', 'https://',
        CRM_Utils_System::url('civicrm/dashboard', 'reset=1', TRUE,
          NULL, FALSE, FALSE
        )
      );
      if (!CRM_Utils_System::checkURL($url, TRUE)) {
        $errors = [
          'enableSSL' => ts('You need to set up a secure server before you can use the Force Secure URLs option'),
        ];
        return $errors;
      }
    }
    return TRUE;
  }

  public function postProcess() {
    // if extensions url is set, lets clear session status messages to avoid
    // a potentially spurious message which might already have been set. This
    // is a bit hackish
    // CRM-10629
    $session = CRM_Core_Session::singleton();
    $session->getStatus(TRUE);

    parent::postProcess();
  }

}
