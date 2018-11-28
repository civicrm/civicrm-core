<?php

namespace Civi\Api4\Event\Subscriber;

use Civi\Api4\Event\Events;
use Civi\Api4\Event\SchemaMapBuildEvent;
use Civi\Api4\Service\Schema\Joinable\Joinable;
use Civi\Api4\Service\Schema\Table;
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
   * @param SchemaMapBuildEvent $event
   */
  public function onSchemaBuild(SchemaMapBuildEvent $event) {
    $schema = $event->getSchemaMap();
    $table = $schema->getTableByName('civicrm_contact');
    $this->addCreatedActivitiesLink($table);
    $this->fixPreferredLanguageAlias($table);
  }

  /**
   * @param Table $table
   */
  private function addCreatedActivitiesLink($table) {
    $alias = 'created_activities';
    $joinable = new Joinable('civicrm_activity_contact', 'contact_id', $alias);
    $joinable->addCondition($alias . '.record_type_id = 1');
    $joinable->setJoinType($joinable::JOIN_TYPE_ONE_TO_MANY);
    $table->addTableLink('id', $joinable);
  }

  /**
   * @param Table $table
   */
  private function fixPreferredLanguageAlias($table) {
    foreach ($table->getExternalLinks() as $link) {
      if ($link->getAlias() === 'languages') {
        $link->setAlias('preferred_language');
        return;
      }
    }
  }

}
