<?php

namespace Civi\Api4\Event\Subscriber\Generic;

use Civi\API\Event\PrepareEvent;
use Civi\Api4\Generic\DAOCreateAction;

abstract class PreCreationSubscriber extends AbstractPrepareSubscriber {

  /**
   * @param \Civi\API\Event\PrepareEvent $event
   */
  public function onApiPrepare(PrepareEvent $event) {
    $apiRequest = $event->getApiRequest();
    if (!$apiRequest instanceof DAOCreateAction) {
      return;
    }

    $this->addDefaultCreationValues($apiRequest);
    if ($this->applies($apiRequest)) {
      $this->modify($apiRequest);
    }
  }

  /**
   * Modify the request
   *
   * @param \Civi\Api4\Generic\DAOCreateAction $request
   *
   * @return void
   */
  abstract protected function modify(DAOCreateAction $request);

  /**
   * Check if this subscriber should be applied to the request
   *
   * @param \Civi\Api4\Generic\DAOCreateAction $request
   *
   * @return bool
   */
  abstract protected function applies(DAOCreateAction $request);

  /**
   * Sets default values common to all creation requests
   *
   * @param \Civi\Api4\Generic\DAOCreateAction $request
   */
  protected function addDefaultCreationValues(DAOCreateAction $request) {
  }

}
