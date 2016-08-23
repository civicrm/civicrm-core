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
 */

/**
 * This class generates form components for Address Section.
 */
class CRM_Admin_Form_Preferences_Address extends CRM_Admin_Form_Preferences {
  public function preProcess() {

    CRM_Utils_System::setTitle(ts('Settings - Addresses'));

    // Address Standardization
    $addrProviders = array(
      '' => '- select -',
    ) + CRM_Core_SelectValues::addressProvider();

    $this->_varNames = array(
      CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME => array(
        'address_options' => array(
          'html_type' => 'checkboxes',
          'title' => ts('Address Fields'),
          'weight' => 1,
        ),
        'address_format' => array(
          'html_type' => 'textarea',
          'title' => ts('Display Format'),
          'description' => NULL,
          'weight' => 2,
        ),
        'mailing_format' => array(
          'html_type' => 'textarea',
          'title' => ts('Mailing Label Format'),
          'description' => NULL,
          'weight' => 3,
        ),
        'hideCountryMailingLabels' => array(
          'html_type' => 'YesNo',
          'title' => 'Hide Country in Mailing Labels when same as domain country',
          'weight' => 4,
        ),
      ),
      CRM_Core_BAO_Setting::ADDRESS_STANDARDIZATION_PREFERENCES_NAME => array(
        'address_standardization_provider' => array(
          'html_type' => 'select',
          'title' => ts('Provider'),
          'option_values' => $addrProviders,
          'weight' => 5,
        ),
        'address_standardization_userid' => array(
          'html_type' => 'text',
          'title' => ts('User ID'),
          'description' => NULL,
          'weight' => 6,
        ),
        'address_standardization_url' => array(
          'html_type' => 'text',
          'title' => ts('Web Service URL'),
          'description' => NULL,
          'weight' => 7,
        ),
      ),
    );

    parent::preProcess();
  }

  /**
   * @return array
   */
  public function setDefaultValues() {
    $defaults = array();
    $defaults['address_standardization_provider'] = $this->_config->address_standardization_provider;
    $defaults['address_standardization_userid'] = $this->_config->address_standardization_userid;
    $defaults['address_standardization_url'] = $this->_config->address_standardization_url;

    $this->addressSequence = isset($newSequence) ? $newSequence : "";

    $defaults['address_format'] = $this->_config->address_format;
    $defaults['mailing_format'] = $this->_config->mailing_format;
    $defaults['hideCountryMailingLabels'] = $this->_config->hideCountryMailingLabels;

    parent::cbsDefaultValues($defaults);

    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->applyFilter('__ALL__', 'trim');

    $this->addFormRule(array('CRM_Admin_Form_Preferences_Address', 'formRule'));

    //get the tokens for Mailing Label field
    $tokens = CRM_Core_SelectValues::contactTokens();
    $this->assign('tokens', CRM_Utils_Token::formatTokensForDisplay($tokens));

    parent::buildQuickForm();
  }

  /**
   * @param $fields
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
      if (!CRM_Utils_System::checkPHPVersion(5, FALSE)) {
        $errors['_qf_default'] = ts('Address Standardization features require PHP version 5 or greater.');
        return $errors;
      }

      if (!($p && $u && $w)) {
        $errors['_qf_default'] = ts('You must provide values for all three Address Standarization fields.');
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

    // check if county option has been set
    $options = CRM_Core_OptionGroup::values('address_options', FALSE, FALSE, TRUE);
    foreach ($options as $key => $title) {
      if ($title == ts('County')) {
        // check if the $key is present in $this->_params
        if (isset($this->_params['address_options']) &&
          !empty($this->_params['address_options'][$key])
        ) {
          // print a status message to the user if county table seems small
          $countyCount = CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM civicrm_county");
          if ($countyCount < 10) {
            CRM_Core_Session::setStatus(ts('You have enabled the County option. Please ensure you populate the county table in your CiviCRM Database. You can find extensions to populate counties in the <a %1>CiviCRM Extensions Directory</a>.', array(1 => 'href="' . CRM_Utils_System::url('civicrm/admin/extensions', array('reset' => 1), TRUE, 'extensions-addnew') . '"')),
              ts('Populate counties'),
              "info"
            );
          }
        }
      }
    }

    $this->postProcessCommon();
  }

}
