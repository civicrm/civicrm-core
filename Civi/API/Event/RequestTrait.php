<?php

namespace Civi\API\Event;

/**
 * Trait RequestTrait
 * @package Civi\API\Event
 *
 * Most events emitted by the API subsystem should include information about the active API request.
 */
trait RequestTrait {

  /**
   * @var \Civi\Api4\Generic\AbstractAction|array
   *   The full description of the API request.
   *
   * @see \Civi\API\Request::create
   */
  protected $apiRequest;

  /**
   * @return \Civi\Api4\Generic\AbstractAction|array
   */
  public function getApiRequest() {
    return $this->apiRequest;
  }

  /**
   * @param \Civi\Api4\Generic\AbstractAction|array $apiRequest
   *   The full description of the API request.
   * @return static
   */
  protected function setApiRequest($apiRequest) {
    $this->apiRequest = $apiRequest;
    return $this;
  }

  /**
   * Create a brief string identifying the entity/action. Useful for
   * pithy matching/switching.
   *
   * Ex: if ($e->getApiRequestSig() === '3.contact.get') { ... }
   *
   * @return string
   *   Ex: '3.contact.get'
   */
  public function getApiRequestSig(): string {
    return mb_strtolower($this->apiRequest['version'] . '.' . $this->apiRequest['entity'] . '.' . $this->apiRequest['action']);
  }

  /**
   * @return string
   *   Ex: 'Contact', 'Activity'
   */
  public function getEntityName(): string {
    return $this->apiRequest['entity'];
  }

  /**
   * @return string
   *   Ex: 'create', 'update'
   */
  public function getActionName(): string {
    return $this->apiRequest['action'];
  }

}
