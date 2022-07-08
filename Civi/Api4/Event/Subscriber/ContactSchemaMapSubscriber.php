<?php

namespace Civi\Api4\Event\Subscriber;

use Civi\Api4\Event\Events;
use Civi\Api4\Event\SchemaMapBuildEvent;
use Civi\Api4\Service\Schema\Joinable\Joinable;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContactSchemaMapSubscriber implements EventSubscriberInterface {

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
    $table = $schema->getTableByName('civicrm_contact');

    // Add links to primary & billing email, address, phone & im
    foreach (['email', 'address', 'phone', 'im'] as $ent) {
      foreach (['primary', 'billing'] as $type) {
        $link = new Joinable("civicrm_$ent", 'contact_id', "{$type}_$ent");
        $link->setBaseTable('civicrm_contact');
        $link->setJoinType(Joinable::JOIN_TYPE_ONE_TO_ONE);
        $link->addCondition("`{target_table}`.`is_$type` = 1");
        $table->addTableLink('id', $link);
      }
    }
  }

}
