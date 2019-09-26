<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
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
