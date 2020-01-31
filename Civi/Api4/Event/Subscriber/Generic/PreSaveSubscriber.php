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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 *
 */


namespace Civi\Api4\Event\Subscriber\Generic;

use Civi\API\Event\PrepareEvent;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\AbstractCreateAction;
use Civi\Api4\Generic\AbstractUpdateAction;

abstract class PreSaveSubscriber extends AbstractPrepareSubscriber {

  /**
   * @var string
   *   create|update|both
   */
  public $supportedOperation = 'both';

  /**
   * @param \Civi\API\Event\PrepareEvent $event
   */
  public function onApiPrepare(PrepareEvent $event) {
    $apiRequest = $event->getApiRequest();

    if ($apiRequest instanceof AbstractAction && $this->applies($apiRequest)) {
      if (
        ($apiRequest instanceof AbstractCreateAction && $this->supportedOperation !== 'update') ||
        ($apiRequest instanceof AbstractUpdateAction && $this->supportedOperation !== 'create')
      ) {
        $values = $apiRequest->getValues();
        $this->modify($values, $apiRequest);
        $apiRequest->setValues($values);
      }
    }
  }

  /**
   * Modify the item about to be saved
   *
   * @param array $item
   * @param \Civi\Api4\Generic\AbstractAction $request
   *
   */
  abstract protected function modify(&$item, AbstractAction $request);

  /**
   * Check if this subscriber should be applied to the request
   *
   * @param \Civi\Api4\Generic\AbstractAction $request
   *
   * @return bool
   */
  abstract protected function applies(AbstractAction $request);

}
