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
class CRM_Event_ActionMapping_ByType extends CRM_Event_ActionMapping {

  public function getId() {
    return self::EVENT_TYPE_MAPPING_ID;
  }

  public function getName(): string {
    return 'event_type';
  }

  public function getLabel(): string {
    return ts('Event Type');
  }

  public function getValueLabels(): array {
    return CRM_Event_PseudoConstant::eventType();
  }

  public function checkAccess(array $entityValue): bool {
    return FALSE;
  }

}
