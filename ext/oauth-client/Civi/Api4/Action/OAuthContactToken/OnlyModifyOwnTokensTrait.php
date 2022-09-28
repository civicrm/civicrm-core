<?php

namespace Civi\Api4\Action\OAuthContactToken;

trait OnlyModifyOwnTokensTrait {

  public function isAuthorized(): bool {
    if (\CRM_Core_Permission::check(['manage all OAuth contact tokens'])) {
      return TRUE;
    }
    if (!\CRM_Core_Permission::check(['manage my OAuth contact tokens'])) {
      return FALSE;
    }
    $loggedInContactID = \CRM_Core_Session::singleton()->getLoggedInContactID();
    foreach ($this->where as $clause) {
      [$field, $op, $val] = $clause;
      if ($field !== 'contact_id') {
        continue;
      }
      if (($op === '=' || $op === 'LIKE') && $val != $loggedInContactID) {
        return FALSE;
      }
      if ($op === 'IN' && $val != [$loggedInContactID]) {
        return FALSE;
      }
    }
    $this->addWhere('contact_id', '=', $loggedInContactID);
    return TRUE;
  }

}
