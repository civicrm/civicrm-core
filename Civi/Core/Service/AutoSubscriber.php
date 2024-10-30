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
namespace Civi\Core\Service;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * AutoSubscriber allows child classes to listen to events.
 *
 * Child classes must implement the `getSubscribedEvents` method, and the callbacks
 * it returns will be automatically registered.
 *
 * This class implies @service @internal on all subclasses.
 */
abstract class AutoSubscriber implements AutoServiceInterface, EventSubscriberInterface {

  use AutoServiceTrait;

}
