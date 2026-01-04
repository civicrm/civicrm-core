<?php
namespace Civi\Setup;

/**
 * Class Model
 *
 * @package Civi\Setup
 *
 * The `Model` defines the main options and inputs that are used to configure
 * the installer.
 *
 * @property string $srcPath
 *   Path to CiviCRM-core source tree.
 *   Ex: '/var/www/sites/all/modules/civicrm'.
 * @property string $setupPath
 *   Path to CiviCRM-setup source tree.
 *   Ex: '/var/www/sites/all/modules/civicrm/setup'.
 * @property string $settingsPath
 *   Ex: '/var/www/sites/default/civicrm.settings.php'.
 * @property string $templateCompilePath
 *   Ex: '/var/www/sites/default/files/civicrm/templates_c'.
 * @property string $cms
 *   Ex: 'Backdrop', 'Drupal', 'Drupal8', 'Joomla', 'WordPress'.
 * @property string $cmsBaseUrl
 *   Ex: 'http://example.org/'.
 * @property array $db
 *   Ex: ['server'=>'localhost:3306', 'username'=>'admin', 'password'=>'s3cr3t', 'database'=>'mydb']
 * @property array $cmsDb
 *   Ex: ['server'=>'localhost:3306', 'username'=>'admin', 'password'=>'s3cr3t', 'database'=>'mydb']
 * @property string $siteKey
 *   Ex: 'abcd1234ABCD9876'.
 * @property string[] $credKeys
 *   Ex: ['::abcd1234ABCD9876'].
 * @property string[] $signKeys
 *   Ex: ['jwt-hs256::abcd1234ABCD9876'].
 * @property string $deployID
 *   Ex: '1234ABCD9876'.
 * @property string|NULL $lang
 *   The language of the default dataset.
 *   Ex: 'fr_FR'.
 * @property bool $syncUsers
 *   Whether to automatically create `Contact` records for each pre-existing CMS `User`
 * @property bool $loadGenerated
 *   UNSUPPORTED: Load example dataset (in lieu of the standard dataset).
 *   This was copied-in from the previous installer code, but it should probably be
 *   reconceived.
 * @property array $components
 *   Ex: ['CiviMail', 'CiviContribute', 'CiviEvent', 'CiviMember', 'CiviReport']
 * @property array $extensions
 *   Ex: ['org.civicrm.flexmailer', 'org.civicrm.shoreditch'].
 * @property array $paths
 *   List of hard-coded path-overrides.
 *   Ex: ['wp.frontend.base'=>['url'=>'http://example.org/']].
 * @property array $settings
 *   List of domain settings to apply.
 *   These are defaults during installation; they could be changed by the admin post-install via GUI or API.
 *   Ex: ['ajaxPopupsEnabled' => 0].
 * @property array $mandatorySettings
 *   List of hard-coded setting-overrides.
 *   These are mandatory settings which are hard-coded into the config file. Changing requires editing the file.
 *   This makes sense for path/URL settings that are generally system-local and not migrated between dev/prod/etc.
 *   Ex: ['ajaxPopupsEnabled' => 0].
 * @property array $extras
 *   Open-ended list of private, adhoc fields/flags/tags.
 *   Keys should be prefixed based on which plugin manages the field.
 *   Values must only be scalars (bool/int/string) and arrays.
 *   Ex: ['opt-in.version-check' => TRUE].
 * @property array $moFiles
 *   Open-ended list translations files which should be downloaded. Each entry is a url of an mo-file.
 *   Provide each entry with en_US langugae code. That code will be replaced with the actual language.
 *   The default is: ['https://download.civicrm.org/civicrm-l10n-core/mo/en_US/civicrm.mo']
 * @property bool $doNotCreateSettingsFile
 *   Flag to skip creation of civicrm.settings.php on install.
 *   Set to TRUE if you want to NOT create civicrm.settings.php on install.
 *   The default is FALSE, create settings file on install.
 */
class Model {

  protected $sorted = FALSE;
  protected $fields = array();
  protected $values = array();

  public function __construct() {
    $this->addField(array(
      'description' => 'Local path of the CiviCRM-core tree',
      'name' => 'srcPath',
      'type' => 'string',
    ));
    $this->addField(array(
      'description' => 'Local path of the CiviCRM-setup tree',
      'name' => 'setupPath',
      'type' => 'string',
    ));
    $this->addField(array(
      'description' => 'Local path to civicrm.settings.php',
      'name' => 'settingsPath',
      'type' => 'string',
    ));
    $this->addField(array(
      'description' => 'Local path to the PHP compilation cache',
      'name' => 'templateCompilePath',
      'type' => 'string',
    ));
    $this->addField(array(
      'description' => 'Symbolic name of the CMS/user-framework',
      'name' => 'cms',
      'type' => 'string',
    ));
    $this->addField(array(
      'description' => 'The CMS base URL',
      'name' => 'cmsBaseUrl',
      'type' => 'string',
    ));
    $this->addField(array(
      'description' => 'Credentials for Civi database',
      'name' => 'db',
      'type' => 'dsn',
    ));
    $this->addField(array(
      'description' => 'Credentials for CMS database',
      'name' => 'cmsDb',
      'type' => 'dsn',
    ));
    $this->addField(array(
      'description' => 'Site key',
      'name' => 'siteKey',
      'type' => 'string',
    ));
    $this->addField(array(
      'description' => 'Credential encryption keys',
      'name' => 'credKeys',
      'type' => 'array',
    ));
    $this->addField(array(
      'description' => 'Signing keys',
      'name' => 'signKeys',
      'type' => 'array',
    ));
    $this->addField(array(
      'description' => 'Load example data',
      'name' => 'loadGenerated',
      'type' => 'bool',
    ));
    $this->addField(array(
      'description' => 'Load users',
      'name' => 'syncUsers',
      'type' => 'bool',
    ));
    $this->addField(array(
      'description' => 'Language',
      'name' => 'lang',
      'type' => 'string',
      'options' => array(),
    ));
    $this->addField(array(
      'description' => 'List of CiviCRM components to enable',
      'name' => 'components',
      'type' => 'array',
      'value' => array(),
    ));
    $this->addField(array(
      'description' => 'List of CiviCRM extensions to enable',
      'name' => 'extensions',
      'type' => 'array',
      'value' => array(),
    ));
    $this->addField(array(
      'description' => 'List of mandatory path overrides.',
      'name' => 'paths',
      'type' => 'array',
      'value' => array(),
    ));
    $this->addField(array(
      'description' => 'List of setting overrides.',
      'name' => 'settings',
      'type' => 'array',
      'value' => array(),
    ));
    $this->addField(array(
      'description' => 'List of mandatory settings',
      'name' => 'mandatorySettings',
      'type' => 'array',
      'value' => array(),
    ));
    $this->addField(array(
      'description' => 'Open-ended list of private, adhoc fields/flags/tags',
      'name' => 'extras',
      'type' => 'array',
      'value' => array(),
    ));
    $this->addField(array(
      'description' => 'l10n download files. The [locale] will be replaced with the selected language.',
      'name' => 'moFiles',
      'type' => 'array',
      'value' => array(
        'civicrm.mo' => 'https://download.civicrm.org/civicrm-l10n-core/mo/[locale]/civicrm.mo',
      ),
    ));
    $this->addField([
      'description' => 'Option for installation process to skip creation of civicrm.settings.php',
      'name' => 'doNotCreateSettingsFile',
      'type' => 'bool',
      'value' => FALSE,
    ]);
  }

  /**
   * @param array $field
   *   - name: string
   *   - description: string
   *   - type: string. One of "checkbox", "string".
   *   - weight: int. (Default: 0)
   *   - visible: bool. (Default: TRUE)
   *   - value: mixed. (Default: NULL)
   * @return $this
   */
  public function addField($field) {
    $defaults = array(
      'weight' => 0,
      'visible' => TRUE,
    );
    $field = array_merge($defaults, $field);

    if (array_key_exists('value', $field) || !array_key_exists($field['name'], $this->values)) {
      $this->values[$field['name']] = $field['value'] ?? NULL;
      unset($field['value']);
    }

    $this->fields[$field['name']] = $field;

    $this->sorted = FALSE;
    return $this;
  }

  public function setField($field, $property, $value) {
    $this->fields[$field][$property] = $value;
    return $this;
  }

  /**
   * @param string $field
   *   The name of the field.
   *   Ex: 'cmsDb', 'lang'.
   * @param string $property
   *   A specific property of the field to load.
   *   Ex: 'name', 'description', 'type', 'options'.
   * @return mixed|NULL
   */
  public function getField($field, $property = NULL) {
    if ($property) {
      return $this->fields[$field][$property] ?? NULL;
    }
    else {
      return $this->fields[$field] ?? NULL;
    }
  }

  public function getFields() {
    if (!$this->sorted) {
      uasort($this->fields, function ($a, $b) {
        if ($a['weight'] < $b['weight']) {
          return -1;
        }
        if ($a['weight'] > $b['weight']) {
          return 1;
        }
        return strcmp($a['name'], $b['name']);
      });
    }
    return $this->fields;
  }

  /**
   * Set the values of multiple fields.
   *
   * @param array $values
   *   Ex: array('root' => '/var/www/sites/default/files/civicrm')
   * @return $this
   */
  public function setValues($values) {
    foreach ($values as $key => $value) {
      $this->values[$key] = $value;
    }
    return $this;
  }

  public function getValues() {
    return $this->values;
  }

  public function &__get($name) {
    return $this->values[$name];
  }

  public function __set($name, $value) {
    $this->values[$name] = $value;
  }

  public function __isset($name) {
    return isset($this->values[$name]);
  }

  public function __unset($name) {
    unset($this->values[$name]);
  }

}
