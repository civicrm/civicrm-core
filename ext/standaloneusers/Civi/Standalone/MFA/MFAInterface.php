<?php
namespace Civi\Standalone\MFA;

interface MFAInterface {

  /**
   * Returns an appropriate URL for a user to go to
   * to either provide or set-up this MFA after
   * correctly entering their username and password.
   */
  public function getFormUrl(): string;

  /**
   * Returns whether this MFA is configured for the user.
   */
  public function userHasCompletedSetup(): bool;

  public function checkMFAData($data):bool;

  /**
   * Handle the User.login request with MFA class + data.
   *
   * @return bool
   *   Should login continue?
   */
  public function processMFAAttempt(array $pending, $code): bool;

}
