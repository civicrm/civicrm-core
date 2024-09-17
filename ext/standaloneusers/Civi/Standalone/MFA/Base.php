<?php
namespace Civi\Standalone\MFA;

use CRM_Core_Session;

class Base {

  public ?int $userID;

  public function __construct(int $userID) {
    $this->userID = $userID;
    // @todo expose the 120s timeout to config?
    CRM_Core_Session::singleton()->set('pendingLogin', ['userID' => $userID, 'expiry' => time() + 120]);
  }

  public static function classIsAvailable(string $shortClassName): ?string {
    $mfaClass = "Civi\\Standalone\\MFA\\$shortClassName";
    if (is_subclass_of($mfaClass, 'Civi\\Standalone\\MFA\\MFAInterface') && class_exists($mfaClass)) {
      return $mfaClass;
    }
    return NULL;
  }

  /**
   * Fetch the array of pending login data (userID, expiry)
   * if it exists and has not expired.
   *
   * If it's expired, drop it from the session.
   */
  public static function getPendingLogin(): ?array {
    $pending = \CRM_Core_Session::singleton()->get('pendingLogin');
    if (!$pending || !is_array($pending)
      || (($pending['expiry'] ?? 0) < time())
    ) {
      \CRM_Core_Session::singleton()->set('pendingLogin', []);
      return NULL;
    }
    return $pending;
  }

}
