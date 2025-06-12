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
    $this->reKeyID();
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
    if (CIVICRM_UF === 'Drupal') {
      $this->copyDrupalUsersAndRoles();
    }
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

  private function copyDrupalUsersAndRoles(): void {
    $roles = user_roles(); // Returns array: rid => role name
    $role_permissions = user_role_permissions(array_keys($roles));
    foreach ($roles as $rid => $role) {
      // create roles matching d7 roles
      $rolePermissions = array_keys($role_permissions[$rid] ?? []);
      $name = $role;
      $label = $role;
      if ($role === 'anonymous user') {
        $rolePermissions[] = 'authenticate with password';
        $rolePermissions[] = 'access password resets';
        $label = 'Everyone, including anonymous users';
        $name = 'everyone';
      }
      $permissions = CRM_Core_DAO::serializeField($rolePermissions, \CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND);
      CRM_Core_DAO::executeQuery('
        INSERT INTO `civicrm_role` (`id`, `name`, `label`, `permissions`, `is_active`)
        SELECT %1, %2, %3, %4, 1
        ON DUPLICATE KEY UPDATE label = %3, permissions = %4, is_active = 1
      ', [
        1 => [$rid, 'Integer'],
        // if name = anonymous user set name = everyone
        2 => [$name, 'String'],
        3 => [$label, 'String'],
        // implode, bookend serialize
        4 => [$permissions, 'String']
      ]);

    }
    $users = entity_load('user');
    // -- create superuser role
    $superUserPermissions = CRM_Core_DAO::serializeField([
      "all CiviCRM permissions and ACLs",
      "access password resets",
    ], \CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND);

    CRM_Core_DAO::executeQuery('
       INSERT INTO `civicrm_role` (`name`, `label`, `permissions`, `is_active`)
        VALUES ("superuser", "Superuser", %1, 1)
        ON DUPLICATE KEY UPDATE label = "Superuser", permissions = %1, is_active = 1
        ', [1 => [$superUserPermissions, 'String']]
    );

    //  assign superuser role to User ID 1
    CRM_Core_DAO::executeQuery('
      INSERT INTO `civicrm_user_role` (`user_id`, `role_id`)
      SELECT 1, `id` FROM `civicrm_role` WHERE `name` = "superuser"');

    foreach ($users as $user) {
      if ($user->uid == 0
        || empty(CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_uf_match WHERE uf_id = %1', [1 => [$user->uid, 'Integer']]))) {
        continue;
      }
      CRM_Core_DAO::executeQuery("
        UPDATE `civicrm_uf_match`
        SET username = %1,
        `hashed_password` = %2,
        `when_created` = IF(%3 > 0, FROM_UNIXTIME(%3), NULL),
        `when_last_accessed` = IF(%4 > 0, FROM_UNIXTIME(%4), NULL),
        `when_updated` = IF(%4 > 0, FROM_UNIXTIME(%4), NULL),
        is_active = %5
        WHERE uf_id = %6
      ", [
        1 => [$user->name, 'String'],
        2 => [$user->pass, 'String'],
        3 => [$user->created, 'Integer'],
        4 => [$user->access, 'Integer'],
        5 => [$user->status, 'Integer'],
        6 => [$user->uid, 'Integer'],
      ]);
      foreach (array_keys($user->roles ?? []) as $roleID) {
        // assign roles after the above so the FK works (ie civicrm_user_role.user_id -> civicrm_user.id.
        CRM_Core_DAO::executeQuery('INSERT INTO `civicrm_user_role` (`user_id`, `role_id`) VALUES(%1, %2)', [
          1 => [$user->uid, 'Integer'],
          2 => [$roleID, 'Integer'],
        ]);
      }
    }
  }
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

  /**
   * @return void
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public function reKeyID(): void {
    // -- Standaloneusers expects uf_id to match id. In order to alter these
    // we need to follow a 2 step process to drop and then to re-add the primary key.
    CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_uf_match
     MODIFY id int(10) unsigned NOT NULL COMMENT 'Unique User ID',
     DROP PRIMARY KEY");
    CRM_Core_DAO::executeQuery("UPDATE civicrm_uf_match SET id = uf_id");
    CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_uf_match
       MODIFY id int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique User ID',
       ADD PRIMARY KEY (id)");
  }

}
