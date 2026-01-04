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
class CRM_Admin_Form_Setting_Localization extends CRM_Admin_Form_Generic {

  public function preProcess() {
    parent::preProcess();
    $this->sections = [
      'language' => [
        'title' => ts('Language and Currency'),
        'icon' => 'fa-globe',
        'weight' => 10,
      ],
      'address' => [
        'title' => ts('Address'),
        'icon' => 'fa-map-location-dot',
        'weight' => 20,
      ],
      'multi' => [
        'title' => ts('Multiple Languages Support'),
        'icon' => 'fa-language',
        'weight' => 30,
      ],
      'advanced' => [
        'title' => ts('Advanced'),
        'icon' => 'fa-wrench',
        'weight' => 40,
      ],
      'legacy' => [
        'title' => ts('Legacy Settings'),
        'icon' => 'fa-clock-rotate-left',
        'weight' => 50,
        'description' => ts('These settings are supplanted by Formatting locale.'),
      ],
    ];
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $warningTitle = json_encode(ts('Warning'));
    $defaultLocaleOptions = CRM_Admin_Form_Setting_Localization::getDefaultLocaleOptions();

    if (CRM_Core_I18n::isMultiLingual()) {
      // Override `languageLimit` element with calculated options
      $this->addCheckBox('languageLimit', ts('Available Languages'), array_flip($defaultLocaleOptions), NULL, NULL, NULL, NULL, ' &nbsp; ');
      // Extra `addLanguage` element
      $this->addElement('select', 'addLanguage', ts('Add Language'), array_merge(['' => ts('- select -')], array_diff(CRM_Core_I18n::languages(), $defaultLocaleOptions)));
      $this->sections['language']['fields']['addLanguage'] = [
        'weight' => 11,
      ];

      // add the ability to return to single language
      $warning = ts('This will make your CiviCRM installation a single-language one again. THIS WILL DELETE ALL DATA RELATED TO LANGUAGES OTHER THAN THE DEFAULT ONE SELECTED ABOVE (and only that language will be preserved).');
      $warning = json_encode($warning);
      $attributes = ['onChange' => "if (this.checked) CRM.alert($warning, $warningTitle, 'warning', {expires: 0})"];
      $this->addElement('checkbox', 'makeSinglelingual', ts('Return to Single Language'),
        NULL, $attributes
      );
      $this->sections['multi']['fields']['makeSinglelingual'] = [
        'weight' => 0,
      ];
      $this->sections['multi']['description'] = ts("Check this box and click 'Save' to switch this installation from multi-language to single-language.");
    }
    else {
      $warning = ts('Enabling multiple languages changes the schema of your database, so make sure you know what you are doing when enabling this function; making a database backup is strongly recommended.');
      $warning = json_encode($warning);
      $validTriggerPermission = CRM_Core_DAO::checkTriggerViewPermission(TRUE);
      $attributes = ['onChange' => "if (this.checked) CRM.alert($warning, $warningTitle, 'warning', {expires: 0})"];
      if (!$validTriggerPermission) {
        $attributes['disabled'] = 'disabled';
        $this->sections['multi']['description'] = ts("In order to use this functionality, the installation's database user must have privileges to create triggers and views (if binary logging is enabled – this means the SUPER privilege). This install does not have the required privilege(s) enabled.");
      }
      elseif (Civi::settings()->get('logging')) {
        $attributes['disabled'] = 'disabled';
        $this->sections['multi']['description'] = ts("(Multilingual support currently cannot be enabled on installations with enabled logging.)");
      }
      else {
        $this->sections['multi']['description'] = ts("Check this box and click 'Save' to switch this installation from single- to multi-language, then add further languages.");
      }
      $this->addElement('checkbox', 'makeMultilingual', ts('Enable Multiple Languages'),
        NULL, $attributes
      );
      $this->sections['multi']['fields']['makeMultilingual'] = [
        'weight' => 0,
      ];
    }

    // Extra currencyLimit field
    $this->add('select2', 'currencyLimit', ts('Available Currencies'),
      Civi::entity('Contribution')->getOptions('currency'),
      FALSE,
      ['placeholder' => ts('- default currency only -'), 'multiple' => TRUE, 'class' => 'huge']
    );
    $this->sections['language']['fields']['currencyLimit'] = [
      'weight' => 95,
    ];

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
    if (($fields['monetaryThousandSeparator'] ?? NULL) == ($fields['monetaryDecimalPoint'] ?? NULL)) {
      $errors['monetaryThousandSeparator'] = ts('Thousands Separator and Decimal Delimiter can not be the same.');
    }

    if (strlen($fields['monetaryThousandSeparator']) == 0) {
      $errors['monetaryThousandSeparator'] = ts('Thousands Separator can not be empty. You can use a space character instead.');
    }

    if (strlen($fields['monetaryDecimalPoint']) > 1) {
      $errors['monetaryDecimalPoint'] = ts('Decimal Delimiter can not have more than 1 character.');
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
    Civi::rebuild(['system' => TRUE])->execute();

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
    if (isset($values['uiLanguages']) && !in_array($values['lcMessages'], $values['uiLanguages'])) {
      $values['uiLanguages'][] = $values['lcMessages'];
    }

    // if we manipulated the language list, return to the localization admin screen
    $return = (!empty($values['makeMultilingual']) || !empty($values['addLanguage']));

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

    // save all the settings
    $this->saveMetadataDefinedSettings($filteredValues);
    CRM_Core_Session::setStatus(ts('Settings Saved.'), ts('Saved'), 'success');

    if ($return) {
      CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url('civicrm/admin/setting/localization', 'reset=1'));
    }
    else {
      CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url('civicrm/admin', 'reset=1'));
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
    $newCurrency ??= $metadata['default'];

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
      'current_site_language' => ts('Use language in use at the time'),
    ];
    $availableLanguages = array_merge($availableOptions, CRM_Admin_Form_Setting_Localization::getDefaultLocaleOptions());
    return $availableLanguages;
  }

  /**
   * Metadata callback for settings that should be conditionally added to the form depending on multilingual env.
   * @param array $setting
   */
  public static function toggleMultilingualDependentSettings(array &$setting): void {
    $settingToggles = [
      'languageLimit' => TRUE,
      'inheritLocale' => TRUE,
      'uiLanguages' => FALSE,
    ];
    if ($settingToggles[$setting['name']] !== CRM_Core_I18n::isMultiLingual()) {
      unset($setting['settings_pages']['localization']);
    }
  }

}
