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
 * This defines the scheduled-reminder functionality for CiviEvent
 * participants with filtering by event-type.
 */
class CRM_Event_ActionMapping_ByTemplate extends CRM_Event_ActionMapping {

  public function getId() {
    return self::EVENT_TPL_MAPPING_ID;
  }

  public function getName(): string {
    return 'event_template';
  }

  public function getLabel(): string {
    return ts('Event Template');
  }

  public function getValueLabels(): array {
    return \Civi\Api4\Event::get(FALSE)
      ->addWhere('is_template', '=', TRUE)
      ->addWhere('is_active', '=', TRUE)
      ->execute()
      ->column('template_title', 'id');
  }

}
