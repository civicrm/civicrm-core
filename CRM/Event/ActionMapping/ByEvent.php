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
class CRM_Event_ActionMapping_ByEvent extends CRM_Event_ActionMapping {

  public function getId() {
    return self::EVENT_NAME_MAPPING_ID;
  }

  public function getName(): string {
    return 'event_id';
  }

  public function getLabel(): string {
    return ts('Event');
  }

  public function getValueLabels(): array {
    return CRM_Event_PseudoConstant::event(NULL, FALSE, "is_template = 0");
  }

}
