# Users, Roles, Permissions for Standalone CiviCRM

**⚠️ Do not use this extension if you have CiviCRM installed the normal way (e.g. on Drupal, WordPress, Joomla, Backdrop...)!**

This is only for people running [CiviCRM Standalone](https://github.com/civicrm/civicrm-standalone/) which is currently highly experimental, insecure and definitely NOT for production use!

Normally, CiviCRM sits atop a CMS which provides role-based authentication: users can login, users are granted different roles, roles are granted different permissions. But standalone doesn't have these structures and relies on this extension for them.

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

* PHP v7.4+
* CiviCRM (standalone)

## Getting started

Normal installation methods (such as `cv core:install` or `civibuild`) should enable `standaloneusers` by default.
The installer should display the default user account, such as:

```
  "adminUser": "super",
  "adminPass": "O5fAlyXgdEU",
  "adminEmail": "admin@localhost.localdomain"
```

If not, you may also find the default credentials in the local log.

```
[notice] Created new user "admin" (user ID #1, contact ID #203) with default password "admin" and ALL permissions.
```

If you try to load your site it should fail: you've got no access rights.

At this stage, because you're moving from a system that had no concept of users to one that does, you'll need to clear your browser cookies for the site, otherwise login will get confused (You may see a "session already active" authx error.)

Done that? Then head to `/civicrm/login`, enter your credentials and hopefully you're now back in the admin interface!

## Advanced

To customize the default credentials, pass any of these arguments to the installer:

```bash
cv core:install ... \
  -m 'extras.adminUser=zeta' \
  -m 'extras.adminPass=SECRET' \
  -m 'extras.adminEmail=zeta@example.com'
```

## Conventions

From the `Civi\Auth\Standalone` class, the User.id is stored in the global `$loggedInUserId` and when there's a session, under the key `ufId`.

## Have I Been Pwned integration

Standalone’s password change form integrates by default with the service at https://haveibeenpwned.com/Passwords
to check if a given password is known to have been compromised. This is controlled by the constant
`CIVICRM_HIBP_URL` - if you want to disable this, add this line to your `civicrm.settings.php` file:

    // Disable haveibeenpwned checking.
    define('CIVICRM_HIBP_URL', '');

