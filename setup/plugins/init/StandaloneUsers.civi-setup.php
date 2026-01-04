<?php
/**
 * @file
 *
 * On "Standalone" UF, default policy is to enable `standaloneusers` and create user with admin role.
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
    $e->getModel()->extras['adminPassWasSpecified'] = !empty($e->getModel()->extras['adminPass']);
    $e->getModel()->extras = array_merge($defaults, $e->getModel()->extras);
  });

\Civi\Setup::dispatcher()
  ->addListener('civi.setup.installDatabase', function (\Civi\Setup\Event\InstallDatabaseEvent $e) {
    if ($e->getModel()->cms !== 'Standalone' || !in_array('standaloneusers', $e->getModel()->extensions)) {
      return;
    }

    \Civi\Setup::log()->info(sprintf('[%s] Handle %s', basename(__FILE__), 'installDatabase'));

    $roleIds = \Civi\Api4\Role::get(FALSE)
      ->execute()
      ->indexBy('name')
      ->column('id');

    // Create contact+user for admin.
    $adminUser = $e->getModel()->extras['adminUser'];
    $adminPass = $e->getModel()->extras['adminPass'];
    $adminEmail = $e->getModel()->extras['adminEmail'];

    $contactID = \Civi\Api4\Contact::create(FALSE)
      ->addValue('contact_type', 'Individual')
      ->addValue('first_name', 'Standalone')
      ->addValue('last_name', 'Admin')
      ->execute()->single()['id'];

    // add email to the contact
    \Civi\Api4\Email::create(FALSE)
      ->addValue('contact_id', $contactID)
      ->addValue('email', $adminEmail)
      ->execute();

    // NOTE: normally uf_name would be derived automatically
    // from the contact email, and roles could be provided using
    // the facade on the User entity
    // BUT: User BAO pre hooks are not online in the installer
    // so we need to do them directly
    $userID = \Civi\Api4\User::create(FALSE)
      ->addValue('contact_id', $contactID)
      ->addValue('username', $adminUser)
      ->addValue('password', $adminPass)
      ->addValue('uf_name', $adminEmail)
      ->execute()->single()['id'];

    // Assign 'admin' role to user
    \Civi\Api4\UserRole::create(FALSE)
      ->addValue('user_id', $userID)
      ->addValue('role_id', $roleIds['admin'])
      ->execute();

    $message = "Created new user \"{$adminUser}\" (user ID #$userID, contact ID #$contactID) with 'admin' role and ";
    $message .= empty($e->getModel()->extras['adminPassWasSpecified'])
    ? "random password \"" . ($adminPass) . '"'
    : "specified password";
    \Civi::log()->notice($message);

  }, \Civi\Setup::PRIORITY_LATE);
