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
            'access CiviMail subscribe/unsubscribe pages',
            'make online contributions',
            'view event info',
            'register for events',
            'access password resets',
            'authenticate with password',
          ],
        ],
        [
          'name' => 'staff',
          'label' => ts('Staff'),
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
            // Standalone-defined permissions that have the same name as the cms: prefixed synthetic ones
            'administer users',
            'view user account',
            // The admninister CiviCRM data implicitly sets other permissions as well.
            // Such as, edit message templates and admnister dedupe rules.
            'administer CiviCRM Data',
          ],
        ],
        [
          'name' => 'admin',
          'label' => ts('Administrator'),
          'permissions' => [
            'all CiviCRM permissions and ACLs',
          ],
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
