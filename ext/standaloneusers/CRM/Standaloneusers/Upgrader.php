<?php
use CRM_Standaloneusers_ExtensionUtil as E;

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
  public function onInstall() {
    $config = \CRM_Core_Config::singleton();
    if (!($config->userPermissionClass instanceof \CRM_Core_Permission_Standalone)) {
      throw new \CRM_Core_Exception("standaloneusers can only be installed on standalone");
    }
    if (!($config->userSystem instanceof \CRM_Utils_System_Standalone)) {
      throw new \CRM_Core_Exception("standaloneusers can only be installed on standalone");
    }
    parent::onInstall();
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

    Civi::settings()->set('authx_login_cred', array_unique(array_merge(
      Civi::settings()->get('authx_login_cred'),
      ['pass']
    )));

    $users = \Civi\Api4\User::get(FALSE)->selectRowCount()->execute()->countMatched();
    if ($users == 0) {

      CRM_Core_DAO::executeQuery('DELETE FROM civicrm_uf_match');

      // Create an admin contact.
      $contactID = \Civi\Api4\Contact::create(FALSE)
        ->setValues([
          'contact_type' => 'Individual',
          'first_name' => 'Standalone',
          'last_name' => 'Admin',
        ])
        ->execute()->first()['id'];
      $dummyEmail = 'admin@localhost.localdomain';

      // Create user
      $password = substr(base64_encode(random_bytes(8)), 0, 12);
      $params = [
        'cms_name'   => 'admin',
        'cms_pass'   => $password,
        'notify'     => FALSE,
        $dummyEmail => $dummyEmail,
        'contactID'  => $contactID,
      ];
      $userID = \CRM_Core_BAO_CMSUser::create($params, $dummyEmail);

      // Create Role
      $roleID = \Civi\Api4\Role::create(FALSE)->setValues(['name' => 'Administrator'])->execute()->first()['id'];

      // Assign role to user
      \Civi\Api4\UserRole::create(FALSE)->setValues(['role_id' => $roleID, 'user_id' => $userID])->execute();

      // Create permissions for role
      // @todo I expect there's a better way than this; this doesn't even bring in all the permissions.
      $records = [['permission' => 'authenticate with password']];
      foreach (array_keys(\CRM_Core_Permission::getCorePermissions()) as $permission) {
        $records[] = ['permission' => $permission];
      }
      \Civi\Api4\RolePermission::save(FALSE)
        ->setDefaults(['role_id' => $roleID])
        ->setRecords($records)
        ->execute();

      $message = "Created New admin User $userID and contact $contactID with password $password and ALL permissions.";
      \Civi::log()->notice($message);
      if (php_sapi_name() === 'cli') {
        print $message . "\n";
      }
      else {
        $authx = new \Civi\Authx\Standalone();
        $authx->loginSession($userID);
        CRM_Core_Session::setStatus($message . " You are logged in!", 'Standalone installed', 'alert');
      }
    }
  }

  /**
   * Example: Run an external SQL script when the module is uninstalled.
   */
  // public function uninstall() {
  //  $this->executeSqlFile('sql/myuninstall.sql');
  // }

  /**
   * Example: Run a simple query when a module is enabled.
   */
  // public function enable() {
  //  CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 1 WHERE bar = "whiz"');
  // }

  /**
   * Example: Run a simple query when a module is disabled.
   */
  // public function disable() {
  //   CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 0 WHERE bar = "whiz"');
  // }

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

}
