# Users, Roles, Permissions for Standalone CiviCRM

**⚠️ Do not use this extension if you have CiviCRM installed the normal way (e.g. on Drupal, WordPress, Joomla, Backdrop...)!**

This is only for people running [CiviCRM Standalone](https://github.com/civicrm/civicrm-standalone/) which is currently highly experimental, insecure and definitely NOT for production use!

Normally, CiviCRM sits atop a CMS which provides role-based authentication: users can login, users are granted different roles, roles are granted different permissions. But standalone doesn't have these structures and relies on this extension for them.

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

* PHP v7.4+
* CiviCRM (standalone)

## Getting started

First, get standalone set up - e.g. you can see the admin interface up and running.

Next configure AuthX from **Administer » System Settings » Authentication**. You'll need to add **User Password** to the **Acceptable credentials (HTTP Session Login) select. And hit Save.

Now you can install this extension from the command line. (Clone this repo into web/upload/ext/ then enable it with `cv en standaloneusers`).

On install, an account is created, user `admin`, and the password is printed on the console (if you install through the UI, the password is output in the Civi logs). The admin user is granted all permissions. Example:

```
% cv en standaloneusers
Enabling extension "standaloneusers"
Created New admin User 1 and contact 203 with password iLkPsffZYYA= and ALL permissions.
```

Now if you try to load your site it should fail: you've got no access rights.

At this stage, because you're moving from a system that had no concept of users to one that does, you'll need to clear your browser cookies for the site, otherwise login will get confused (You may see a "session already active" authx error.)

Done that? Then head to `/civicrm/login`, enter your credentials and hopefully you're now back in the admin interface!


## Conventions

From the `Civi\Auth\Standalone` class, the User.id is stored in the global `$loggedInUserId` and when there's a session, under the key `ufId`.
