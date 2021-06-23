<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

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
