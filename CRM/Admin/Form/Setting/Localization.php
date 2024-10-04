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

use Civi\Api4\OptionGroup;
use Civi\Api4\OptionValue;

/**
 * This class generates form components for Localization.
 */
class CRM_Admin_Form_Setting_Localization extends CRM_Admin_Form_Setting {

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
    'contact_default_language' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'countryLimit' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'customTranslateFunction' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'defaultContactCountry' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'pinnedContactCountries' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'defaultContactStateProvince' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'defaultCurrency' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'fieldSeparator' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'inheritLocale' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'lcMessages' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'legacyEncoding' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'partial_locales' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'format_locale' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'monetaryThousandSeparator' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'monetaryDecimalPoint' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'moneyformat' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'provinceLimit' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
    'uiLanguages' => CRM_Core_BAO_Setting::LOCALIZATION_PREFERENCES_NAME,
  ];

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->setTitle(ts('Settings - Localization'));

    $warningTitle = json_encode(ts('Warning'));
    $defaultLocaleOptions = CRM_Admin_Form_Setting_Localization::getDefaultLocaleOptions();

    if (CRM_Core_I18n::isMultiLingual()) {
      // add language limiter and language adder
      $this->addCheckBox('languageLimit', ts('Available Languages'), array_flip($defaultLocaleOptions), NULL, NULL, NULL, NULL, ' &nbsp; ');
      $this->addElement('select', 'addLanguage', ts('Add Language'), array_merge(['' => ts('- select -')], array_diff(CRM_Core_I18n::languages(), $defaultLocaleOptions)));

      // add the ability to return to single language
      $warning = ts('This will make your CiviCRM installation a single-language one again. THIS WILL DELETE ALL DATA RELATED TO LANGUAGES OTHER THAN THE DEFAULT ONE SELECTED ABOVE (and only that language will be preserved).');
      $this->assign('warning', $warning);
      $warning = json_encode($warning);
      $this->addElement('checkbox', 'makeSinglelingual', ts('Return to Single Language'),
        NULL, ['onChange' => "if (this.checked) CRM.alert($warning, $warningTitle)"]
      );
    }
    else {
      $warning = ts('Enabling multiple languages changes the schema of your database, so make sure you know what you are doing when enabling this function; making a database backup is strongly recommended.');
      $this->assign('warning', $warning);
      $warning = json_encode($warning);
      $validTriggerPermission = CRM_Core_DAO::checkTriggerViewPermission(TRUE);

      if ($validTriggerPermission &&
        !\Civi::settings()->get('logging')
      ) {
        $this->addElement('checkbox', 'makeMultilingual', ts('Enable Multiple Languages'),
          NULL, ['onChange' => "if (this.checked) CRM.alert($warning, $warningTitle)"]
        );
      }
    }
    $this->addElement('select', 'contact_default_language', ts('Default Language for users'),
      CRM_Admin_Form_Setting_Localization::getDefaultLanguageOptions());

    $this->add('select2', 'currencyLimit', ts('Available Currencies'),
      Civi::entity('Contribution')->getOptions('currency'),
      FALSE,
      ['placeholder' => ts('- default currency only -'), 'multiple' => TRUE, 'class' => 'huge']
    );

    $this->addFormRule(['CRM_Admin_Form_Setting_Localization', 'formRule']);

    parent::buildQuickForm();
  }

  /**
   * @param array $fields
   *
   * @return array|bool
   */
  public static function formRule($fields) {
    $errors = [];
    if (($fields['monetaryThousandSeparator'] ?? NULL) ==
      CRM_Utils_Array::value('monetaryDecimalPoint', $fields)
    ) {
      $errors['monetaryThousandSeparator'] = ts('Thousands Separator and Decimal Delimiter can not be the same.');
    }

    if (strlen($fields['monetaryThousandSeparator']) == 0) {
      $errors['monetaryThousandSeparator'] = ts('Thousands Separator can not be empty. You can use a space character instead.');
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
      (!empty($fields['countryLimit']) &&
        (!in_array($fields['defaultContactCountry'], $fields['countryLimit']))
      )
    ) {
      $errors['defaultContactCountry'] = ts('Please select a default country that is in the list of available countries.');
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Set the default values for the form.
   *
   * @return array
   */
  public function setDefaultValues() {
    parent::setDefaultValues();

    // CRM-1496
    // retrieve default values for currencyLimit
    $this->_defaults['currencyLimit'] = array_keys(CRM_Core_OptionGroup::values('currencies_enabled'));

    $this->_defaults['languageLimit'] = Civi::settings()->get('languageLimit');

    // CRM-5111: unset these two unconditionally, we don’t want them to stick – ever
    unset($this->_defaults['makeMultilingual']);
    unset($this->_defaults['makeSinglelingual']);
    return $this->_defaults;
  }

  public function postProcess() {
    $values = $this->exportValues();

    //cache contact fields retaining localized titles
    //though we changed localization, so reseting cache.
    Civi::cache('fields')->clear();

    //CRM-8559, cache navigation do not respect locale if it is changed, so reseting cache.
    Civi::cache('navigation')->clear();
    // reset ACL and System caches
    CRM_Core_BAO_Cache::resetCaches();

    // make the site multi-lang if requested
    if (!empty($values['makeMultilingual'])) {
      CRM_Core_I18n_Schema::makeMultilingual($values['lcMessages']);
      $values['languageLimit'][$values['lcMessages']] = 1;
      // make the site single-lang if requested
    }
    elseif (!empty($values['makeSinglelingual'])) {
      CRM_Core_I18n_Schema::makeSinglelingual($values['lcMessages']);
      $values['languageLimit'] = '';
    }

    // add a new db locale if the requested language is not yet supported by the db
    if (empty($values['makeSinglelingual']) && !empty($values['addLanguage'])) {
      $locales = CRM_Core_I18n::getMultilingual();
      if (!in_array($values['addLanguage'], $locales)) {
        CRM_Core_I18n_Schema::addLocale($values['addLanguage'], $values['lcMessages']);
      }
      $values['languageLimit'][$values['addLanguage']] = 1;
    }

    // current language should be in the ui list
    if (!in_array($values['lcMessages'], $values['uiLanguages'])) {
      $values['uiLanguages'][] = $values['lcMessages'];
    }

    // if we manipulated the language list, return to the localization admin screen
    $return = (bool) (CRM_Utils_Array::value('makeMultilingual', $values) or CRM_Utils_Array::value('addLanguage', $values));

    // Update enabled currencies
    // we do this only to initialize monetary decimal point and thousand separator
    $config = CRM_Core_Config::singleton();
    // save enabled currencies and default currency in option group 'currencies_enabled'
    // CRM-1496
    $currencyLimit = $values['currencyLimit'] ? explode(',', $values['currencyLimit']) : [];
    if (!in_array($values['defaultCurrency'], $currencyLimit)) {
      $currencyLimit[] = $values['defaultCurrency'];
    }
    self::updateEnabledCurrencies($currencyLimit, $values['defaultCurrency']);
    // unset currencyLimit so we dont store there
    unset($values['currencyLimit']);

    $filteredValues = $values;
    unset($filteredValues['makeMultilingual']);
    unset($filteredValues['makeSinglelingual']);
    unset($filteredValues['addLanguage']);
    unset($filteredValues['languageLimit']);

    Civi::settings()->set('languageLimit', CRM_Utils_Array::value('languageLimit', $values));

    // save all the settings
    parent::commonProcess($filteredValues);

    if ($return) {
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/setting/localization', 'reset=1'));
    }
  }

  /**
   * Replace available currencies by the ones provided
   *
   * @param string[] $currencies array of currencies ['USD', 'CAD']
   * @param string $default default currency
   *
   * @throws \CRM_Core_Exception
   */
  public static function updateEnabledCurrencies(array $currencies, string $default): void {

    // sort so that when we display drop down, weights have right value
    sort($currencies);
    // get labels for all the currencies
    $options = [];

    $currencySymbols = self::getCurrencySymbols();
    foreach ($currencies as $i => $currency) {
      $options[] = [
        'label' => $currencySymbols[$currency],
        'value' => $currency,
        'weight' => $i + 1,
        'is_active' => 1,
        'is_default' => $currency === $default,
      ];
    }
    $optionGroupID = OptionGroup::get(FALSE)->addSelect('id')
      ->addWhere('name', '=', 'currencies_enabled')
      ->execute()->first()['id'];
    // @TODO: This causes a problem in multilingual
    // (https://github.com/civicrm/civicrm-core/pull/17228), but is needed in
    // order to be able to remove currencies once added.
    if (!CRM_Core_I18n::isMultiLingual()) {
      CRM_Core_DAO::executeQuery("
        DELETE
        FROM civicrm_option_value
        WHERE option_group_id = $optionGroupID
      ");
    }

    OptionValue::save(FALSE)
      ->setRecords($options)
      ->setDefaults(['is_active' => 1, 'option_group_id' => $optionGroupID])
      ->setMatch(['option_group_id', 'value'])
      ->execute();
  }

  /**
   * @return array
   */
  public static function getAvailableCountries() {
    $i18n = CRM_Core_I18n::singleton();
    $country = [];
    CRM_Core_PseudoConstant::populate($country, 'CRM_Core_DAO_Country', TRUE, 'name', 'is_active');
    $i18n->localizeArray($country, ['context' => 'country']);
    asort($country);
    return $country;
  }

  /**
   * Get the default locale options.
   *
   * @return array
   */
  public static function getDefaultLocaleOptions() {
    $locales = CRM_Core_I18n::getMultilingual();
    $languages = CRM_Core_I18n::languages();
    if ($locales) {
      // for multi-lingual sites, populate default language drop-down with available languages
      return array_intersect_key($languages, array_flip($locales));
    }
    else {
      return $languages;
    }
  }

  /**
   * Get a list of currencies (with their symbols).
   *
   * @return array
   *   Array('USD' => 'USD ($)').
   */
  public static function getCurrencySymbols() {
    $symbols = CRM_Contribute_DAO_Contribution::buildOptions('currency', 'abbreviate');
    $_currencySymbols = [];
    foreach ($symbols as $key => $value) {
      $_currencySymbols[$key] = "$key";
      if ($value) {
        $_currencySymbols[$key] .= " ($value)";
      }
    }
    return $_currencySymbols;
  }

  /**
   * Update session and uf_match table when the locale is updated.
   *
   * @param string $oldLocale
   * @param string $newLocale
   * @param array $metadata
   * @param int $domainID
   */
  public static function onChangeLcMessages($oldLocale, $newLocale, $metadata, $domainID) {
    if ($oldLocale == $newLocale) {
      return;
    }

    $session = CRM_Core_Session::singleton();
    if ($newLocale && $session->get('userID')) {
      $ufm = new CRM_Core_DAO_UFMatch();
      $ufm->contact_id = $session->get('userID');
      if ($newLocale && $ufm->find(TRUE)) {
        $session->set('lcMessages', $newLocale);
      }
    }
  }

  public static function onChangeDefaultCurrency($oldCurrency, $newCurrency, $metadata) {
    if ($oldCurrency == $newCurrency) {
      return;
    }

    // ensure that default currency is always in the list of enabled currencies
    $currencies = array_keys(CRM_Core_OptionGroup::values('currencies_enabled'));
    if (!in_array($newCurrency, $currencies)) {
      if (empty($currencies)) {
        $currencies = [$newCurrency];
      }
      else {
        $currencies[] = $newCurrency;
      }

      CRM_Admin_Form_Setting_Localization::updateEnabledCurrencies($currencies, $newCurrency);
    }

  }

  /**
   * @return array
   */
  public static function getDefaultLanguageOptions() {
    $availableOptions = [
      '*default*' => ts('Use default site language'),
      'undefined' => ts('Leave undefined'),
      'current_site_language' => ts('Use language in use at the time'),
    ];
    $availableLanguages = array_merge($availableOptions, CRM_Admin_Form_Setting_Localization::getDefaultLocaleOptions());
    return $availableLanguages;
  }

}
