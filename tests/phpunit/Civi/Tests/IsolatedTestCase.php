<?php

namespace Civi\Tests;

class IsolatedTestCase extends \PHPUnit_Framework_Testcase {
  public static $db = NULL;

  public function checkPristine() {
    $result = self::$db->query("SELECT pristine FROM test_transaction_guard");
    $row = $result->fetch();
    if ($row[0] != '1') {
      throw new Exception("The database has been modified from its \"pristine\" state. Maybe there was a database command that forced a commit in the last test?");
    }
  }

  public static function setUpBeforeClass() {
    $settings_path = \CRM_Utils_Path::join(dirname(dirname(__DIR__)), 'CiviTest', 'civicrm.concentrator.settings.local.php');
    require($settings_path);
    $concentrator_db_settings = new \CRM_DB_Settings(array('settings_array' => $concentrator_civicrm_database_settings));
    self::$db = new \PDO($concentrator_db_settings->toPDODSN(), $concentrator_db_settings->username, $concentrator_db_settings->password);
    self::$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    self::$db->exec("DROP TABLE IF EXISTS test_transaction_guard");
    self::$db->exec("CREATE TABLE test_transaction_guard (pristine BOOLEAN)");
    self::$db->exec("INSERT INTO test_transaction_guard VALUES (1)");
    \CRM_DB_EntityManager::reset_entity_manager($concentrator_db_settings);
    \CRM_Core_Config::singleton(TRUE, FALSE, $concentrator_db_settings->toCiviDSN());
  }

  protected function setUp() {
    $this->checkPristine();
    self::$db->exec("BEGIN");
    self::$db->exec("UPDATE test_transaction_guard SET pristine = 0");
  }

  protected function tearDown() {
    self::$db->exec("ROLLBACK");
    $this->checkPristine();
  }
}
