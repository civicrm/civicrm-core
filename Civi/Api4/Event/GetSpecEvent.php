<?php

namespace Civi\Api4\Event;

use Civi\Api4\Generic\AbstractAction;
use Symfony\Component\EventDispatcher\Event as BaseEvent;

class GetSpecEvent extends BaseEvent {
  /**
   * @var \Civi\Api4\Generic\AbstractAction
   */
  protected $request;

  /**
   * @param \Civi\Api4\Generic\AbstractAction $request
   */
  public function __construct(AbstractAction $request) {
    $this->request = $request;
  }

  /**
   * @return \Civi\Api4\Generic\AbstractAction
   */
  public function getRequest() {
    return $this->request;
  }

  /**
   * @param $request
   */
  public function setRequest(AbstractAction $request) {
    $this->request = $request;
  }

}
