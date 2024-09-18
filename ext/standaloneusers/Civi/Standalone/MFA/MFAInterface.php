<?php
namespace Civi\Standalone\MFA;

interface MFAInterface {

  public function getFormUrl(): string;

  public function checkMFAData($data):bool;

  /**
   * Handle the User.login request with MFA class + data.
   *
   * @return bool
   *   Should login continue?
   */
  public function processMFAAttempt(array $pending, $code): bool;

}
