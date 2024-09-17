<?php
namespace Civi\Standalone\MFA;

use CRM_Core_Session;

class Base {

  public ?int $userID;

  public function __construct(int $userID) {
    $this->userID = $userID;
    // @todo expose the 120s timeout to config?
    CRM_Core_Session::singleton()->set('pendingLogin', ['userID' => $this->userID, 'expiry' => time() + 120]);
  }

}
