<?php

namespace Civi\Api4\Action\User;

use Civi\API\Exception\UnauthorizedException;

use Civi\Standalone\Security;

trait WriteTrait {

  /**
   * If given, this is checked against the current user's password before the change is allowed.
   *
   * @var string
   */
  protected $actorPassword;

  /**
   * At this point we don't have the records we're going to update, we just have the
   * API values we're going to SET on (each) record that gets processed.
   *
   * We can do some basic checks.
   *
   * Do most of our complex permissions checks here.
   *
   * @param array $record
   * @throws \CRM_Core_Exception
   */
  protected function formatWriteValues(&$record) {

    if ($this->getCheckPermissions()) {
      // We must have a logged in user if we're checking permissions.
      $loggedInUserID = \CRM_Utils_System::getLoggedInUfID();
      if (!$loggedInUserID) {
        throw new UnauthorizedException("Unauthorized");
      }

      // We never allow one user to directly change the hashed password of another.
      // We assume that directly setting hashed_password would only ever be done by
      // integration/migration scripts which would not use checkPermissions()
      // Some other things should also not be changed by permissioned API call.
      $disallowChanging = ['hashed_password', 'when_created', 'when_last_accessed', 'when_updated'];
      $forbidden = array_intersect_key($record, array_flip($disallowChanging));
      if ($forbidden) {
        throw new UnauthorizedException("Not allowed to change " . implode(' or ', array_keys($forbidden)));
      }

      $requireAdminPermissionToChange = ['contact_id', 'roles', 'is_active'];
      $forbidden = array_intersect_key($record, array_flip($requireAdminPermissionToChange));
      if (!\CRM_Core_Permission::check(['cms:administer users']) && $forbidden) {
        throw new UnauthorizedException("Not allowed to change " . implode(' or ', array_keys($forbidden)));
      }
    }
    if (array_key_exists('password', $record)) {
      if (!empty($record['hashed_password'])) {
        throw new \CRM_Core_Exception("Ambiguous password parameters: Cannot pass password AND hashed_password.");
      }
      if (empty($record['password'])) {
        throw new \CRM_Core_Exception("Disallowing empty password.");
      }
    }
    parent::formatWriteValues($record);
  }

  /**
   * This is called with the values for a record fully loaded.
   *
   * Note that we will now have hashed_password, as well as possibly password.
   *
   */
  protected function validateValues() {
    if (!$this->getCheckPermissions()) {
      return;
    }
    $loggedInUserID = \CRM_Utils_System::getLoggedInUfID() ?? FALSE;
    $hasAdminPermission = \CRM_Core_Permission::check(['cms:administer users']);
    $authenticatedAsLoggedInUser = FALSE;
    // Check that we have the logged-in-user's password.
    if ($this->actorPassword && $loggedInUserID) {
      $user = \CRM_Core_Config::singleton()->userSystem->getUserById($loggedInUserID);
      if (!_authx_uf()->checkPassword($user['username'], $this->actorPassword)) {
        throw new UnauthorizedException("Incorrect password");
      }
      $authenticatedAsLoggedInUser = TRUE;
    }

    $records = ($this->getActionName() === 'save') ? $this->records : [$this->getValues()];
    foreach ($records as $values) {
      // Cases:
      // 1. Not logged in: only valid change is password, if we have a passwordResetToken.
      // 2. Logged in: if change includes password, require $authenticatedAsLoggedInUser
      //    2.1 if changing a different user to the logged in user, require $hasAdminPermission
      // 3. Logged in: change is without password
      //    3.1 if changing a different user to the logged in user, require $hasAdminPermission

      $changingPassword = array_key_exists('password', $values);
      if (!$loggedInUserID) {
        throw new UnauthorizedException("Unauthorized");
      }
      else {
        $changingOtherUser = intval($values['id'] ?? 0) !== $loggedInUserID;
        if ($changingOtherUser && !$hasAdminPermission) {
          throw new UnauthorizedException("You are not permitted to change other users' accounts.");
        }
        if ($changingPassword && !$authenticatedAsLoggedInUser) {
          throw new UnauthorizedException("Unauthorized");
        }
      }
    }
  }

  /**
   * Overrideable function to save items using the appropriate BAO function
   *
   * @param array[] $items
   *   Items already formatted by self::writeObjects
   * @return \CRM_Core_DAO[]
   *   Array of saved DAO records
   */
  protected function write(array $items) {
    foreach ($items as &$item) {
      // If given, convert password to hashed_password now.
      if (isset($item['password'])) {
        $item['hashed_password'] = Security::singleton()->hashPassword($item['password']);
        unset($item['password']);
      }
    }
    unset($item);

    // Call parent to do the main saving.
    $saved = parent::write($items);

    // Enforce uf_id === id
    foreach ($saved as $bao) {
      if ($bao->uf_id !== $bao->id) {
        $bao->uf_id = $bao->id;
        $bao->save();
      }
    }
    return $saved;
  }

}
