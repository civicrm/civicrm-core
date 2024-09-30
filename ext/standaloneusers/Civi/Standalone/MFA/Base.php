<?php
namespace Civi\Standalone\MFA;

use Civi\Core\Event\GenericHookEvent;
use CRM_Core_Session;

class Base {

  public ?int $userID;

  public function __construct(int $userID) {
    $this->userID = $userID;
  }

  public function updatePendingLogin(array $changes): array {
    $p = CRM_Core_Session::singleton()->get('pendingLogin') ?? [];
    $p = array_merge($p, $changes);
    CRM_Core_Session::singleton()->set('pendingLogin', $p);
    return $p;
  }

  public function clearPendingLogin() {
    CRM_Core_Session::singleton()->set('pendingLogin', []);
  }

  /**
   * Checks if a given token is an enabled MFA class, and returns
   * the fully qualified class name (or NULL)
   */
  public static function classIsAvailable(string $shortClassName): ?string {

    if (!in_array($shortClassName, \Civi::settings()->get('standalone_mfa_enabled'))) {
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
   * Does the class exist on the system.
   */
  public static function classIsMFA(string $shortClassName): ?string {
    $mfaClass = "Civi\\Standalone\\MFA\\$shortClassName";
    return (is_subclass_of($mfaClass, 'Civi\\Standalone\\MFA\\MFAInterface') && class_exists($mfaClass));
  }

  /**
   * Returns an array of fully qualified or short class names that are available.
   *
   * Available here means:
   * - is configured in settings as available to users
   * - is actually an MFA class.
   */
  public static function getAvailableClasses(bool $short = FALSE): array {
    $list = [];
    foreach (\Civi::settings()->get('standalone_mfa_enabled') as $shortClassName) {
      $fqcn = Base::classIsAvailable($shortClassName);
      if ($fqcn) {
        $list[] = $short ? $shortClassName : $fqcn;
      }
    }
    return $list;
  }

  /**
   * This is the options callback for the standalone_mfa_enabled
   * setting.
   *
   */
  public static function getMFAclasses(): array {
    $mfas = ['TOTP'];
    // Create an event object with all the data you wan to pass in.
    $event = GenericHookEvent::create(['mfaClasses' => &$mfas]);
    \Civi::dispatcher()->dispatch('civi.standalone.altermfaclasses', $event);
    // Check the list looks ok.
    $legit = [];
    foreach ($mfas as $shortClassName) {
      $mfaClass = "Civi\\Standalone\\MFA\\$shortClassName";
      if (is_subclass_of($mfaClass, 'Civi\\Standalone\\MFA\\MFAInterface') && class_exists($mfaClass)) {
        // The code is available, all good.
        $legit[$shortClassName] = $shortClassName;
      }
    }
    return $legit;
  }

  /**
   * Fetch the array of pending login data (userID, expiry, ...)
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
