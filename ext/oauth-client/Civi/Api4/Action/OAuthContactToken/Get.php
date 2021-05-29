<?php


namespace Civi\Api4\Action\OAuthContactToken;

class Get extends \Civi\Api4\Generic\DAOGetAction {

  protected function setDefaultWhereClause() {
    $this->applyContactTokenPermissions();
    parent::setDefaultWhereClause();
  }

  private function applyContactTokenPermissions() {
    if (!$this->getCheckPermissions()) {
      return;
    }
    if (\CRM_Core_Permission::check(['manage all OAuth contact tokens'])) {
      return;
    }
    if (\CRM_Core_Permission::check(['manage my OAuth contact tokens'])) {
      $loggedInContactID = \CRM_Core_Session::singleton()
        ->getLoggedInContactID();
      $this->addWhere('contact_id', '=', $loggedInContactID);
      return;
    }
    throw new \Civi\API\Exception\UnauthorizedException(ts('Insufficient permissions to get contact tokens'));
  }

}
