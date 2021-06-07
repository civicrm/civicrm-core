<?php

namespace Civi\API\Event;

/**
 * Trait AuthorizedTrait
 * @package Civi\API\Event
 */
trait AuthorizedTrait {

  /**
   * @var bool|null
   *   - TRUE: The action is explicitly authorized.
   *   - FALSE: The action is explicitly prohibited.
   *   - NULL: The authorization status has not been determined.
   */
  private $authorized = NULL;

  /**
   * Mark the request as authorized.
   *
   * @return static
   */
  public function authorize() {
    $this->authorized = TRUE;
    return $this;
  }

  /**
   * @return bool|null
   *   TRUE if the request has been authorized.
   */
  public function isAuthorized(): ?bool {
    return $this->authorized;
  }

  /**
   * Change the authorization status.
   *
   * @param bool|null $authorized
   * @return static
   */
  public function setAuthorized(?bool $authorized) {
    $this->authorized = $authorized;
    return $this;
  }

}
