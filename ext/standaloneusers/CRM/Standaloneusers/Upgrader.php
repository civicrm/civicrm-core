<?php
use CRM_Standaloneusers_ExtensionUtil as E;
use Civi\Api4\Navigation;

/**
 * Collection of upgrade steps.
 */
class CRM_Standaloneusers_Upgrader extends CRM_Extension_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Ensure that we're installing on suitable environment.
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  public function preInstall(): void {
    $entity = include __DIR__ . '/../../schema/User.entityType.php';
    $tableName = 'civicrm_uf_match';
    $ctx = new CRM_Queue_TaskContext();
    foreach ($entity['getFields']() as $fieldName => $fieldSpec) {
      // We can't run the next line in preInstall - so it's contents are copied here.
      // CRM_Upgrade_Incremental_Base::alterSchemaField(NULL, 'User', $fieldName, $params);
      $fieldSql = Civi::schemaHelper()->arrayToSql($fieldSpec);
      if (CRM_Core_BAO_SchemaHandler::checkIfFieldExists($tableName, $fieldName, FALSE)) {
        CRM_Upgrade_Incremental_Base::alterColumn($ctx, $tableName, $fieldName, $fieldSql, !empty($fieldSpec['localizable']));
      }
      else {
        CRM_Upgrade_Incremental_Base::addColumn($ctx, $tableName, $fieldName, $fieldSql, !empty($fieldSpec['localizable']));
      }
    }
  }

  /**
   * Example: Run an external SQL script when the module is installed.
   *
   * public function install() {
   * $this->executeSqlFile('sql/myinstall.sql');
   * }
   *
   * /**
   * Example: Work with entities usually not available during the install step.
   *
   * This method can be used for any post-install tasks. For example, if a step
   * of your installation depends on accessing an entity that is itself
   * created during the installation (e.g., a setting or a managed entity), do
   * so here to avoid order of operation problems.
   */
  public function postInstall() {

    // Ensure users can login with username/password via authx.
    Civi::settings()->set('authx_login_cred', array_unique(array_merge(
      Civi::settings()->get('authx_login_cred'),
      ['pass']
    )));

    // `standaloneusers` is installed as part of the overall install process for `Standalone`.
    // A subsequent step will configure some default users (*depending on local options*).
    // See also: `StandaloneUsers.civi-setup.php`
  }

  /**
   * Example: Run an external SQL script when the module is uninstalled.
   */
  // public function uninstall() {
  //  $this->executeSqlFile('sql/myuninstall.sql');
  // }

  /**
   * On enable:
   * - disable the user sync menu item
   */
  public function enable() {
    // standaloneusers is incompatible with user sync, so disable this nav menu item
    Navigation::update(FALSE)
      ->addWhere('url', '=', 'civicrm/admin/synchUser?reset=1')
      ->addValue('is_active', FALSE)
      ->execute();
  }

  /**
   * On disable:
   * - re-enable the user sync menu item
   */
  public function disable() {
    // reinstate user sync menu item
    Navigation::update(FALSE)
      ->addWhere('url', '=', 'civicrm/admin/synchUser?reset=1')
      ->addValue('is_active', TRUE)
      ->execute();
  }

  public function upgrade_5692(): bool {
    CRM_Core_DAO::executeQuery(<<<SQL
      CREATE TABLE IF NOT EXISTS `civicrm_totp` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique TOTP ID',
        `user_id` int(10) unsigned NOT NULL COMMENT 'Reference to User (UFMatch) ID',
        `seed` varchar(512) NOT NULL,
        `hash` varchar(20) NOT NULL DEFAULT '\"sha1\"',
        `period` INT(1) UNSIGNED NOT NULL DEFAULT '30',
        `length` INT(1) UNSIGNED NOT NULL DEFAULT '6',
        PRIMARY KEY (`id`)
      )
      SQL);
    return TRUE;
  }

  /**
   * Example: Run a couple simple queries.
   *
   * @return TRUE on success
   * @throws Exception
   */
  // public function upgrade_4200(): bool {
  //   $this->ctx->log->info('Applying update 4200');
  //   CRM_Core_DAO::executeQuery('UPDATE foo SET bar = "whiz"');
  //   CRM_Core_DAO::executeQuery('DELETE FROM bang WHERE willy = wonka(2)');
  //   return TRUE;
  // }


  /**
   * Example: Run an external SQL script.
   *
   * @return TRUE on success
   * @throws Exception
   */
  // public function upgrade_4201(): bool {
  //   $this->ctx->log->info('Applying update 4201');
  //   // this path is relative to the extension base dir
  //   $this->executeSqlFile('sql/upgrade_4201.sql');
  //   return TRUE;
  // }


  /**
   * Example: Run a slow upgrade process by breaking it up into smaller chunk.
   *
   * @return TRUE on success
   * @throws Exception
   */
  // public function upgrade_4202(): bool {
  //   $this->ctx->log->info('Planning update 4202'); // PEAR Log interface

  //   $this->addTask(E::ts('Process first step'), 'processPart1', $arg1, $arg2);
  //   $this->addTask(E::ts('Process second step'), 'processPart2', $arg3, $arg4);
  //   $this->addTask(E::ts('Process second step'), 'processPart3', $arg5);
  //   return TRUE;
  // }
  // public function processPart1($arg1, $arg2) { sleep(10); return TRUE; }
  // public function processPart2($arg3, $arg4) { sleep(10); return TRUE; }
  // public function processPart3($arg5) { sleep(10); return TRUE; }

  /**
   * Example: Run an upgrade with a query that touches many (potentially
   * millions) of records by breaking it up into smaller chunks.
   *
   * @return TRUE on success
   * @throws Exception
   */
  // public function upgrade_4203(): bool {
  //   $this->ctx->log->info('Planning update 4203'); // PEAR Log interface

  //   $minId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contribution');
  //   $maxId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contribution');
  //   for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
  //     $endId = $startId + self::BATCH_SIZE - 1;
  //     $title = E::ts('Upgrade Batch (%1 => %2)', array(
  //       1 => $startId,
  //       2 => $endId,
  //     ));
  //     $sql = '
  //       UPDATE civicrm_contribution SET foobar = whiz(wonky()+wanker)
  //       WHERE id BETWEEN %1 and %2
  //     ';
  //     $params = array(
  //       1 => array($startId, 'Integer'),
  //       2 => array($endId, 'Integer'),
  //     );
  //     $this->addTask($title, 'executeSql', $sql, $params);
  //   }
  //   return TRUE;
  // }

  /**
   * Create table civicrm_session
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_5691(): bool {
    $this->ctx->log->info('Applying update 5691');
    $this->executeSqlFile('sql/upgrade_5691.sql');
    return TRUE;
  }

}
