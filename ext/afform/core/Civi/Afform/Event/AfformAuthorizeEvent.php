<?php
namespace Civi\Afform\Event;

/**
 * Class AfformAuthorizeEvent
 * @package Civi\Afform\Event
 *
 * Perform a supplemental authorization check for the "Afform.prefill" and
 * "Afform.submit" actions.
 */
class AfformAuthorizeEvent extends AfformBaseEvent {

  /**
   * (Experimental) Should we bypass permission-enforcement on nested API calls?
   *
   * It's not clear if this framing will be good forever, but it seems OK for now.
   *
   * @var bool
   */
  private $checkNestedPermission = TRUE;

  /**
   * @var bool
   */
  private $authorized = FALSE;

  /**
   * Mark the request as authorized.
   *
   */
  public function authorize() {
    $this->authorized = TRUE;
    $this->stopPropagation();
    return $this;
  }

  /**
   * Mark the request as prohibited. No other party will be allowed to authorize it.
   */
  public function prohibit() {
    $this->authorized = FALSE;
    $this->stopPropagation();
    return $this;
  }

  /**
   * @return bool
   *   TRUE if the request has been authorized.
   */
  public function isAuthorized(): bool {
    return $this->authorized;
  }

  /**
   * @param bool $checkNestedPermission
   * @return $this
   */
  public function setCheckNestedPermission(bool $checkNestedPermission) {
    $this->checkNestedPermission = $checkNestedPermission;
    return $this;
  }

  /**
   * @return bool
   */
  public function getCheckNestedPermission(): bool {
    return $this->checkNestedPermission;
  }

}
