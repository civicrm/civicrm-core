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

namespace Civi\Core;

/**
 * Class SettingsBag
 * @package Civi\Core
 *
 * Read and write settings for a given domain (or contact).
 *
 * If the target entity does not already have a value for the setting, then
 * the defaults will be used. If mandatory values are provided, they will
 * override any defaults or custom settings.
 *
 * It's expected that the SettingsBag will have O(50-250) settings -- and that
 * we'll load the full bag on many page requests. Consequently, we don't
 * want the full metadata (help text and version history and HTML widgets)
 * for all 250 settings, but we do need the default values.
 *
 * This class is not usually instantiated directly. Instead, use SettingsManager
 * or Civi::settings().
 *
 * @see \Civi::settings()
 * @see SettingsManagerTest
 */
class SettingsBag {

  protected $domainId;

  protected $contactId;

  /**
   * @var array
   *   Array(string $settingName => mixed $value).
   */
  protected $defaults;

  /**
   * @var array
   *   Array(string $settingName => mixed $value).
   */
  protected $mandatory;

  /**
   * The result of combining default values, mandatory
   * values, and user values.
   *
   * @var array|null
   *   Array(string $settingName => mixed $value).
   */
  protected $combined;

  /**
   * @var array
   */
  protected $values;

  /**
   * @param int $domainId
   *   The domain for which we want settings.
   * @param int|null $contactId
   *   The contact for which we want settings. Use NULL for domain settings.
   */
  public function __construct($domainId, $contactId) {
    $this->domainId = $domainId;
    $this->contactId = $contactId;
    $this->values = [];
    $this->combined = NULL;
  }

  /**
   * Set/replace the default values.
   *
   * @param array $defaults
   *   Array(string $settingName => mixed $value).
   * @return SettingsBag
   */
  public function loadDefaults($defaults) {
    $this->defaults = $defaults;
    $this->combined = NULL;
    return $this;
  }

  /**
   * Set/replace the mandatory values.
   *
   * @param array $mandatory
   *   Array(string $settingName => mixed $value).
   * @return SettingsBag
   */
  public function loadMandatory($mandatory) {
    $this->mandatory = $mandatory;
    $this->combined = NULL;
    return $this;
  }

  /**
   * Load all explicit settings that apply to this domain or contact.
   *
   * @return SettingsBag
   */
  public function loadValues() {
    // Note: Don't use DAO child classes. They require fields() which require
    // translations -- which are keyed off settings!

    $this->values = [];
    $this->combined = NULL;

    $isUpgradeMode = \CRM_Core_Config::isUpgradeMode();

    // Only query table if it exists.
    if (!$isUpgradeMode || \CRM_Core_DAO::checkTableExists('civicrm_setting')) {
      $dao = \CRM_Core_DAO::executeQuery($this->createQuery()->toSQL());
      while ($dao->fetch()) {
        $this->values[$dao->name] = ($dao->value !== NULL) ? \CRM_Utils_String::unserialize($dao->value) : NULL;
      }
    }

    return $this;
  }

  /**
   * Add a batch of settings. Save them.
   *
   * @param array $settings
   *   Array(string $settingName => mixed $settingValue).
   * @return SettingsBag
   */
  public function add(array $settings) {
    foreach ($settings as $key => $value) {
      $this->set($key, $value);
    }
    return $this;
  }

  /**
   * Get a list of all effective settings.
   *
   * @return array
   *   Array(string $settingName => mixed $settingValue).
   */
  public function all() {
    if ($this->combined === NULL) {
      $this->combined = $this->combine(
        [$this->defaults, $this->values, $this->mandatory]
      );
      // computeVirtual() depends on completion of preceding pass.
      $this->combined = $this->combine(
        [$this->combined, $this->computeVirtual()]
      );
    }
    return $this->combined;
  }

  /**
   * Determine the effective value.
   *
   * @param string $key
   * @return mixed
   */
  public function get($key) {
    $all = $this->all();
    return $all[$key] ?? NULL;
  }

  /**
   * Determine the default value of a setting.
   *
   * @param string $key
   *   The simple name of the setting.
   * @return mixed|NULL
   */
  public function getDefault($key) {
    return $this->defaults[$key] ?? NULL;
  }

  /**
   * Determine the explicitly designated value, regardless of
   * any default or mandatory values.
   *
   * @param string $key
   *   The simple name of the setting.
   * @return mixed|NULL
   */
  public function getExplicit($key) {
    return ($this->values[$key] ?? NULL);
  }

  /**
   * Determine the mandatory value of a setting.
   *
   * @param string $key
   *   The simple name of the setting.
   * @return mixed|NULL
   */
  public function getMandatory($key) {
    return $this->mandatory[$key] ?? NULL;
  }

  /**
   * Alias of hasExplicit retained for backwards compatibility
   *
   * @deprecated
   *
   * @param string $key
   *   The simple name of the setting.
   * @return bool
   */
  public function hasExplict($key) {
    \CRM_Core_Error::deprecatedFunctionWarning('hasExplicit (spelt correctly)');
    return $this->hasExplicit($key);
  }

  /**
   * Determine if the entity has explicitly designated a value.
   *
   * Note that get() may still return other values based on
   * mandatory values or defaults.
   *
   * @param string $key
   *   The simple name of the setting.
   * @return bool
   */
  public function hasExplicit($key) {
    // NULL means no designated value.
    return isset($this->values[$key]);
  }

  /**
   * Removes any explicit settings. This restores the default.
   *
   * @param string $key
   *   The simple name of the setting.
   * @return SettingsBag
   */
  public function revert($key) {
    // It might be better to DELETE (to avoid long-term leaks),
    // but setting NULL is simpler for now.
    return $this->set($key, NULL);
  }

  /**
   * Add a single setting. Save it.
   *
   * @param string $key
   *   The simple name of the setting.
   * @param mixed $value
   *   The new, explicit value of the setting.
   * @return SettingsBag
   */
  public function set($key, $value) {
    if ($this->updateVirtual($key, $value)) {
      return $this;
    }
    $this->setDb($key, $value);
    return $this;
  }

  /**
   * Get a list of all explicitly assigned values.
   *
   * @return array
   */
  public function exportValues(): array {
    return $this->values;
  }

  /**
   * Replace the list of all explicitly assigned values.
   *
   * @param array $newValues
   *   Full list of all settings.
   * @return void
   */
  public function importValues(array $newValues): void {
    $currentValues = $this->exportValues();

    foreach ($this->getVirtualKeys() as $key) {
      unset($newValues[$key], $currentValues[$key]);
    }

    $revertKeys = array_diff(array_keys($currentValues), array_keys($newValues));
    foreach ($revertKeys as $key) {
      $this->revert($key);
    }

    foreach ($newValues as $key => $value) {
      $this->set($key, $value);
    }
  }

  private function getVirtualKeys(): array {
    return ['contribution_invoice_settings'];
  }

  /**
   * Update a virtualized/deprecated setting.
   *
   * Temporary handling for phasing out contribution_invoice_settings.
   *
   * Until we have transitioned we need to handle setting & retrieving
   * contribution_invoice_settings.
   *
   * Once removed from core we will add deprecation notices & then remove this.
   *
   * https://lab.civicrm.org/dev/core/issues/1558
   *
   * @param string $key
   * @param array $value
   * @return bool
   *   TRUE if $key is a virtualized setting. FALSE if it is a normal setting.
   */
  public function updateVirtual($key, $value) {
    if ($key === 'contribution_invoice_settings') {
      \CRM_Core_Error::deprecatedWarning('Invoicing settings should be directly accessed - eg Civi::setting()->set("invoicing")');
      foreach (SettingsBag::getContributionInvoiceSettingKeys() as $possibleKeyName => $settingName) {
        $keyValue = $value[$possibleKeyName] ?? '';
        if ($possibleKeyName === 'invoicing' && is_array($keyValue)) {
          $keyValue = $keyValue['invoicing'];
        }
        $this->set($settingName, $keyValue);
      }
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Determine the values of any virtual/computed settings.
   *
   * @return array
   */
  public function computeVirtual() {
    $contributionSettings = [];
    foreach (SettingsBag::getContributionInvoiceSettingKeys() as $keyName => $settingName) {
      switch ($keyName) {
        case 'invoicing':
          $contributionSettings[$keyName] = $this->get($settingName) ? [$keyName => 1] : 0;
          break;

        default:
          $contributionSettings[$keyName] = $this->get($settingName);
          break;
      }
    }
    return array_merge(
        ['contribution_invoice_settings' => $contributionSettings],
        $this->interpolateDsnSettings('civicrm'),
        // TODO: provide equivalent component settings for CIVICRM_UF_DSN
        // $this->interpolateDsnSettings('civicrm_uf')
    );
  }

  /**
   * @return \CRM_Utils_SQL_Select
   */
  protected function createQuery() {
    $select = \CRM_Utils_SQL_Select::from('civicrm_setting')
      ->select('id, name, value, domain_id, contact_id, is_domain, component_id, created_date, created_id')
      ->where('(domain_id IS NULL OR domain_id = #id)', [
        'id' => $this->domainId,
      ]);
    if ($this->contactId === NULL) {
      $select->where('contact_id IS NULL');
    }
    else {
      $select->where('contact_id = #id', [
        'id' => $this->contactId,
      ]);
      $select->where('is_domain = 0');
    }
    return $select;
  }

  /**
   * Combine a series of arrays, excluding any
   * null values. Later values override earlier
   * values.
   *
   * @param array $arrays
   *   List of arrays to combine.
   * @return array
   */
  protected function combine($arrays) {
    $combined = [];
    foreach ($arrays as $array) {
      foreach ($array as $k => $v) {
        if ($v !== NULL) {
          $combined[$k] = $v;
        }
      }
    }
    return $combined;
  }

  /**
   * Update the DB record for this setting.
   *
   * @param string $name
   *   The simple name of the setting.
   * @param mixed $value
   *   The new value of the setting.
   */
  protected function setDb($name, $value) {
    $fields = [];
    $fieldsToSet = \CRM_Core_BAO_Setting::validateSettingsInput([$name => $value], $fields);
    //We haven't traditionally validated inputs to setItem, so this breaks things.
    //foreach ($fieldsToSet as $settingField => &$settingValue) {
    //  self::validateSetting($settingValue, $fields['values'][$settingField]);
    //}

    $metadata = $fields['values'][$name];

    // this should probably be higher in the Setting api layer as well
    if ($metadata['is_constant'] ?? FALSE) {
      $error = "{$metadata['title']} is a system constant. It can only be set in civicrm.settings.php";

      if ($metadata['is_env_loadable'] ?? FALSE) {
        $fqn = $metadata['global_name'] ?? '(ENV VAR NAME MISSING)';
        $error .= " or using the environment variable {$fqn}";
      }
      $error .= ".";
      throw new \CRM_Core_Exception($error);
    }

    $dao = new \CRM_Core_DAO_Setting();
    $dao->name = $name;
    $dao->is_domain = 0;
    // Contact-specific settings
    if ($this->contactId) {
      $dao->contact_id = $this->contactId;
      $dao->domain_id = $this->domainId;
    }
    // Domain-specific settings. For legacy support this is assumed to be TRUE if not set
    elseif ($metadata['is_domain'] ?? TRUE) {
      $dao->is_domain = 1;
      $dao->domain_id = $this->domainId;
    }
    $dao->find(TRUE);
    $oldValue = \CRM_Utils_String::unserialize($dao->value);

    // Call 'on_change' listeners. It would be nice to only fire when there's
    // a genuine change in the data. However, PHP developers have mixed
    // expectations about whether 0, '0', '', NULL, and FALSE represent the same
    // value, so there's no universal way to determine if a change is genuine.
    if (isset($metadata['on_change'])) {
      foreach ($metadata['on_change'] as $callback) {
        call_user_func(
          \Civi\Core\Resolver::singleton()->get($callback),
          $oldValue,
          $value,
          $metadata,
          $this->domainId
        );
      }
    }

    if (!is_array($value) && \CRM_Utils_System::isNull($value)) {
      $dao->value = 'null';
      $value = NULL;
    }
    else {
      $dao->value = serialize($value);
    }

    if (!isset(\Civi::$statics[__CLASS__]['upgradeMode'])) {
      \Civi::$statics[__CLASS__]['upgradeMode'] = \CRM_Core_Config::isUpgradeMode();
    }
    if (\Civi::$statics[__CLASS__]['upgradeMode'] && \CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_setting', 'group_name')) {
      $dao->group_name = 'placeholder';
    }

    $dao->created_date = \CRM_Utils_Time::getTime('YmdHis');

    $session = \CRM_Core_Session::singleton();
    if (\CRM_Contact_BAO_Contact_Utils::isContactId($session->get('userID'))) {
      $dao->created_id = $session->get('userID');
    }

    if ($dao->id) {
      $dao->save();
    }
    else {
      // Cannot use $dao->save(); in upgrade mode (eg WP + Civi 4.4=>4.7), the DAO will refuse
      // to save the field `group_name`, which is required in older schema.
      \CRM_Core_DAO::executeQuery(\CRM_Utils_SQL_Insert::dao($dao)->toSQL());
    }

    $this->values[$name] = $value;
    $this->combined = NULL;

    // Call 'post_change' listeners after the value has been saved.
    // Unlike 'on_change', this will only fire if the oldValue and newValue are not equivalent (using == comparison)
    if ($value != $oldValue && !empty($metadata['post_change'])) {
      foreach ($metadata['post_change'] as $callback) {
        call_user_func(
          \Civi\Core\Resolver::singleton()->get($callback),
          $oldValue,
          $value,
          $metadata,
          $this->domainId
        );
      }
    }
  }

  /**
   * @return array
   */
  public static function getContributionInvoiceSettingKeys(): array {
    $convertedKeys = [
      'credit_notes_prefix' => 'credit_notes_prefix',
      'invoice_prefix' => 'invoice_prefix',
      'due_date' => 'invoice_due_date',
      'due_date_period' => 'invoice_due_date_period',
      'notes' => 'invoice_notes',
      'is_email_pdf'  => 'invoice_is_email_pdf',
      'tax_term' => 'tax_term',
      'tax_display_settings' => 'tax_display_settings',
      'invoicing' => 'invoicing',
    ];
    return $convertedKeys;
  }

  /**
   * Compute a missing DSN from its component parts or vice versa
   *
   * Note: defaults for civicrm_db_XXX will be used
   *
   * @param string $prefix
   *   The prefix of the DB setting group - ex: 'civicrm' or 'civicrm_uf'
   *
   * @return array
   *   Ex 1:
   *
   *   $prefix = 'civicrm'
   *   civicrm_db_dsn is NOT already set
   *   civicrm_db_user set to 'james'
   *   civicrm_db_password set to 'i<3#browns'
   *
   *    returns [
   *     'civicrm_db_dsn' => 'mysql://james:i%3C3%23browns@localhost:3306/civicrm',
   *   ]
   *
   *   Ex 2:
   *
   *   $prefix = 'civicrm_uf', civicrm_uf_db_dsn is set to 'mysql://my_user!:pass#word@host.name/db_name'
   *
   *    returns [
   *     'civicrm_uf_db_user' => 'my_user!',
   *     'civicrm_uf_db_password' => 'pass#word',
   *     'civicrm_uf_db_host' => 'host.name',
   *     'civicrm_uf_db_database' => 'db_name',
   *   ]
   */
  protected function interpolateDsnSettings(string $prefix): array {
    $computed = [];

    $dsn = $this->get($prefix . '_db_dsn');

    if ($dsn) {
      // if dsn is set explicitly, use this as the source of truth.
      // set the component parts in case anyone wants to read them individually
      $urlComponents = \DB::parseDSN($dsn);

      if (!$urlComponents) {
        // couldn't parse the dsn so we dont set the components
        // (it could be a socket rather than a url)
        return [];
      }

      $componentKeyMap = [
        'hostspec' => 'host',
        'database' => 'name',
        'username' => 'user',
        'password' => 'password',
        'port' => 'port',
      ];

      foreach ($componentKeyMap as $theirKey => $ourKey) {
        $settingName = $prefix . '_db_' . $ourKey;
        $value = $urlComponents[$theirKey] ?? NULL;

        if ($value) {
          $computed[$settingName] = $value;
        }
      }

      // for db name we need to parse the path
      $settingName = $prefix . '_db_name';

      $urlPath = $urlComponents['path'] ?? '';
      $dbName = trim($urlPath, '/');
      if ($dbName) {
        $computed[$settingName] = $dbName;
      }
      return $computed;
    }

    $componentValues = [];

    foreach (['host', 'name', 'user', 'password', 'port'] as $componentKey) {
      $value = $this->get($prefix . '_db_' . $componentKey);
      if (!$value) {
        // if missing a required key to compose the dsn, give up trying to interpolate
        // (we have defaults for all keys but password, so this is likely to be unset password
        // (but could be one of the other components has been explicitly nulled))
        return [];
      }
      $componentValues[$componentKey] = urlencode($value);
    }

    $dsn = "mysql://{$componentValues['user']}:{$componentValues['password']}@{$componentValues['host']}:{$componentValues['port']}/{$componentValues['name']}?new_link=true";
    $ssl = $this->get($prefix . '_db_ssl');
    if ($ssl) {
      $dsn .= '&' . $ssl;
    }
    $computed[$prefix . '_db_dsn'] = $dsn;

    return $computed;
  }

}
