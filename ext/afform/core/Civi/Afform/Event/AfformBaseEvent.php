<?php
namespace Civi\Afform\Event;

use Symfony\Component\EventDispatcher\Event;

class AfformBaseEvent extends Event {

  /**
   * @var array
   *   The main 'Afform' record/configuration.
   */
  public $afform;

  /**
   * @var \Civi\Api4\Generic\AbstractAction
   */
  public $apiRequest;

  /**
   * AfformBaseEvent constructor.
   * @param array $afform
   * @param \Civi\Api4\Generic\AbstractAction $apiRequest
   */
  public function __construct(array $afform, \Civi\Api4\Generic\AbstractAction $apiRequest) {
    $this->afform = $afform;
    $this->apiRequest = $apiRequest;
  }

  /**
   * @return \Civi\Api4\Generic\AbstractAction
   */
  public function getApiRequest() {
    return $this->apiRequest;
  }

}
