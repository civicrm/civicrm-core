<?php

namespace Civi\Api4\Event\Subscriber;

use Civi\Api4\Event\Events;
use Civi\Api4\Event\SchemaMapBuildEvent;
use Civi\Api4\Service\Schema\Joinable\ActivityToActivityContactAssigneesJoinable;
use Civi\Api4\Service\Schema\Joinable\BridgeJoinable;
use Civi\Api4\Service\Schema\Joinable\Joinable;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use \CRM_Utils_String as StringHelper;

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
   * @param SchemaMapBuildEvent $event
   */
  public function onSchemaBuild(SchemaMapBuildEvent $event) {
    $schema = $event->getSchemaMap();
    $table = $schema->getTableByName('civicrm_activity');

    $middleAlias = StringHelper::createRandom(10, implode(range('a', 'z')));
    $middleLink = new ActivityToActivityContactAssigneesJoinable($middleAlias);

    $bridge = new BridgeJoinable('civicrm_contact', 'id', 'assignees', $middleLink);
    $bridge->setBaseTable('civicrm_activity_contact');
    $bridge->setJoinType(Joinable::JOIN_TYPE_ONE_TO_MANY);

    $table->addTableLink('contact_id', $bridge);
  }

}
