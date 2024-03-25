<?php
namespace Civi\Test;

use RuntimeException;

/**
 * Class Data
 */
class Data {

  /**
   * @return bool
   */
  public function populate() {
    \Civi\Test::asPreInstall(function() {
      \Civi\Test::schema()->truncateAll();

      \Civi\Test::schema()->setStrict(FALSE);

      // Ensure that when we populate the database it is done in utf8 mode
      \Civi\Test::execute('SET NAMES utf8mb4');
      $sqlDir = dirname(dirname(__DIR__)) . "/sql";

      if (!isset(\Civi\Test::$statics['locale_data'])) {
        $schema = new \CRM_Core_CodeGen_PhpSchema(\Civi\Test::codeGen());
        \Civi\Test::$statics['locale_data'] = $schema->generateLocaleDataSql('en_US');
      }

      $query2 = \Civi\Test::$statics['locale_data']["civicrm_data.mysql"];
      $query3 = file_get_contents("$sqlDir/test_data.mysql");
      $query4 = file_get_contents("$sqlDir/test_data_second_domain.mysql");
      if (\Civi\Test::execute($query2) === FALSE) {
        throw new RuntimeException("Cannot load civicrm_data.mysql. Aborting.");
      }
      if (\Civi\Test::execute($query3) === FALSE) {
        throw new RuntimeException("Cannot load test_data.mysql. Aborting.");
      }
      if (\Civi\Test::execute($query4) === FALSE) {
        throw new RuntimeException("Cannot load test_data.mysql. Aborting.");
      }

      unset($query, $query2, $query3);

      \Civi\Test::schema()->setStrict(TRUE);
      \Civi::reset();
    });

    civicrm_api('setting', 'create', ['installed' => 1, 'domain_id' => 'all', 'version' => 3]);

    // Rebuild triggers
    civicrm_api('system', 'flush', ['version' => 3, 'triggers' => 1]);

    \CRM_Core_BAO_ConfigSetting::setEnabledComponents([
      'CiviEvent',
      'CiviContribute',
      'CiviMember',
      'CiviMail',
      'CiviReport',
      'CiviPledge',
    ]);

    return TRUE;
  }

}
