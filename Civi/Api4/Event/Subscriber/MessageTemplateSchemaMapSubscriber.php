<?php

namespace Civi\Api4\Event\Subscriber;

use Civi\Api4\Event\SchemaMapBuildEvent;
use Civi\Api4\Service\Schema\Joinable\Joinable;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service civi.api4.messagetemplateSchema
 */
class MessageTemplateSchemaMapSubscriber extends \Civi\Core\Service\AutoService implements EventSubscriberInterface {

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
   * @see \Civi\Api4\Service\Spec\Provider\MessageTemplateGetSpecProvider
   *
   * @param \Civi\Api4\Event\SchemaMapBuildEvent $event
   *
   * Condition based on CRM_Admin_Page_MessageTemplates::__construct()
   */
  public function onSchemaBuild(SchemaMapBuildEvent $event) {
    $schema = $event->getSchemaMap();
    $table = $schema->getTableByName('civicrm_msg_template');

    $link = new Joinable("civicrm_msg_template", 'id', "master_id");
    $link->setBaseTable('civicrm_msg_template');
    $link->setJoinType(Joinable::JOIN_TYPE_ONE_TO_ONE);
    $link->addCondition("`{target_table}`.`id` =
      (SELECT `id` FROM `civicrm_msg_template` `orig`
        WHERE `{base_table}`.`workflow_name` = `orig`.`workflow_name` AND `orig`.`is_reserved` = 1 AND
          ( `{base_table}`.`msg_subject` != `orig`.`msg_subject` OR
            `{base_table}`.`msg_text`    != `orig`.`msg_text`    OR
            `{base_table}`.`msg_html`    != `orig`.`msg_html`
          ))"
    );
    $table->addTableLink(NULL, $link);
  }

}
