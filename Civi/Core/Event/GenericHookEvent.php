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

namespace Civi\Core\Event;

/**
 * Class GenericHookEvent
 * @package Civi\Core\Event
 *
 * Note the Symfony Event class disappears in symfony 6, but in order
 * to also work in symfony 4 this needs to extend that class because in symfony
 * 4 the dispatch() function explicitly declares the parameter as type Event.
 */
if (class_exists('\Symfony\Component\EventDispatcher\Event')) {
  class GenericHookEvent extends \Symfony\Component\EventDispatcher\Event {
    use GenericHookEventTrait;

  }
}
else {
  class GenericHookEvent extends Symfony4Event {
    use GenericHookEventTrait;

  }
}
