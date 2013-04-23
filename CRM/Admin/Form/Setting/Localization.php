<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class generates form components for Localization
 *
 */
class CRM_Admin_Form_Setting_Localization extends CRM_Admin_Form_Setting {
  // use this variable to store mappings that we compute in buildForm and also
  // use in postProcess (CRM-1496)
  protected $_currencySymbols;

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $config = CRM_Core_Config::singleton();

    $i18n = CRM_Core_I18n::singleton();
    CRM_Utils_System::setTitle(ts('Settings - Localization'));

    $locales = CRM_Core_I18n::languages();

    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);
    if ($domain->locales) {
      // for multi-lingual sites, populate default language drop-down with available languages
      $lcMessages = array();
      foreach ($locales as $loc => $lang) {
        if (substr_count($domain->locales, $loc)) {
          $lcMessages[$loc] = $lang;
        }
      }
      $this->addElement('select', 'lcMessages', ts('Default Language'), $lcMessages);

      // add language limiter and language adder
      $this->addCheckBox('languageLimit', ts('Available Languages'), array_flip($lcMessages), NULL, NULL, NULL, NULL, ' &nbsp; ');
      $this->addElement('select', 'addLanguage', ts('Add Language'), array_merge(array('' => ts('- select -')), array_diff($locales, $lcMessages)));

      // add the ability to return to single language
      $warning = ts('WARNING: This will make your CiviCRM installation a single-language one again. THIS WILL DELETE ALL DATA RELATED TO LANGUAGES OTHER THAN THE DEFAULT ONE SELECTED ABOVE (and only that language will be preserved).');
      $this->assign('warning', $warning);
      $this->addElement('checkbox', 'makeSinglelingual', ts('Return to Single Language'),
        NULL, array('onChange' => "if (this.checked) alert('$warning')")
      );
    }
    else {
      // for single-lingual sites, populate default language drop-down with all languages
      $this->addElement('select', 'lcMessages', ts('Default Language'), $locales);

      $warning = ts('WARNING: Enabling multiple languages changes the schema of your database, so make sure you know what you are doing when enabling this function; making a database backup is strongly recommended.');
      $this->assign('warning', $warning);

      $validTriggerPermission = CRM_Core_DAO::checkTriggerViewPermission(TRUE);

      if ($validTriggerPermission &&
        !$config->logging
      ) {
        $this->addElement('checkbox', 'makeMultilingual', ts('Enable Multiple Languages'),
          NULL, array('onChange' => "if (this.checked) alert('$warning')")
        );
      }
    }

    $this->addElement('checkbox', 'inheritLocale', ts('Inherit CMS Language'));
    $this->addElement('text', 'monetaryThousandSeparator', ts('Thousands Separator'), array('size' => 2));
    $this->addElement('text', 'monetaryDecimalPoint', ts('Decimal Delimiter'), array('size' => 2));
    $this->addElement('text', 'moneyformat', ts('Monetary Amount Display'));
    $this->addElement('text', 'moneyvalueformat', ts('Monetary Value Display'));

    $country = array();
    CRM_Core_PseudoConstant::populate($country, 'CRM_Core_DAO_Country', TRUE, 'name', 'is_active');
    $i18n->localizeArray($country, array('context' => 'country'));
    asort($country);

    $includeCountry = &$this->addElement('advmultiselect', 'countryLimit',
      ts('Available Countries') . ' ', $country,
      array(
        'size' => 5,
        'style' => 'width:150px',
        'class' => 'advmultiselect',
      )
    );

    $includeCountry->setButtonAttributes('add', array('value' => ts('Add >>')));
    $includeCountry->setButtonAttributes('remove', array('value' => ts('<< Remove')));

    $includeState = &$this->addElement('advmultiselect', 'provinceLimit',
      ts('Available States and Provinces') . ' ', $country,
      array(
        'size' => 5,
        'style' => 'width:150px',
        'class' => 'advmultiselect',
      )
    );

    $includeState->setButtonAttributes('add', array('value' => ts('Add >>')));
    $includeState->setButtonAttributes('remove', array('value' => ts('<< Remove')));

    $this->addElement('select', 'defaultContactCountry', ts('Default Country'), array('' => ts('- select -')) + $country);

    /***Default State/Province***/
    $stateCountryMap = array();
    $stateCountryMap[] = array(
      'state_province' => 'defaultContactStateProvince',
      'country' => 'defaultContactCountry',
    );

    $countryDefault = isset($this->_submitValues['defaultContactCountry']) ? $this->_submitValues['defaultContactCountry'] : $config->defaultContactCountry;

    if ($countryDefault) {
      $selectStateProvinceOptions = array('' => ts('- select -')) + CRM_Core_PseudoConstant::stateProvinceForCountry($countryDefault);
    }
    else {
      $selectStateProvinceOptions = array('' => ts('- select a country -'));
    }

    $i18n->localizeArray($selectStateProvinceOptions, array('context' => 'state_province'));
    asort($selectStateProvinceOptions);

    $this->addElement('select', 'defaultContactStateProvince', ts('Default State/Province'), $selectStateProvinceOptions);

    // state country js
    CRM_Core_BAO_Address::addStateCountryMap($stateCountryMap);

    $defaults = array();
    CRM_Core_BAO_Address::fixAllStateSelects($form, $defaults);

    // we do this only to initialize currencySymbols, kinda hackish but works!
    $config->defaultCurrencySymbol();

    $symbol = $config->currencySymbols;
    foreach ($symbol as $key => $value) {
      $this->_currencySymbols[$key] = "$key";
      if ($value) {
        $this->_currencySymbols[$key] .= " ($value)";
      }
    }
    $this->addElement('select', 'defaultCurrency', ts('Default Currency'), $this->_currencySymbols);

    $includeCurrency = &$this->addElement('advmultiselect', 'currencyLimit',
      ts('Available Currencies') . ' ', $this->_currencySymbols,
      array(
        'size' => 5,
        'style' => 'width:150px',
        'class' => 'advmultiselect',
      )
    );

    $includeCurrency->setButtonAttributes('add', array('value' => ts('Add >>')));
    $includeCurrency->setButtonAttributes('remove', array('value' => ts('<< Remove')));

    $this->addElement('text', 'legacyEncoding', ts('Legacy Encoding'));
    $this->addElement('text', 'customTranslateFunction', ts('Custom Translate Function'));
    $this->addElement('text', 'fieldSeparator', ts('Import / Export Field Separator'), array('size' => 2));

    $this->addFormRule(array('CRM_Admin_Form_Setting_Localization', 'formRule'));

    parent::buildQuickForm();
  }

  static function formRule($fields) {
    $errors = array();
    if (CRM_Utils_Array::value('monetaryThousandSeparator', $fields) ==
      CRM_Utils_Array::value('monetaryDecimalPoint', $fields)
    ) {
      $errors['monetaryThousandSeparator'] = ts('Thousands Separator and Decimal Delimiter can not be the same.');
    }

    if (strlen($fields['monetaryThousandSeparator']) == 0) {
      $errors['monetaryThousandSeparator'] = ts('Thousands Separator can not be empty. You can use a space character instead.');
    }

    if (strlen($fields['monetaryThousandSeparator']) > 1) {
      $errors['monetaryThousandSeparator'] = ts('Thousands Separator can not have more than 1 character.');
    }

    if (strlen($fields['monetaryDecimalPoint']) > 1) {
      $errors['monetaryDecimalPoint'] = ts('Decimal Delimiter can not have more than 1 character.');
    }

    if (trim($fields['customTranslateFunction']) &&
      !function_exists(trim($fields['customTranslateFunction']))
    ) {
      $errors['customTranslateFunction'] = ts('Please define the custom translation function first.');
    }

    // CRM-7962, CRM-7713, CRM-9004
    if (!empty($fields['defaultContactCountry']) &&
      (CRM_Utils_Array::value('countryLimit', $fields) &&
        (!in_array($fields['defaultContactCountry'], $fields['countryLimit']))
      )
    ) {
      $errors['defaultContactCountry'] = ts('Please select a default country that is in the list of available countries.');
    }

    return empty($errors) ? TRUE : $errors;
  }

  function setDefaultValues() {
    parent::setDefaultValues();

    // CRM-1496
    // retrieve default values for currencyLimit
    $this->_defaults['currencyLimit'] = array_keys(CRM_Core_OptionGroup::values('currencies_enabled'));

    // CRM-5111: unset these two unconditionally, we don’t want them to stick – ever
    unset($this->_defaults['makeMultilingual']);
    unset($this->_defaults['makeSinglelingual']);
    return $this->_defaults;
  }

  public function postProcess() {
    $values = $this->exportValues();

    // FIXME: stupid QF not submitting unchecked checkboxen…
    if (!isset($values['inheritLocale'])) {
      $values['inheritLocale'] = 0;
    }

    //cache contact fields retaining localized titles
    //though we changed localization, so reseting cache.
    CRM_Core_BAO_Cache::deleteGroup('contact fields');

    //CRM-8559, cache navigation do not respect locale if it is changed, so reseting cache.
    CRM_Core_BAO_Cache::deleteGroup('navigation');

    // we do this only to initialize monetary decimal point and thousand separator
    $config = CRM_Core_Config::singleton();

    // set default Currency Symbol
    $values['defaultCurrencySymbol'] = $config->defaultCurrencySymbol($values['defaultCurrency']);

    // save enabled currencies and defaul currency in option group 'currencies_enabled'
    // CRM-1496
    if (empty($values['currencyLimit'])) {
      $values['currencyLimit'] = array($values['defaultCurrency']);
    }
    elseif (!in_array($values['defaultCurrency'],
        $values['currencyLimit']
      )) {
      $values['currencyLimit'][] = $values['defaultCurrency'];
    }

    // sort so that when we display drop down, weights have right value
    sort($values['currencyLimit']);

    // get labels for all the currencies
    $options = array();

    for ($i = 0; $i < count($values['currencyLimit']); $i++) {
      $options[] = array(
        'label' => $this->_currencySymbols[$values['currencyLimit'][$i]],
        'value' => $values['currencyLimit'][$i],
        'weight' => $i + 1,
        'is_active' => 1,
        'is_default' => $values['currencyLimit'][$i] == $values['defaultCurrency'],
      );
    }

    $dontCare = NULL;
    CRM_Core_OptionGroup::createAssoc('currencies_enabled',
      $options,
      $dontCare
    );

    // unset currencyLimit so we dont store there
    unset($values['currencyLimit']);

    // make the site multi-lang if requested
    if (CRM_Utils_Array::value('makeMultilingual', $values)) {
      CRM_Core_I18n_Schema::makeMultilingual($values['lcMessages']);
      $values['languageLimit'][$values['lcMessages']] = 1;
      // make the site single-lang if requested
    }
    elseif (CRM_Utils_Array::value('makeSinglelingual', $values)) {
      CRM_Core_I18n_Schema::makeSinglelingual($values['lcMessages']);
      $values['languageLimit'] = '';
    }

    // add a new db locale if the requested language is not yet supported by the db
    if (!CRM_Utils_Array::value('makeSinglelingual', $values) and CRM_Utils_Array::value('addLanguage', $values)) {
      $domain = new CRM_Core_DAO_Domain();
      $domain->find(TRUE);
      if (!substr_count($domain->locales, $values['addLanguage'])) {
        CRM_Core_I18n_Schema::addLocale($values['addLanguage'], $values['lcMessages']);
      }
      $values['languageLimit'][$values['addLanguage']] = 1;
    }

    // if we manipulated the language list, return to the localization admin screen
    $return = (bool)(CRM_Utils_Array::value('makeMultilingual', $values) or CRM_Utils_Array::value('addLanguage', $values));

    // save all the settings
    parent::commonProcess($values);

    if ($return) {
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/setting/localization', 'reset=1'));
    }
  }
}

