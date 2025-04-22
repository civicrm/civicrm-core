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
 * This class generates form components for Address Section.
 */
class CRM_Admin_Form_Preferences_Address extends CRM_Admin_Form_Preferences {

  /**
   * This should only be populated programmatically via the settings metadata.
   *
   * DO NOT add new settings to these - they need to be migrated to being declared in metadata.
   *
   * @var array
   */
  protected $_settings = [
    'address_options' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'address_format' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'mailing_format' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'hideCountryMailingLabels' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'address_standardization_provider' => CRM_Core_BAO_Setting::ADDRESS_STANDARDIZATION_PREFERENCES_NAME,
    'address_standardization_userid' => CRM_Core_BAO_Setting::ADDRESS_STANDARDIZATION_PREFERENCES_NAME,
    'address_standardization_url' => CRM_Core_BAO_Setting::ADDRESS_STANDARDIZATION_PREFERENCES_NAME,
  ];

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->applyFilter('__ALL__', 'trim');

    $this->addFormRule(['CRM_Admin_Form_Preferences_Address', 'formRule']);

    //get the tokens for Mailing Label field
    $tokens = CRM_Core_SelectValues::contactTokens();
    $this->assign('tokens', CRM_Utils_Token::formatTokensForDisplay($tokens));

    parent::buildQuickForm();
  }

  /**
   * @param array $fields
   *
   * @return bool
   */
  public static function formRule($fields) {
    $p = $fields['address_standardization_provider'];
    $u = $fields['address_standardization_userid'];
    $w = $fields['address_standardization_url'];

    // make sure that there is a value for all of them
    // if any of them are set
    if ($p || $u || $w) {

      if (!($p && $u && $w)) {
        $errors['_qf_default'] = ts('You must provide values for all three Address Standardization fields.');
        return $errors;
      }
    }

    return TRUE;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    if ($this->_action == CRM_Core_Action::VIEW) {
      return;
    }

    $this->_params = $this->controller->exportValues($this->_name);
    $addressOptions = CRM_Core_OptionGroup::values('address_options', TRUE);

    // check if county option has been set
    if (!empty($this->_params['address_options'][$addressOptions['County']])) {
      $countyCount = CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM civicrm_county");
      if ($countyCount < 10) {
        CRM_Core_Session::setStatus(ts('You have enabled the County option. Please ensure you populate the county table in your CiviCRM Database. You can find extensions to populate counties in the <a %1>CiviCRM Extensions Directory</a>.', [1 => 'href="' . CRM_Utils_System::url('civicrm/admin/extensions', ['reset' => 1], TRUE, 'extensions-addnew') . '"']),
          ts('Populate counties'),
          "info"
        );
      }
    }

    // check that locale supports address parsing
    if (
      !empty($this->_params['address_options'][$addressOptions['Street Address Parsing']]) &&
      !CRM_Core_BAO_Address::isSupportedParsingLocale()
    ) {
      $config = CRM_Core_Config::singleton();
      $locale = $config->lcMessages;
      CRM_Core_Session::setStatus(ts('Default locale (%1) does not support street parsing. en_US locale will be used instead.', [1 => $locale]), ts('Unsupported Locale'), 'alert');
    }

    $this->postProcessCommon();
  }

}
