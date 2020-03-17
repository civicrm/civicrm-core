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
use Civi\API\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractPrepareSubscriber implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      Events::PREPARE => 'onApiPrepare',
    ];
  }

  /**
   * @param \Civi\API\Event\PrepareEvent $event
   */
  abstract public function onApiPrepare(PrepareEvent $event);

}
