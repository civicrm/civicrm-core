<?php
namespace Civi\Setup\Event;

/**
 * Check if the current user is authorized to perform installations.
 *
 * Event Name: 'civi.setup.checkAuthorized'
 */
class CheckAuthorizedEvent extends BaseSetupEvent {

  /**
   * @var bool
   */
  private $authorized = FALSE;

  /**
   * @return bool
   */
  public function isAuthorized() {
    return $this->authorized;
  }

  /**
   * @param bool $authorized
   */
  public function setAuthorized($authorized) {
    $this->authorized = $authorized;
  }

}
