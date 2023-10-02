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

    $roles = \Civi\Api4\Role::save(FALSE)
      ->setDefaults([
        'is_active' => TRUE,
      ])
      ->setRecords([
        [
          'name' => 'everyone',
          'label' => ts('Everyone, including anonymous users'),
          // Provide default open permissions
          'permissions' => [
            'CiviMail subscribe/unsubscribe pages',
            'make online contributions',
            'view event info',
            'register for events',
            'access password resets',
            'authenticate with password',
          ],
        ],
        [
          'name' => 'admin',
          'label' => ts('Administrator'),
          'permissions' => array_keys(\CRM_Core_SelectValues::permissions()),
        ],
      ])
      ->execute()->indexBy('name');

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
      'email'      => $adminEmail,
      'notify'     => FALSE,
      'contact_id' => $contactID,
    ];
    $userID = \CRM_Core_BAO_CMSUser::create($params, 'email');

    // Assign 'admin' role to user
    \Civi\Api4\User::update(FALSE)
      ->addWhere('id', '=', $userID)
      ->addValue('roles:name', ['admin'])
      ->execute();

    $message = "Created new user \"{$e->getModel()->extras['adminUser']}\" (user ID #$userID, contact ID #$contactID) with 'admin' role and ";
    $message .= empty($e->getModel()->extras['adminPassWasSpecified'])
    ? "random password \"" . ($e->getModel()->extras['adminPass']) . '"'
    : "specified password";
    \Civi::log()->notice($message);

  }, \Civi\Setup::PRIORITY_LATE);
