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
   * Post Install
   * - ensure necessary Role records exist
   * - ensure authx settings allow for login
   *
   * Note: the core installer will also create a default user
   * based on user default in StandaloneUsers.civi-setup.php,
   * which runs *after* this
   */
  public function postInstall() {

    // Ensure users can login with username/password via authx.
    Civi::settings()->set('authx_login_cred', array_unique(array_merge(
      Civi::settings()->get('authx_login_cred'),
      ['pass']
    )));

    // Ensure default roles exist
    \Civi\Api4\Role::save(FALSE)
      ->setMatch(['name'])
      ->setRecords([
        [
          'name' => CRM_Standaloneusers_BAO_Role::ANONYMOUS_ROLE_NAME,
          'label' => ts('Everyone, including anonymous users'),
          'is_active' => TRUE,
          // Provide default open permissions
          'permissions' => [
            'access CiviMail subscribe/unsubscribe pages',
            'make online contributions',
            'view event info',
            'register for events',
            'access password resets',
            'authenticate with password',
          ],
        ],
        [
          'name' => CRM_Standaloneusers_BAO_Role::SUPERADMIN_ROLE_NAME,
          'label' => ts('Administrator'),
          'is_active' => TRUE,
          'permissions' => [
            'all CiviCRM permissions and ACLs',
          ],
        ],
        [
          'name' => 'staff',
          'label' => ts('Staff'),
          'is_active' => TRUE,
          'permissions' => [
            'access AJAX API',
            'access CiviCRM',
            'access Contact Dashboard',
            'access uploaded files',
            'add contacts',
            'view my contact',
            'view all contacts',
            'edit all contacts',
            'edit my contact',
            'delete contacts',
            'import contacts',
            'access deleted contacts',
            'merge duplicate contacts',
            'edit groups',
            'manage tags',
            'administer Tagsets',
            'view all activities',
            'delete activities',
            'add contact notes',
            'view all notes',
            'access CiviContribute',
            'delete in CiviContribute',
            'edit contributions',
            'make online contributions',
            'view my invoices',
            'access CiviEvent',
            'delete in CiviEvent',
            'edit all events',
            'edit event participants',
            'register for events',
            'view event info',
            'view event participants',
            'gotv campaign contacts',
            'interview campaign contacts',
            'manage campaign',
            'release campaign contacts',
            'reserve campaign contacts',
            'sign CiviCRM Petition',
            'access CiviGrant',
            'delete in CiviGrant',
            'edit grants',
            'access CiviMail',
            'access CiviMail subscribe/unsubscribe pages',
            'delete in CiviMail',
            'view public CiviMail content',
            'access CiviMember',
            'delete in CiviMember',
            'edit memberships',
            'access all cases and activities',
            'access my cases and activities',
            'add cases',
            'delete in CiviCase',
            'access CiviPledge',
            'delete in CiviPledge',
            'edit pledges',
            'access CiviReport',
            'access Report Criteria',
            'administer reserved reports',
            'save Report Criteria',
            'profile create',
            'profile edit',
            'profile listings',
            'profile listings and forms',
            'profile view',
            'close all manual batches',
            'close own manual batches',
            'create manual batch',
            'delete all manual batches',
            'delete own manual batches',
            'edit all manual batches',
            'edit own manual batches',
            'export all manual batches',
            'export own manual batches',
            'reopen all manual batches',
            'reopen own manual batches',
            'view all manual batches',
            'view own manual batches',
            'access all custom data',
            'access contact reference fields',
            // standaloneusers provides concrete permissions in place of
            // the synthetic ones on other UF
            'cms:administer users',
            'cms:view user account',
            // The admninister CiviCRM data implicitly sets other permissions as well.
            // Such as, edit message templates and admnister dedupe rules.
            'administer CiviCRM Data',
          ],
        ],
      ])
      ->execute();
  }

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
