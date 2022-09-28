<?php


namespace Civi\Api4\Action\OAuthContactToken;

use Civi\Api4\Generic\Result;

class Create extends \Civi\Api4\Generic\DAOCreateAction {

  public function _run(Result $result) {
    $this->fillContactIdFromTag();
    $this->assertPermissionForTokenContact();
    parent::_run($result);
  }

  private function fillContactIdFromTag(): void {
    if (isset($this->values['contact_id'])) {
      return;
    }

    $tag = $this->values['tag'] ?? NULL;

    if ('linkContact:' === substr($tag, 0, 12)) {
      $this->values['contact_id'] = substr($tag, 12);
    }
    elseif ('nullContactId' === $tag) {
      $this->values['contact_id'] = NULL;
    }
    elseif ('createContact' === $tag) {
      $contact = \CRM_OAuth_ContactFromToken::createContact($this->values);
      $this->values['contact_id'] = $contact['id'];
    }
    else {
      $this->values['contact_id'] = \CRM_Core_Session::singleton()
        ->getLoggedInContactID();
    }
  }

  /**
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  private function assertPermissionForTokenContact(): void {
    if (!$this->getCheckPermissions()) {
      return;
    }
    if (\CRM_Core_Permission::check('manage all OAuth contact tokens')) {
      return;
    }
    if (\CRM_Core_Permission::check('manage my OAuth contact tokens')) {
      $loggedInContactID = \CRM_Core_Session::singleton()
        ->getLoggedInContactID();
      $tokenContactID = $this->values['contact_id'] ?? NULL;
      if ($loggedInContactID == $tokenContactID) {
        return;
      }
    }
    throw new \Civi\API\Exception\UnauthorizedException(ts(
      "You do not have permission to create OAuth tokens for contact id %1",
      [1 => $tokenContactID]));
  }

}
