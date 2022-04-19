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
 * Class SystemInstallEvent
 * @package Civi\API\Event
 */
class SystemInstallEvent extends GenericHookEvent {

  /**
   * The SystemInstallEvent fires once after installation - during the first page-view.
   *
   * @deprecated - You may simply use the event name directly. dev/core#1744
   */
  const EVENT_NAME = 'civi.core.install';

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see \CRM_Utils_Hook::eventDefs
   */
  public static function hookEventDefs($e) {
    $e->inspector->addEventClass('civi.core.install', __CLASS__);
  }

}
