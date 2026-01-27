<?php

namespace Civi\Api4\Event\Subscriber;

use Civi\Api4\Event\SchemaMapBuildEvent;
use Civi\Api4\Service\Schema\Joinable\Joinable;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service civi.api4.contactSchema
 */
class ContactSchemaMapSubscriber extends \Civi\Core\Service\AutoService implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents(): array {
    return [
      'api.schema_map.build' => 'onSchemaBuild',
    ];
  }

  /**
   * This creates a joinable which gets exposed and rendered by:
   *
   * @see \Civi\Api4\Service\Spec\Provider\ContactGetSpecProvider
   *
   * @param \Civi\Api4\Event\SchemaMapBuildEvent $event
   */
  public function onSchemaBuild(SchemaMapBuildEvent $event) {
    $schema = $event->getSchemaMap();
    $table = $schema->getTableByName('civicrm_contact');

    // Add links to primary & billing email, address, phone & im
    foreach (['email', 'address', 'phone', 'im'] as $ent) {
      foreach (['primary', 'billing'] as $type) {
        $link = new Joinable("civicrm_$ent", 'contact_id', "{$ent}_$type");
        $link->setBaseTable('civicrm_contact');
        $link->setJoinType(Joinable::JOIN_TYPE_ONE_TO_ONE);
        $link->addCondition("`{target_table}`.`is_$type` = 1");
        $table->addTableLink('id', $link);
      }
    }
  }

}
