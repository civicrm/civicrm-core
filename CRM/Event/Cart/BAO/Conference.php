<?php

/**
 * Class CRM_Event_Cart_BAO_Conference
 */
class CRM_Event_Cart_BAO_Conference {
  /**
   * XXX assumes we don't allow a contact to register for the same conference more than once
   * XXX flattens the object tree for convenient templating
   * @param int $main_event_participant_id
   *
   * @return array|null
   */
  public static function get_participant_sessions($main_event_participant_id) {
    $sql = <<<EOS
SELECT sub_event.* FROM civicrm_participant main_participant
    JOIN civicrm_event sub_event ON sub_event.parent_event_id = main_participant.event_id
    JOIN civicrm_participant sub_participant ON sub_participant.event_id = sub_event.id
    LEFT JOIN
        civicrm_option_value slot ON sub_event.slot_label_id = slot.value
    LEFT JOIN
        civicrm_option_group og ON slot.option_group_id = og.id
  WHERE
      main_participant.id = %1
      AND sub_participant.contact_id = main_participant.contact_id
      AND og.name = 'conference_slot'
  ORDER BY
      slot.weight,
      sub_event.start_date
EOS;
    $sql_args = array(1 => array($main_event_participant_id, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($sql, $sql_args);
    $smarty_sessions = array();
    while ($dao->fetch()) {
      $smarty_sessions[] = get_object_vars($dao);
    }
    if (empty($smarty_sessions)) {
      return NULL;
    }
    return $smarty_sessions;
  }

}
