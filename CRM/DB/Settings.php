<?php

class CRM_DB_InvalidSettings extends Exception {};

class CRM_DB_Settings {
  public static $cividsn_to_settings_name = array(
    'database' => 'database',
    'dbsyntax' => 'driver',
    'hostspec' => 'host',
    'password' => 'password',
    'port' => 'port',
    'username' => 'username',
  );
  public $database;
  public $driver;
  public $host;
  public $password;
  public $port;
  public static $settings_to_doctrine_options = array(
    'database' => 'dbname',
    'driver' => 'driver',
    'host' => 'host',
    'password' => 'password',
    'port' => 'port',
    'username' => 'user',
  );
  public static $settings_to_pdo_options = array(
    'host' => 'host',
    'port' => 'port',
    'database' => 'dbname',
    'socket_path' => 'unix_socket',
  );
  public $socket_path;
  public $username;

  function __construct($settings_array = NULL) {
    if ($settings_array == NULL) {
      $civi_dsn = $this->findCiviDSN();
      $this->loadFromCiviDSN($civi_dsn);
    } else {
      $this->loadFromSettingsArray($settings_array);
    }
  }

  function findCiviDSN() {
    if (defined('CIVICRM_DSN')) {
      return CIVICRM_DSN;
    }
    $civi_dsn = getenv('CIVICRM_TEST_DSN');
    if ($civi_dsn !== FALSE) {
      return $civi_dsn;
    }
    throw new CRM_DB_InvalidSettings("CIVCRM_DSN is not defined and there is not CIVCRM_TEST_DSN environment variable");
  }

  function loadFromCiviDSN($civi_dsn) {
    require_once("DB.php");
    $parsed_dsn = DB::parseDSN($civi_dsn);
    foreach (static::$cividsn_to_settings_name as $key => $value) {
      if (array_key_exists($key, $parsed_dsn)) {
        $this->$value = $parsed_dsn[$key];
      }
    }
  }

  function loadFromSettingsArray($settings_array) {
    foreach ($settings_array as $key => $value) {
      $this->$key = $value;
    }
  }

  function toCiviDSN() {
    $civi_dsn = "mysql://{$this->username}:{$this->password}@{$this->host}";
    if ($this->port !== NULL) {
      $civi_dsn = "$civi_dsn:{$this->port}";
    }
    $civi_dsn = "$civi_dsn/{$this->database}?new_link=true";
    return $civi_dsn;
  }

  function toDoctrineArray() {
    $result = array();
    foreach (self::$settings_to_doctrine_options as $key => $value){
      $result[$value] = $this->$key;
    }
    $result['driver'] = "pdo_{$result['driver']}";
    return $result;
  }

  function toDrupalDSN() {
    $drupal_dsn = "{$this->driver}://{$this->username}:{$this->password}@{$this->host}";
    if ($this->port !== NULL) {
      $drupal_dsn = "$drupal_dsn:{$this->port}";
    }
    $drupal_dsn = "$drupal_dsn/{$this->database}";
    return $drupal_dsn;
  }

  function toPDODSN($options = array()) {
    $pdo_dsn = "{$this->driver}:";
    $pdo_dsn_options = array();
    $settings_to_pdo_options = static::$settings_to_pdo_options;
    if (CRM_Utils_Array::fetch('no_database', $options, FALSE)) {
      unset($settings_to_pdo_options['database']);
    }
    foreach ($settings_to_pdo_options as $settings_name => $pdo_name) {
      if ($this->$settings_name !== NULL) {
        $pdo_dsn_options[] = "{$pdo_name}={$this->$settings_name}";
      }
    }
    $pdo_dsn .= implode(';', $pdo_dsn_options);
    return $pdo_dsn;
  }
}
