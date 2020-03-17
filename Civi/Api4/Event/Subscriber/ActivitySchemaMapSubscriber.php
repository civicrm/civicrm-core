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


namespace Civi\Api4\Event\Subscriber;

use Civi\Api4\Event\Events;
use Civi\Api4\Event\SchemaMapBuildEvent;
use Civi\Api4\Service\Schema\Joinable\ActivityToActivityContactAssigneesJoinable;
use Civi\Api4\Service\Schema\Joinable\BridgeJoinable;
use Civi\Api4\Service\Schema\Joinable\Joinable;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ActivitySchemaMapSubscriber implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      Events::SCHEMA_MAP_BUILD => 'onSchemaBuild',
    ];
  }

  /**
   * @param \Civi\Api4\Event\SchemaMapBuildEvent $event
   */
  public function onSchemaBuild(SchemaMapBuildEvent $event) {
    $schema = $event->getSchemaMap();
    $table = $schema->getTableByName('civicrm_activity');

    $middleAlias = \CRM_Utils_String::createRandom(10, implode(range('a', 'z')));
    $middleLink = new ActivityToActivityContactAssigneesJoinable($middleAlias);

    $bridge = new BridgeJoinable('civicrm_contact', 'id', 'assignees', $middleLink);
    $bridge->setBaseTable('civicrm_activity_contact');
    $bridge->setJoinType(Joinable::JOIN_TYPE_ONE_TO_MANY);

    $table->addTableLink('contact_id', $bridge);
  }

}
