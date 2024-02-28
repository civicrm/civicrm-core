<?php
namespace Civi\Api4\Action\User;

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Generic\DAOUpdateAction;
use Civi\Api4\Generic\Result;

use CRM_Core_Permission;

class Update extends DAOUpdateAction {
  use WriteTrait;

  /**
   * Run some permission checks before doing the update.
   */
  public function _run(Result $result) {
    if ($this->getCheckPermissions()) {
      $loggedInUfID = (int) \CRM_Utils_System::getLoggedInUfID();
      if (!$loggedInUfID) {
        // Never allow update if not logged in.
        throw new UnauthorizedException("User.update API call when not logged in.");
      }
      if (!CRM_Core_Permission::check('cms:administer users')) {
        // Non-admin users should only be allowed to update their own record.
        $found = FALSE;
        foreach ($this->where as $where) {
          if ($where[0] === 'id' && $where[1] === '=' && $where[2] == $loggedInUfID) {
            $found = TRUE;
            break;
          }
        }
        if (!$found) {
          throw new UnauthorizedException("User.update called without 'cms:administer users' permission and without a where clause limiting to logged-in user.");
        }
      }
    }

    return parent::_run($result);
  }

}
