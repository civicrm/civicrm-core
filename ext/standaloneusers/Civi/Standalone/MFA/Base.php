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

  /**
   * Checks if a given token is an enabled MFA class, and returns
   * the fully qualified class name (or NULL)
   */
  public static function classIsAvailable(string $shortClassName): ?string {

    if (!in_array($shortClassName, explode(',', \Civi::settings()->get('standalone_mfa_enabled')))) {
      // Class is not configured for use.
      return NULL;
    }

    $mfaClass = "Civi\\Standalone\\MFA\\$shortClassName";
    if (is_subclass_of($mfaClass, 'Civi\\Standalone\\MFA\\MFAInterface') && class_exists($mfaClass)) {
      // The code is available, all good.
      return $mfaClass;
    }

    return NULL;
  }

  /**
   * Returns an array of fully qualified class names that are available.
   */
  public static function getAvailableClasses(): array {
    $fullClassNames = [];
    foreach (explode(',', \Civi::settings()->get('standalone_mfa_enabled')) as $shortClassName) {
      $fqcn = Base::classIsAvailable($shortClassName);
      if ($fqcn) {
        $fullClassNames[] = $fqcn;
      }
    }
    return $fullClassNames;
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
