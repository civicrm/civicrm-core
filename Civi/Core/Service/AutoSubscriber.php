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
 * AutoSubscriber allows classes to listen to events.
 *
 * Classes must implement the `getSubscribedEvents` method, and the callbacks
 * it returns will be automatically registered.
 *
 * This is like `AutoServiceInterface` with @service @internal on all impl.
 */
interface AutoSubscriber extends EventSubscriberInterface {

}
