<?php
/**
 * @file
 *
 * On "Standalone" UF, default policy is to enable `standaloneusers` and create user+role.
 */

if (!defined('CIVI_SETUP')) {
  exit("Installation plugins must only be loaded by the installer.\n");
}

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.init', function (\Civi\Setup\Event\InitEvent $e) {
    if ($e->getModel()->cms !== 'Standalone') {
      return;
    }

    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'init'));

    // Assign defaults. (These may be overridden by the agent performing installation.)
    $e->getModel()->extensions[] = 'standaloneusers';

    $toAlphanum = function ($bits) {
      return preg_replace(';[^a-zA-Z0-9];', '', base64_encode($bits));
    };
    $defaults = [
      'adminUser' => 'admin',
      'adminPass' => $toAlphanum(random_bytes(8)),
      'adminEmail' => 'admin@localhost.localdomain',
    ];
    $e->getModel()->extras = array_merge($defaults, $e->getModel()->extras);
  });

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.installDatabase', function (\Civi\Setup\Event\InstallDatabaseEvent $e) {
    if ($e->getModel()->cms !== 'Standalone' || !in_array('standaloneusers', $e->getModel()->extensions)) {
      return;
    }

    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'installDatabase'));

    // Create role with permissions
    $roleID = \Civi\Api4\Role::create(FALSE)->setValues(['name' => 'Administrator'])->execute()->first()['id'];
    // @todo I expect there's a better way than this; this doesn't even bring in all the permissions.
    $records = [['permission' => 'authenticate with password']];
    foreach (array_keys(\CRM_Core_Permission::getCorePermissions()) as $permission) {
      $records[] = ['permission' => $permission];
    }
    \Civi\Api4\RolePermission::save(FALSE)
      ->setDefaults(['role_id' => $roleID])
      ->setRecords($records)
      ->execute();

    // Create contact+user for admin.
    $contactID = \Civi\Api4\Contact::create(FALSE)
      ->setValues([
        'contact_type' => 'Individual',
        'first_name' => 'Standalone',
        'last_name' => 'Admin',
      ])
      ->execute()->first()['id'];
    $adminEmail = $e->getModel()->extras['adminEmail'];
    $params = [
      'cms_name'   => $e->getModel()->extras['adminUser'],
      'cms_pass'   => $e->getModel()->extras['adminPass'],
      'notify'     => FALSE,
      $adminEmail => $adminEmail,
      'contactID'  => $contactID,
    ];
    $userID = \CRM_Core_BAO_CMSUser::create($params, $adminEmail);

    // Assign role to user
    \Civi\Api4\UserRole::create(FALSE)->setValues(['role_id' => $roleID, 'user_id' => $userID])->execute();

    // TODO - If admin specified an explicit password, then we don't really need to log it.
    $message = "Created new user \"admin\" (user ID #$userID, contact ID #$contactID) with default password \"" . ($e->getModel()->extras['adminPass']) . "\" and ALL permissions.";
    \Civi::log()->notice($message);

  }, \Civi\Setup::PRIORITY_LATE);
