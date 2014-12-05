<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.5                                                |
  +--------------------------------------------------------------------+
  | Copyright (C) 2011 Marty Wright                                    |
  | Licensed to CiviCRM under the Academic Free License version 3.0.   |
  +--------------------------------------------------------------------+
  | This file is a part of CiviCRM.                                    |
  |                                                                    |
  | CiviCRM is free software; you can copy, modify, and distribute it  |
  | under the terms of the GNU Affero General Public License           |
  | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
  |                                                                    |
  | CiviCRM is distributed in the hope that it will be useful, but     |
  | WITHOUT ANY WARRANTY; without even the implied warranty of         |
  | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
  | See the GNU Affero General Public License for more details.        |
  |                                                                    |
  | You should have received a copy of the GNU Affero General Public   |
  | License and the CiviCRM Licensing Exception along                  |
  | with this program; if not, contact CiviCRM LLC                     |
  | at info[AT]civicrm[DOT]org. If you have questions about the        |
  | GNU Affero General Public License or the licensing of CiviCRM,     |
  | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
  +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class contains functions for managing Scheduled Reminders
 */
class CRM_Core_BAO_ActionSchedule extends CRM_Core_DAO_ActionSchedule {

  /**
   * @param null $id
   *
   * @return array
   */
  static function getMapping($id = NULL) {
    static $_action_mapping;

    if ($id && !is_null($_action_mapping) && isset($_action_mapping[$id])) {
      return $_action_mapping[$id];
    }

    $dao = new CRM_Core_DAO_ActionMapping();
    if ($id) {
      $dao->id = $id;
    }
    $dao->find();

    $mapping = array();
    while ($dao->fetch()) {
      $defaults = array();
      CRM_Core_DAO::storeValues($dao, $defaults);
      $mapping[$dao->id] = $defaults;
    }
    $_action_mapping = $mapping;

    return $mapping;
  }

  /**
   * Get all fields of the type Date
   */

  static function getDateFields() {
    $allFields = CRM_Core_BAO_CustomField::getFields('');
    $dateFields = array('birth_date' => ts('Birth Date'));
    foreach ($allFields as $fieldID => $field) {
      if ($field['data_type'] == 'Date') {
        $dateFields["custom_$fieldID"] = $field['label'];
      }
    }
    return $dateFields;
  }

  /**
   * Retrieve list of selections/drop downs for Scheduled Reminder form
   *
   * @param bool    $id    mapping id
   *
   * @return array  associated array of all the drop downs in the form
   * @static
   * @access public
   */
  static function getSelection($id = NULL) {
    $mapping = self::getMapping($id);
    $activityStatus = CRM_Core_PseudoConstant::activityStatus();
    $activityType   = CRM_Core_PseudoConstant::activityType(TRUE, TRUE);

    $participantStatus = CRM_Event_PseudoConstant::participantStatus(NULL, NULL, 'label');
    $event = CRM_Event_PseudoConstant::event(NULL, FALSE, "( is_template IS NULL OR is_template != 1 )");
    $eventType = CRM_Event_PseudoConstant::eventType();
    $eventTemplate = CRM_Event_PseudoConstant::eventTemplates();
    $autoRenew = CRM_Core_OptionGroup::values('auto_renew_options');
    $membershipType = CRM_Member_PseudoConstant::membershipType();
    $dateFieldParams = array('data_type' => 'Date');
    $dateFields = self::getDateFields();
    $contactOptions = CRM_Core_OptionGroup::values('contact_date_reminder_options');

    asort($activityType);

    $sel1 = $sel2 = $sel3 = $sel4 = $sel5 = array();
    $options = array(
      'manual' => ts('Choose Recipient(s)'),
      'group' => ts('Select a Group'),
    );

    $entityMapping = array();
    $recipientMapping = array_combine(array_keys($options), array_keys($options));

    if (!$id) {
      $id = 1;
    }

    foreach ($mapping as $value) {
      $entityValue = CRM_Utils_Array::value('entity_value', $value);
      $entityStatus = CRM_Utils_Array::value('entity_status', $value);
      $entityRecipient = CRM_Utils_Array::value('entity_recipient', $value);
      $valueLabel = array('- ' . strtolower(CRM_Utils_Array::value('entity_value_label', $value)) . ' -');
      $key = CRM_Utils_Array::value('id', $value);
      $entityMapping[$key] = CRM_Utils_Array::value('entity', $value);

      $sel1Val = NULL;
      switch ($entityValue) {
        case 'activity_type':
          if ($value['entity'] == 'civicrm_activity') {
            $sel1Val = ts('Activity');
          }
          $sel2[$key] = $valueLabel + $activityType;
          break;

        case 'event_type':
          if ($value['entity'] == 'civicrm_participant') {
            $sel1Val = ts('Event Type');
          }
          $sel2[$key] = $valueLabel + $eventType;
          break;

        case 'event_template':
          if ($value['entity'] == 'civicrm_participant') {
            $sel1Val = ts('Event Template');
          }
          $sel2[$key] = $valueLabel + $eventTemplate;
          break;

        case 'civicrm_event':
          if ($value['entity'] == 'civicrm_participant') {
            $sel1Val = ts('Event Name');
          }
          $sel2[$key] = $valueLabel + $event;
          break;

        case 'civicrm_membership_type':
          if ($value['entity'] == 'civicrm_membership') {
            $sel1Val = ts('Membership');
          }
          $sel2[$key] = $valueLabel + $membershipType;
          break;

        case 'civicrm_contact':
          if ($value['entity'] == 'civicrm_contact') {
            $sel1Val = ts('Contact');
          }
          $sel2[$key] = $dateFields;
          break;
      }
      $sel1[$key] = $sel1Val;

      if ($key == $id) {
        if ($startDate = CRM_Utils_Array::value('entity_date_start', $value)) {
          $sel4[$startDate] = ucwords(str_replace('_', ' ', $startDate));
        }
        if ($endDate = CRM_Utils_Array::value('entity_date_end', $value)) {
          $sel4[$endDate] = ucwords(str_replace('_', ' ', $endDate));
        }

        switch ($entityRecipient) {
          case 'activity_contacts':
            $activityContacts = CRM_Core_OptionGroup::values('activity_contacts');
            $sel5[$entityRecipient] = $activityContacts + $options;
            $recipientMapping += CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
            break;

          case 'event_contacts':
            $eventContacts = CRM_Core_OptionGroup::values('event_contacts');
            $sel5[$entityRecipient] = $eventContacts + $options;
            $recipientMapping += CRM_Core_OptionGroup::values('event_contacts', FALSE, FALSE, FALSE, NULL, 'name');
            break;

          case NULL:
            $sel5[$entityRecipient] = $options;
            break;
        }
      }
    }
    $sel3 = $sel2;

    foreach ($mapping as $value) {
      $entityStatus = CRM_Utils_Array::value('entity_status', $value);
      $statusLabel = array('- ' . strtolower(CRM_Utils_Array::value('entity_status_label', $value)) . ' -');
      $id = CRM_Utils_Array::value('id', $value);

      switch ($entityStatus) {
        case 'activity_status':
          foreach ($sel3[$id] as $kkey => & $vval) {
            $vval = $statusLabel + $activityStatus;
          }
          break;

        case 'civicrm_participant_status_type':
          foreach ($sel3[$id] as $kkey => & $vval) {
            $vval = $statusLabel + $participantStatus;
          }
          break;

        case 'auto_renew_options':
          foreach ($sel3[$id] as $kkey => & $vval) {
            $auto = 0;
            if ($kkey) {
              $auto = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $kkey, 'auto_renew');
            }
            if ( $auto ) {
              $vval = $statusLabel + $autoRenew;
            }
            else {
              $vval = $statusLabel;
            }
          }
          break;

        case 'contact_date_reminder_options':
          foreach ($sel3[$id] as $kkey => & $vval) {
            $vval = $contactOptions;
          }
          break;

        case '':
          $sel3[$id] = '';
          break;

      }
    }
    return array(
      'sel1' => $sel1,
      'sel2' => $sel2,
      'sel3' => $sel3,
      'sel4' => $sel4,
      'sel5' => $sel5,
      'entityMapping' => $entityMapping,
      'recipientMapping' => $recipientMapping,
    );
  }

  /**
   * @param null $id
   *
   * @return array
   */
  static function getSelection1($id = NULL) {
    $mapping = self::getMapping($id);
    $sel4 = $sel5 = array();
    $options = array(
      'manual' => ts('Choose Recipient(s)'),
      'group' => ts('Select a Group'),
    );

    $recipientMapping = array_combine(array_keys($options), array_keys($options));

    foreach ($mapping as $value) {
      $entityRecipient = CRM_Utils_Array::value('entity_recipient', $value);
      $key = CRM_Utils_Array::value('id', $value);

      if ($startDate = CRM_Utils_Array::value('entity_date_start', $value)) {
        $sel4[$startDate] = ucwords(str_replace('_', ' ', $startDate));
      }
      if ($endDate = CRM_Utils_Array::value('entity_date_end', $value)) {
        $sel4[$endDate] = ucwords(str_replace('_', ' ', $endDate));
      }

      switch ($entityRecipient) {
        case 'activity_contacts':
          $activityContacts = CRM_Core_OptionGroup::values('activity_contacts');
          $sel5[$id] = $activityContacts + $options;
          $recipientMapping += CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
          break;

        case 'event_contacts':
          $eventContacts = CRM_Core_OptionGroup::values('event_contacts');
          $sel5[$id] = $eventContacts + $options;
          $recipientMapping += CRM_Core_OptionGroup::values('event_contacts', FALSE, FALSE, FALSE, NULL, 'name');
          break;

        case NULL:
          $sel5[$id] = $options;
          break;
      }
    }

    return array(
      'sel4' => $sel4,
      'sel5' => $sel5[$id],
      'recipientMapping' => $recipientMapping,
    );
  }

  /**
   * Retrieve list of Scheduled Reminders
   *
   * @param bool $namesOnly return simple list of names
   *
   * @param null $entityValue
   * @param null $id
   *
   * @return array  (reference)   reminder list
   * @static
   * @access public
   */
  static function &getList($namesOnly = FALSE, $entityValue = NULL, $id = NULL) {
    $activity_type = CRM_Core_PseudoConstant::activityType(TRUE, TRUE);
    $activity_status = CRM_Core_PseudoConstant::activityStatus();

    $event_type = CRM_Event_PseudoConstant::eventType();
    $civicrm_event = CRM_Event_PseudoConstant::event(NULL, FALSE, "( is_template IS NULL OR is_template != 1 )");
    $civicrm_participant_status_type = CRM_Event_PseudoConstant::participantStatus(NULL, NULL, 'label');
    $event_template = CRM_Event_PseudoConstant::eventTemplates();
    $civicrm_contact = self::getDateFields();

    $auto_renew_options = CRM_Core_OptionGroup::values('auto_renew_options');
    $contact_date_reminder_options = CRM_Core_OptionGroup::values('contact_date_reminder_options');
    $civicrm_membership_type = CRM_Member_PseudoConstant::membershipType();

    $entity = array(
      'civicrm_activity' => 'Activity',
      'civicrm_participant' => 'Event',
      'civicrm_membership' => 'Member',
      'civicrm_contact' => 'Contact',
    );

    $query = "
SELECT
       title,
       cam.entity,
       cas.id as id,
       cam.entity_value as entityValue,
       cas.entity_value as entityValueIds,
       cam.entity_status as entityStatus,
       cas.entity_status as entityStatusIds,
       cas.start_action_date as entityDate,
       cas.start_action_offset,
       cas.start_action_unit,
       cas.start_action_condition,
       cas.absolute_date,
       is_repeat,
       is_active

FROM civicrm_action_schedule cas
LEFT JOIN civicrm_action_mapping cam ON (cam.id = cas.mapping_id)
";
    $params = CRM_Core_DAO::$_nullArray;

    if ($entityValue and $id) {
      $where = "
WHERE   cas.entity_value = $id AND
        cam.entity_value = '$entityValue'";

      $query .= $where;

      $params = array(
        1 => array($id, 'Integer'),
        2 => array($entityValue, 'String'),
      );
    }

    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $list[$dao->id]['id'] = $dao->id;
      $list[$dao->id]['title'] = $dao->title;
      $list[$dao->id]['start_action_offset'] = $dao->start_action_offset;
      $list[$dao->id]['start_action_unit'] = $dao->start_action_unit;
      $list[$dao->id]['start_action_condition'] = $dao->start_action_condition;
      $list[$dao->id]['entityDate'] = ucwords(str_replace('_', ' ', $dao->entityDate));
      $list[$dao->id]['absolute_date'] = $dao->absolute_date;

      $status = $dao->entityStatus;
      $statusArray = explode(CRM_Core_DAO::VALUE_SEPARATOR, $dao->entityStatusIds);
      foreach ($statusArray as & $s) {
        $s = CRM_Utils_Array::value($s, $$status);
      }
      $statusIds = implode(', ', $statusArray);

      $value = $dao->entityValue;
      $valueArray = explode(CRM_Core_DAO::VALUE_SEPARATOR, $dao->entityValueIds);
      foreach ($valueArray as & $v) {
        $v = CRM_Utils_Array::value($v, $$value);
      }
      $valueIds = implode(', ', $valueArray);
      $list[$dao->id]['entity'] = $entity[$dao->entity];
      $list[$dao->id]['value'] = $valueIds;
      $list[$dao->id]['status'] = $statusIds;
      $list[$dao->id]['is_repeat'] = $dao->is_repeat;
      $list[$dao->id]['is_active'] = $dao->is_active;
    }

    return $list;
  }

  /**
   * @param $contactId
   * @param $to
   * @param $scheduleID
   * @param $from
   * @param $tokenParams
   *
   * @return bool|null
   * @throws CRM_Core_Exception
   */
  static function sendReminder($contactId, $to, $scheduleID, $from, $tokenParams) {
    $email = $to['email'];
    $phoneNumber = $to['phone'];
    $schedule = new CRM_Core_DAO_ActionSchedule();
    $schedule->id = $scheduleID;

    $domain     = CRM_Core_BAO_Domain::getDomain();
    $result     = NULL;
    $hookTokens = array();

    if ($schedule->find(TRUE)) {
      $body_text    = $schedule->body_text;
      $body_html    = $schedule->body_html;
      $sms_body_text = $schedule->sms_body_text;
      $body_subject = $schedule->subject;
      if (!$body_text) {
        $body_text = CRM_Utils_String::htmlToText($body_html);
      }

      $params = array(array('contact_id', '=', $contactId, 0, 0));
      list($contact, $_) = CRM_Contact_BAO_Query::apiQuery($params);

      //CRM-4524
      $contact = reset($contact);

      if (!$contact || is_a($contact, 'CRM_Core_Error')) {
        return NULL;
      }

      // merge activity tokens with contact array
      $contact = array_merge($contact, $tokenParams);

      //CRM-5734
      CRM_Utils_Hook::tokenValues($contact, $contactId);

      CRM_Utils_Hook::tokens($hookTokens);
      $categories = array_keys($hookTokens);

      $type = array('body_html' => 'html', 'body_text' => 'text', 'sms_body_text' => 'text');

      foreach ($type as $bodyType => $value) {
        $dummy_mail = new CRM_Mailing_BAO_Mailing();
        if ($bodyType == 'sms_body_text') {
          $dummy_mail->body_text = $$bodyType;
        }
        else {
          $dummy_mail->$bodyType = $$bodyType;
        }
        $tokens = $dummy_mail->getTokens();

        if ($$bodyType) {
          CRM_Utils_Token::replaceGreetingTokens($$bodyType, NULL, $contact['contact_id']);
          $$bodyType = CRM_Utils_Token::replaceDomainTokens($$bodyType, $domain, TRUE, $tokens[$value], TRUE);
          $$bodyType = CRM_Utils_Token::replaceContactTokens($$bodyType, $contact, FALSE, $tokens[$value], FALSE, TRUE);
          $$bodyType = CRM_Utils_Token::replaceComponentTokens($$bodyType, $contact, $tokens[$value], TRUE, FALSE);
          $$bodyType = CRM_Utils_Token::replaceHookTokens($$bodyType, $contact, $categories, TRUE);
        }
      }
      $html = $body_html;
      $text = $body_text;
      $sms_text = $sms_body_text;

      $smarty = CRM_Core_Smarty::singleton();
      foreach (array(
          'text', 'html', 'sms_text') as $elem) {
        $$elem = $smarty->fetch("string:{$$elem}");
      }

      $matches = array();
      preg_match_all('/(?<!\{|\\\\)\{(\w+\.\w+)\}(?!\})/',
        $body_subject,
        $matches,
        PREG_PATTERN_ORDER
      );

      $subjectToken = NULL;
      if ($matches[1]) {
        foreach ($matches[1] as $token) {
          list($type, $name) = preg_split('/\./', $token, 2);
          if ($name) {
            if (!isset($subjectToken[$type])) {
              $subjectToken[$type] = array();
            }
            $subjectToken[$type][] = $name;
          }
        }
      }

      $messageSubject = CRM_Utils_Token::replaceContactTokens($body_subject, $contact, FALSE, $subjectToken);
      $messageSubject = CRM_Utils_Token::replaceDomainTokens($messageSubject, $domain, TRUE, $subjectToken);
      $messageSubject = CRM_Utils_Token::replaceComponentTokens($messageSubject, $contact, $subjectToken, TRUE);
      $messageSubject = CRM_Utils_Token::replaceHookTokens($messageSubject, $contact, $categories, TRUE);

      $messageSubject = $smarty->fetch("string:{$messageSubject}");

      if ($schedule->mode == 'SMS' or $schedule->mode == 'User_Preference') {
        $session = CRM_Core_Session::singleton();
        $userID = $session->get('userID') ? $session->get('userID') : $contactId;
        $smsParams = array('To' => $phoneNumber, 'provider_id' => $schedule->sms_provider_id, 'activity_subject' => $messageSubject);
        $activityTypeID = CRM_Core_OptionGroup::getValue('activity_type',
          'SMS',
          'name'
        );
        $activityParams = array(
          'source_contact_id' => $userID,
          'activity_type_id' => $activityTypeID,
          'activity_date_time' => date('YmdHis'),
          'subject' => $messageSubject,
          'details' => $sms_text,
          'status_id' => CRM_Core_OptionGroup::getValue('activity_status', 'Completed', 'name'),
        );

        $activity = CRM_Activity_BAO_Activity::create($activityParams);

        CRM_Activity_BAO_Activity::sendSMSMessage($contactId,
          $sms_text,
          $smsParams,
          $activity->id,
          $userID
        );
      }

      if ($schedule->mode == 'Email' or $schedule->mode == 'User_Preference') {
        // set up the parameters for CRM_Utils_Mail::send
        $mailParams = array(
          'groupName' => 'Scheduled Reminder Sender',
          'from' => $from,
          'toName' => $contact['display_name'],
          'toEmail' => $email,
          'subject' => $messageSubject,
          'entity' => 'action_schedule',
          'entity_id' => $scheduleID,
        );

        if (!$html || $contact['preferred_mail_format'] == 'Text' ||
          $contact['preferred_mail_format'] == 'Both'
        ) {
          // render the &amp; entities in text mode, so that the links work
          $mailParams['text'] = str_replace('&amp;', '&', $text);
        }
        if ($html && ($contact['preferred_mail_format'] == 'HTML' ||
            $contact['preferred_mail_format'] == 'Both'
          )
        ) {
          $mailParams['html'] = $html;
        }
        $result = CRM_Utils_Mail::send($mailParams);
      }
    }
    $schedule->free();

    return $result;
  }

  /**
   * Function to add the schedules reminders in the db
   *
   * @param array $params (reference ) an assoc array of name/value pairs
   * @param array $ids    the array that holds all the db ids
   *
   * @return object CRM_Core_DAO_ActionSchedule
   * @access public
   * @static
   *
   */
  static function add(&$params, $ids = array()) {
    $actionSchedule = new CRM_Core_DAO_ActionSchedule();
    $actionSchedule->copyValues($params);

    return $actionSchedule->save();
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $values (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Core_DAO_ActionSchedule object on success, null otherwise
   * @access public
   * @static
   */
  static function retrieve(&$params, &$values) {
    if (empty($params)) {
      return NULL;
    }
    $actionSchedule = new CRM_Core_DAO_ActionSchedule();

    $actionSchedule->copyValues($params);

    if ($actionSchedule->find(TRUE)) {
      $ids['actionSchedule'] = $actionSchedule->id;

      CRM_Core_DAO::storeValues($actionSchedule, $values);

      return $actionSchedule;
    }
    return NULL;
  }

  /**
   * Function to delete a Reminder
   *
   * @param  int  $id     ID of the Reminder to be deleted.
   *
   * @access public
   * @static
   */
  static function del($id) {
    if ($id) {
      $dao = new CRM_Core_DAO_ActionSchedule();
      $dao->id = $id;
      if ($dao->find(TRUE)) {
        $dao->delete();
        return;
      }
    }
    CRM_Core_Error::fatal(ts('Invalid value passed to delete function.'));
  }

  /**
   * update the is_active flag in the db
   *
   * @param int      $id        id of the database record
   * @param boolean  $is_active value we want to set the is_active field
   *
   * @return Object             DAO object on success, null otherwise
   * @static
   */
  static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_ActionSchedule', $id, 'is_active', $is_active);
  }

  /**
   * @param $mappingID
   * @param $now
   *
   * @throws CRM_Core_Exception
   */
  static function sendMailings($mappingID, $now) {
    $domainValues = CRM_Core_BAO_Domain::getNameAndEmail();
    $fromEmailAddress = "$domainValues[0] <$domainValues[1]>";

    $mapping = new CRM_Core_DAO_ActionMapping();
    $mapping->id = $mappingID;
    $mapping->find(TRUE);

    $actionSchedule = new CRM_Core_DAO_ActionSchedule();
    $actionSchedule->mapping_id = $mappingID;
    $actionSchedule->is_active = 1;
    $actionSchedule->find(FALSE);

    $tokenFields = array();
    $session = CRM_Core_Session::singleton();

    while ($actionSchedule->fetch()) {
      $extraSelect = $extraJoin = $extraWhere = $extraOn = '';

    if ($actionSchedule->from_email)
            $fromEmailAddress = "$actionSchedule->from_name <$actionSchedule->from_email>";


      if ($actionSchedule->record_activity) {
        if ($mapping->entity == 'civicrm_membership') {
          $activityTypeID =
            CRM_Core_OptionGroup::getValue('activity_type', 'Membership Renewal Reminder', 'name');
        }
        else {
          $activityTypeID =
            CRM_Core_OptionGroup::getValue('activity_type', 'Reminder Sent', 'name');
        }

        $activityStatusID =
          CRM_Core_OptionGroup::getValue('activity_status', 'Completed', 'name');
      }

      if ($mapping->entity == 'civicrm_activity') {
        $tokenEntity = 'activity';
        $tokenFields = array('activity_id', 'activity_type', 'subject', 'details', 'activity_date_time');
        $extraSelect = ', ov.label as activity_type, e.id as activity_id';
        $extraJoin   = "
INNER JOIN civicrm_option_group og ON og.name = 'activity_type'
INNER JOIN civicrm_option_value ov ON e.activity_type_id = ov.value AND ov.option_group_id = og.id";
        $extraOn = ' AND e.is_current_revision = 1 AND e.is_deleted = 0 ';
        if ($actionSchedule->limit_to == 0) {
          $extraJoin   = "
LEFT JOIN civicrm_option_group og ON og.name = 'activity_type'
LEFT JOIN civicrm_option_value ov ON e.activity_type_id = ov.value AND ov.option_group_id = og.id";
        }
      }

      if ($mapping->entity == 'civicrm_participant') {
        $tokenEntity = 'event';
        $tokenFields = array('event_type', 'title', 'event_id', 'start_date', 'end_date', 'summary', 'description', 'location', 'info_url', 'registration_url', 'fee_amount', 'contact_email', 'contact_phone', 'balance');
        $extraSelect = ', ov.label as event_type, ev.title, ev.id as event_id, ev.start_date, ev.end_date, ev.summary, ev.description, address.street_address, address.city, address.state_province_id, address.postal_code, email.email as contact_email, phone.phone as contact_phone ';

        $extraJoin   = "
INNER JOIN civicrm_event ev ON e.event_id = ev.id
INNER JOIN civicrm_option_group og ON og.name = 'event_type'
INNER JOIN civicrm_option_value ov ON ev.event_type_id = ov.value AND ov.option_group_id = og.id
LEFT  JOIN civicrm_loc_block lb ON lb.id = ev.loc_block_id
LEFT  JOIN civicrm_address address ON address.id = lb.address_id
LEFT  JOIN civicrm_email email ON email.id = lb.email_id
LEFT  JOIN civicrm_phone phone ON phone.id = lb.phone_id
";
        if ($actionSchedule->limit_to == 0) {
          $extraJoin   = "
LEFT JOIN civicrm_event ev ON e.event_id = ev.id
LEFT JOIN civicrm_option_group og ON og.name = 'event_type'
LEFT JOIN civicrm_option_value ov ON ev.event_type_id = ov.value AND ov.option_group_id = og.id
LEFT JOIN civicrm_loc_block lb ON lb.id = ev.loc_block_id
LEFT JOIN civicrm_address address ON address.id = lb.address_id
LEFT JOIN civicrm_email email ON email.id = lb.email_id
LEFT JOIN civicrm_phone phone ON phone.id = lb.phone_id
";
        }
      }

      if ($mapping->entity == 'civicrm_membership') {
        $tokenEntity = 'membership';
        $tokenFields = array('fee', 'id', 'join_date', 'start_date', 'end_date', 'status', 'type');
        $extraSelect = ', mt.minimum_fee as fee, e.id as id , e.join_date, e.start_date, e.end_date, ms.name as status, mt.name as type';
        $extraJoin   = '
 INNER JOIN civicrm_membership_type mt ON e.membership_type_id = mt.id
 INNER JOIN civicrm_membership_status ms ON e.status_id = ms.id';

        if ($actionSchedule->limit_to == 0) {
          $extraJoin   = '
 LEFT JOIN civicrm_membership_type mt ON e.membership_type_id = mt.id
 LEFT JOIN civicrm_membership_status ms ON e.status_id = ms.id';
        }
      }

      if ($mapping->entity == 'civicrm_contact') {
        $tokenEntity = 'contact';
        //TODO: get full list somewhere!
        $tokenFields = array('birth_date', 'last_name');
        //TODO: is there anything to add here?
      }

      $entityJoinClause = "INNER JOIN {$mapping->entity} e ON e.id = reminder.entity_id";
      if ($actionSchedule->limit_to == 0) {
        $entityJoinClause = "LEFT JOIN {$mapping->entity} e ON e.id = reminder.entity_id";
        $extraWhere .= " AND (e.id = reminder.entity_id OR reminder.entity_table = 'civicrm_contact')";
      }
      $entityJoinClause .= $extraOn;

      $query = "
SELECT reminder.id as reminderID, reminder.contact_id as contactID, reminder.entity_table as entityTable, reminder.*, e.id as entityID, e.* {$extraSelect}
FROM  civicrm_action_log reminder
{$entityJoinClause}
{$extraJoin}
WHERE reminder.action_schedule_id = %1 AND reminder.action_date_time IS NULL
{$extraWhere}";

      $dao = CRM_Core_DAO::executeQuery($query,
        array(1 => array($actionSchedule->id, 'Integer'))
      );

      while ($dao->fetch()) {
        $entityTokenParams = array();
        foreach ($tokenFields as $field) {
          if ($field == 'location') {
            $loc = array();
            $stateProvince = CRM_Core_PseudoConstant::stateProvince();
            $loc['street_address'] = $dao->street_address;
            $loc['city'] = $dao->city;
            $loc['state_province'] = CRM_Utils_Array::value($dao->state_province_id, $stateProvince);
            $loc['postal_code'] = $dao->postal_code;
            $entityTokenParams["{$tokenEntity}." . $field] = CRM_Utils_Address::format($loc);
          }
          elseif ($field == 'info_url') {
            $entityTokenParams["{$tokenEntity}." . $field] = CRM_Utils_System::url('civicrm/event/info', 'reset=1&id=' . $dao->event_id, TRUE, NULL, FALSE);
          }
          elseif ($field == 'registration_url') {
            $entityTokenParams["{$tokenEntity}." . $field] = CRM_Utils_System::url('civicrm/event/register', 'reset=1&id=' . $dao->event_id, TRUE, NULL, FALSE);
          }
          elseif (in_array($field, array('start_date','end_date','join_date','activity_date_time'))) {
            $entityTokenParams["{$tokenEntity}." . $field] = CRM_Utils_Date::customFormat($dao->$field);
          }
          elseif ($field == 'balance') {
            if ($dao->entityTable == 'civicrm_contact') {
              $balancePay = 'N/A';
            }
            elseif (!empty($dao->entityID)) {
              $info = CRM_Contribute_BAO_Contribution::getPaymentInfo($dao->entityID, 'event');
              $balancePay = CRM_Utils_Array::value('balance', $info);
              $balancePay = CRM_Utils_Money::format($balancePay);
            }
            $entityTokenParams["{$tokenEntity}." . $field] = $balancePay;
          }
          elseif ($field == 'fee_amount') {
            $entityTokenParams["{$tokenEntity}." . $field] = CRM_Utils_Money::format($dao->$field);
          }
          else {
            $entityTokenParams["{$tokenEntity}." . $field] = $dao->$field;
          }
        }

        $isError  = 0;
        $errorMsg = $toEmail = $toPhoneNumber = '';

        if ($actionSchedule->mode == 'SMS' or $actionSchedule->mode == 'User_Preference') {
          $filters = array('is_deceased' => 0, 'is_deleted' => 0, 'do_not_sms' => 0);
          $toPhoneNumbers = CRM_Core_BAO_Phone::allPhones($dao->contactID, FALSE, 'Mobile', $filters);
          //to get primary mobile ph,if not get a first mobile phONE
          if (!empty($toPhoneNumbers)) {
            $toPhoneNumberDetails = reset($toPhoneNumbers);
            $toPhoneNumber = CRM_Utils_Array::value('phone', $toPhoneNumberDetails);
            //contact allows to send sms
            $toDoNotSms = 0;
          }
        }
        if ($actionSchedule->mode == 'Email' or $actionSchedule->mode == 'User_Preference') {
          $toEmail = CRM_Contact_BAO_Contact::getPrimaryEmail($dao->contactID);
        }
        if ($toEmail || !(empty($toPhoneNumber) or $toDoNotSms)) {
          $to['email'] = $toEmail;
          $to['phone'] = $toPhoneNumber;
          $result =
            CRM_Core_BAO_ActionSchedule::sendReminder(
              $dao->contactID,
              $to,
              $actionSchedule->id,
              $fromEmailAddress,
              $entityTokenParams
            );

          if (!$result || is_a($result, 'PEAR_Error')) {
            // we could not send an email, for now we ignore, CRM-3406
            $isError = 1;
          }
        }
        else {
          $isError = 1;
          $errorMsg = "Couldn\'t find recipient\'s email address.";
        }

        // update action log record
        $logParams = array(
          'id' => $dao->reminderID,
          'is_error' => $isError,
          'message' => $errorMsg ? $errorMsg : "null",
          'action_date_time' => $now,
        );
        CRM_Core_BAO_ActionLog::create($logParams);

        // insert activity log record if needed
        if ($actionSchedule->record_activity && !$isError) {
          $activityParams = array(
            'subject' => $actionSchedule->title,
            'details' => $actionSchedule->body_html,
            'source_contact_id' =>
            $session->get('userID') ? $session->get('userID') : $dao->contactID,
            'target_contact_id' => $dao->contactID,
            'activity_date_time' => date('YmdHis'),
            'status_id' => $activityStatusID,
            'activity_type_id' => $activityTypeID,
            'source_record_id' => $dao->entityID,
          );
          CRM_Activity_BAO_Activity::create($activityParams);
        }
      }

      $dao->free();
    }
  }

  /**
   * @param $mappingID
   * @param $now
   * @param array $params
   *
   * @throws API_Exception
   */
  static function buildRecipientContacts($mappingID, $now, $params = array()) {
    $actionSchedule = new CRM_Core_DAO_ActionSchedule();
    $actionSchedule->mapping_id = $mappingID;
    $actionSchedule->is_active = 1;
    if(!empty($params)) {
      _civicrm_api3_dao_set_filter($actionSchedule, $params, FALSE, 'ActionSchedule');
    }
    $actionSchedule->find();

    while ($actionSchedule->fetch()) {
      $mapping = new CRM_Core_DAO_ActionMapping();
      $mapping->id = $mappingID;
      $mapping->find(TRUE);

      // note: $where - this filtering applies for both
      // 'limit to' and 'addition to' options
      // $limitWhere - this filtering applies only for
      // 'limit to' option
      $select = $join = $where = $limitWhere = array();
      $limitTo = $actionSchedule->limit_to;
      $value = explode(CRM_Core_DAO::VALUE_SEPARATOR,
        trim($actionSchedule->entity_value, CRM_Core_DAO::VALUE_SEPARATOR)
      );
      $value = implode(',', $value);

      $status = explode(CRM_Core_DAO::VALUE_SEPARATOR,
        trim($actionSchedule->entity_status, CRM_Core_DAO::VALUE_SEPARATOR)
      );
      $status = implode(',', $status);

      $anniversary = false;

      if (!CRM_Utils_System::isNull($mapping->entity_recipient)) {
        $recipientOptions = CRM_Core_OptionGroup::values($mapping->entity_recipient, FALSE, FALSE, FALSE, NULL, 'name');
      }
      $from = "{$mapping->entity} e";

      if ($mapping->entity == 'civicrm_activity') {
        $contactField = 'r.contact_id';
        $table = 'civicrm_activity e';
        $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
        $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
        $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
        $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

        if ($limitTo == 0) {
          // including the activity target contacts if 'in addition' is defined
          $join[] = "INNER JOIN civicrm_activity_contact r ON r.activity_id = e.id AND record_type_id = {$targetID}";
        }
        else {
          switch (CRM_Utils_Array::value($actionSchedule->recipient, $recipientOptions)) {
            case 'Activity Assignees':
              $join[] = "INNER JOIN civicrm_activity_contact r ON r.activity_id = e.id AND record_type_id = {$assigneeID}";
              break;

            case 'Activity Source':
              $join[] = "INNER JOIN civicrm_activity_contact r ON r.activity_id = e.id AND record_type_id = {$sourceID}";
              break;

            default:
            case 'Activity Targets':
              $join[] = "INNER JOIN civicrm_activity_contact r ON r.activity_id = e.id AND record_type_id = {$targetID}";
              break;
          }
        }
        // build where clause
        if (!empty($value)) {
          $where[] = "e.activity_type_id IN ({$value})";
        }
        else {
          $where[] = "e.activity_type_id IS NULL";
        }
        if (!empty($status)) {
          $where[] = "e.status_id IN ({$status})";
        }
        $where[] = ' e.is_current_revision = 1 ';
        $where[] = ' e.is_deleted = 0 ';

        $dateField = 'e.activity_date_time';
      }

      if ($mapping->entity == 'civicrm_participant') {
        $table = 'civicrm_event r';
        $contactField = 'e.contact_id';
        $join[] = 'INNER JOIN civicrm_event r ON e.event_id = r.id';
        if ($actionSchedule->recipient_listing && $limitTo) {
          $rList = explode(CRM_Core_DAO::VALUE_SEPARATOR,
            trim($actionSchedule->recipient_listing, CRM_Core_DAO::VALUE_SEPARATOR)
          );
          $rList = implode(',', $rList);

          switch ($recipientOptions[$actionSchedule->recipient]) {
            case 'participant_role':
              $where[] = "e.role_id IN ({$rList})";
              break;

            default:
              break;
          }
        }

        // build where clause
        if (!empty($value)) {
          $where[] = ($mapping->entity_value == 'event_type') ? "r.event_type_id IN ({$value})" : "r.id IN ({$value})";
        }
        else {
          $where[] = ($mapping->entity_value == 'event_type') ? "r.event_type_id IS NULL" : "r.id IS NULL";
        }

        // participant status criteria not to be implemented
        // for additional recipients
        if (!empty($status)) {
          $limitWhere[] = "e.status_id IN ({$status})";
        }

        $where[] = 'r.is_active = 1';
        $where[] = 'r.is_template = 0';
        $dateField = str_replace('event_', 'r.', $actionSchedule->start_action_date);
      }

      $notINClause = '';
      if ($mapping->entity == 'civicrm_membership') {
        $contactField = 'e.contact_id';
        $table = 'civicrm_membership e';
        // build where clause
        if ( $status == 2 ) {
          //auto-renew memberships
          $where[] = "e.contribution_recur_id IS NOT NULL ";
        }
        elseif ( $status == 1 ) {
          $where[] = "e.contribution_recur_id IS NULL ";
        }

        // build where clause
        if (!empty($value)) {
          $where[] = "e.membership_type_id IN ({$value})";
        }
        else {
          $where[] = "e.membership_type_id IS NULL";
        }

        $where[] = "( e.is_override IS NULL OR e.is_override = 0 )";
        $dateField = str_replace('membership_', 'e.', $actionSchedule->start_action_date);
        $notINClause = self::permissionedRelationships($contactField);

        $membershipStatus = CRM_Member_PseudoConstant::membershipStatus(NULL, "(is_current_member = 1 OR name = 'Expired')", 'id');
        $mStatus = implode (',', $membershipStatus);
        $where[] = "e.status_id IN ({$mStatus})";
      }

      if ($mapping->entity == 'civicrm_contact') {
        if ($value == 'birth_date') {
          $dateDBField = 'birth_date';
          $table = 'civicrm_contact e';
          $contactField = 'e.id';
          $where[] = 'e.is_deleted = 0';
          $where[] = 'e.is_deceased = 0';
        }
        else {
          //custom field
          $customFieldParams = array('id' => substr($value, 7));
          $customGroup = $customField = array();
          CRM_Core_BAO_CustomField::retrieve($customFieldParams, $customField);
          $dateDBField = $customField['column_name'];
          $customGroupParams = array('id' => $customField['custom_group_id'], $customGroup);
          CRM_Core_BAO_CustomGroup::retrieve($customGroupParams, $customGroup);
          $from = $table = "{$customGroup['table_name']} e";
          $contactField = 'e.entity_id';
          $where[] = '1'; // possible to have no "where" in this case
        }

        $status_ = explode(',', $status);
        if (in_array(2, $status_)) {
          // anniversary mode:
          $dateField = 'DATE_ADD(e.' . $dateDBField . ', INTERVAL ROUND(DATEDIFF(DATE(' . $now . '), e.' . $dateDBField . ') / 365) YEAR)';
          $anniversary = true;
        }
        else {
          // regular mode:
          $dateField = 'e.' . $dateDBField;
        }
        // TODO get this working

        // TODO: Make sure everything's provided for repetition, etc.
      }

      // CRM-13577 Introduce Smart Groups Handling
      if ($actionSchedule->group_id) {

        // Need to check if its a smart group or not
        // Then decide which table to join onto the query
        $group = CRM_Contact_DAO_Group::getTableName();

        // Get the group information
        $sql = "
SELECT     $group.id, $group.cache_date, $group.saved_search_id, $group.children
FROM       $group
WHERE      $group.id = {$actionSchedule->group_id}
";

        $groupDAO = CRM_Core_DAO::executeQuery($sql);
        $isSmartGroup = FALSE;
        if (
          $groupDAO->fetch() &&
          !empty($groupDAO->saved_search_id)
        ) {
          // Check that the group is in place in the cache and up to date
          CRM_Contact_BAO_GroupContactCache::check($actionSchedule->group_id);
          // Set smart group flag
          $isSmartGroup = TRUE;
        }
      }
      // CRM-13577 End Introduce Smart Groups Handling

      if ($limitTo) {
        if ($actionSchedule->group_id) {
          // CRM-13577 If smart group then use Cache table
          if ($isSmartGroup) {
            $join[] = "INNER JOIN civicrm_group_contact_cache grp ON {$contactField} = grp.contact_id";
            $where[] = "grp.group_id IN ({$actionSchedule->group_id})";
          } else {
            $join[] = "INNER JOIN civicrm_group_contact grp ON {$contactField} = grp.contact_id AND grp.status = 'Added'";
            $where[] = "grp.group_id IN ({$actionSchedule->group_id})";
          }
        }
        elseif (!empty($actionSchedule->recipient_manual)) {
          $rList = CRM_Utils_Type::escape($actionSchedule->recipient_manual, 'String');
          $where[] = "{$contactField} IN ({$rList})";
        }
      }
      else {
        $addGroup = $addWhere = '';
        if ($actionSchedule->group_id) {
          // CRM-13577 If smart group then use Cache table
          if ($isSmartGroup) {
            $addGroup = " INNER JOIN civicrm_group_contact_cache grp ON c.id = grp.contact_id";
            $addWhere = " grp.group_id IN ({$actionSchedule->group_id})";
          } else {
            $addGroup = " INNER JOIN civicrm_group_contact grp ON c.id = grp.contact_id AND grp.status = 'Added'";
            $addWhere = " grp.group_id IN ({$actionSchedule->group_id})";
          }
        }
        if (!empty($actionSchedule->recipient_manual)) {
          $rList = CRM_Utils_Type::escape($actionSchedule->recipient_manual, 'String');
          $addWhere = "c.id IN ({$rList})";
        }
      }

      $select[] = "{$contactField} as contact_id";
      $select[] = 'e.id as entity_id';
      $select[] = "'{$mapping->entity}' as entity_table";
      $select[] = "{$actionSchedule->id} as action_schedule_id";
      $reminderJoinClause = "civicrm_action_log reminder ON reminder.contact_id = {$contactField} AND
reminder.entity_id          = e.id AND
reminder.entity_table       = '{$mapping->entity}' AND
reminder.action_schedule_id = %1";

      if ($anniversary) {
        // only consider reminders less than 11 months ago
        $reminderJoinClause .= " AND reminder.action_date_time > DATE_SUB({$now}, INTERVAL 11 MONTH)";
      }

      if ($table != 'civicrm_contact e') {
        $join[] = "INNER JOIN civicrm_contact c ON c.id = {$contactField} AND c.is_deleted = 0 AND c.is_deceased = 0 ";
      }

      if ($actionSchedule->start_action_date) {
        $startDateClause = array();
        $op = ($actionSchedule->start_action_condition == 'before' ? '<=' : '>=');
        $operator = ($actionSchedule->start_action_condition == 'before' ? 'DATE_SUB' : 'DATE_ADD');
        $date = $operator . "({$dateField}, INTERVAL {$actionSchedule->start_action_offset} {$actionSchedule->start_action_unit})";
        $startDateClause[] = "'{$now}' >= {$date}";
        if ($mapping->entity == 'civicrm_participant') {
          $startDateClause[] = $operator. "({$now}, INTERVAL 1 DAY ) {$op} " . $dateField;
        }
        else {
          $startDateClause[] = "DATE_SUB({$now}, INTERVAL 1 DAY ) <= {$date}";
        }

        $startDate = implode(' AND ', $startDateClause);
      }
      elseif ($actionSchedule->absolute_date) {
        $startDate = "DATEDIFF(DATE('{$now}'),'{$actionSchedule->absolute_date}') = 0";
      }

      // ( now >= date_built_from_start_time ) OR ( now = absolute_date )
      $dateClause = "reminder.id IS NULL AND {$startDate}";

      // start composing query
      $selectClause = 'SELECT ' . implode(', ', $select);
      $fromClause = "FROM $from";
      $joinClause = !empty($join) ? implode(' ', $join) : '';
      $whereClause = 'WHERE ' . implode(' AND ', $where);
      $limitWhereClause = '';
      if (!empty($limitWhere)) {
        $limitWhereClause = ' AND ' . implode(' AND ', $limitWhere);
      }

      $query = "
INSERT INTO civicrm_action_log (contact_id, entity_id, entity_table, action_schedule_id)
{$selectClause}
{$fromClause}
{$joinClause}
LEFT JOIN {$reminderJoinClause}
{$whereClause} {$limitWhereClause} AND {$dateClause} {$notINClause}
";
      CRM_Core_DAO::executeQuery($query, array(1 => array($actionSchedule->id, 'Integer')));

      if ($limitTo == 0 && (!empty($addGroup) || !empty($addWhere))) {
        $additionWhere = ' WHERE ';
        if ($actionSchedule->start_action_date) {
          $additionWhere = $whereClause . ' AND ';
        }
        $contactTable = "civicrm_contact c";
        $addSelect  = "SELECT c.id as contact_id, c.id as entity_id, 'civicrm_contact' as entity_table, {$actionSchedule->id} as action_schedule_id";
        $additionReminderClause = "civicrm_action_log reminder ON reminder.contact_id = c.id AND
          reminder.entity_id          = c.id AND
          reminder.entity_table       = 'civicrm_contact' AND
          reminder.action_schedule_id = {$actionSchedule->id}";
        $addWhereClause = '';
        if ($addWhere) {
          $addWhereClause = "AND {$addWhere}";
        }
        $insertAdditionalSql ="
INSERT INTO civicrm_action_log (contact_id, entity_id, entity_table, action_schedule_id)
{$addSelect}
FROM ({$contactTable})
LEFT JOIN {$additionReminderClause}
{$addGroup}
WHERE c.is_deleted = 0 AND c.is_deceased = 0
{$addWhereClause}

AND c.id NOT IN (
     SELECT rem.contact_id
     FROM civicrm_action_log rem INNER JOIN {$mapping->entity} e ON rem.entity_id = e.id
     WHERE rem.action_schedule_id = {$actionSchedule->id}
      AND rem.entity_table = '{$mapping->entity}'
    )
GROUP BY c.id
";
        CRM_Core_DAO::executeQuery($insertAdditionalSql);
      }
      // if repeat is turned ON:
      if ($actionSchedule->is_repeat) {
        $repeatEvent = ($actionSchedule->end_action == 'before' ? 'DATE_SUB' : 'DATE_ADD') . "({$dateField}, INTERVAL {$actionSchedule->end_frequency_interval} {$actionSchedule->end_frequency_unit})";

        if ($actionSchedule->repetition_frequency_unit == 'day') {
          $hrs = 24 * $actionSchedule->repetition_frequency_interval;
        }
        elseif ($actionSchedule->repetition_frequency_unit == 'week') {
          $hrs = 24 * $actionSchedule->repetition_frequency_interval * 7;
        }
        elseif ($actionSchedule->repetition_frequency_unit == 'month') {
          $hrs = "24*(DATEDIFF(DATE_ADD(latest_log_time, INTERVAL 1 MONTH ), latest_log_time))";
        }
        elseif ($actionSchedule->repetition_frequency_unit == 'year') {
          $hrs = "24*(DATEDIFF(DATE_ADD(latest_log_time, INTERVAL 1 YEAR ), latest_log_time))";
        }
        else {
          $hrs = $actionSchedule->repetition_frequency_interval;
        }

        // (now <= repeat_end_time )
        $repeatEventClause = "'{$now}' <= {$repeatEvent}";
        // diff(now && logged_date_time) >= repeat_interval
        $havingClause = "HAVING TIMEDIFF({$now}, latest_log_time) >= TIME('{$hrs}:00:00')";
        $groupByClause = 'GROUP BY reminder.contact_id, reminder.entity_id, reminder.entity_table';
        $selectClause .= ', MAX(reminder.action_date_time) as latest_log_time';
        //CRM-15376 - do not send our reminders if original criteria no longer applies
        // the first part of the startDateClause array is the earliest the reminder can be sent. If the
        // event (e.g membership_end_date) has changed then the reminder may no longer apply
        // @todo - this only handles events that get moved later. Potentially they might get moved earlier
        $originalEventStartDateClause = empty($startDateClause) ? '' : 'AND' . $startDateClause[0];
        $sqlInsertValues = "{$selectClause}
{$fromClause}
{$joinClause}
INNER JOIN {$reminderJoinClause}
{$whereClause} {$limitWhereClause} AND {$repeatEventClause} {$originalEventStartDateClause} {$notINClause}
{$groupByClause}
{$havingClause}";

        $valsqlInsertValues = CRM_Core_DAO::executeQuery($sqlInsertValues, array(1 => array($actionSchedule->id, 'Integer')));

        $arrValues = array();
        while ($valsqlInsertValues->fetch()) {
          $arrValues[] = "( {$valsqlInsertValues->contact_id}, {$valsqlInsertValues->entity_id}, '{$valsqlInsertValues->entity_table}',{$valsqlInsertValues->action_schedule_id} )";
        }

        $valString = implode(',', $arrValues);

        if ($valString) {
          $query = '
              INSERT INTO civicrm_action_log (contact_id, entity_id, entity_table, action_schedule_id) VALUES ' . $valString;
          CRM_Core_DAO::executeQuery($query, array(1 => array($actionSchedule->id, 'Integer')));
        }

        if ($limitTo == 0) {
          $addSelect .= ', MAX(reminder.action_date_time) as latest_log_time';
          $sqlEndEventCheck = "
SELECT * FROM {$table}
{$whereClause} AND {$repeatEventClause} LIMIT 1";

          $daoCheck = CRM_Core_DAO::executeQuery($sqlEndEventCheck);
          if ($daoCheck->fetch()) {
            $valSqlAdditionInsert = "
{$addSelect}
FROM  {$contactTable}
{$addGroup}
INNER JOIN {$additionReminderClause}
WHERE {$addWhere} AND c.is_deleted = 0 AND c.is_deceased = 0
GROUP BY reminder.contact_id
{$havingClause}
";
            $daoForVals = CRM_Core_DAO::executeQuery($valSqlAdditionInsert);
            $addValues = array();
            while ($daoForVals->fetch()) {
              $addValues[] = "( {$daoForVals->contact_id}, {$daoForVals->entity_id}, '{$daoForVals->entity_table}',{$daoForVals->action_schedule_id} )";
            }
            $valString = implode(',', $addValues);

            if ($valString) {
              $query = '
                INSERT INTO civicrm_action_log (contact_id, entity_id, entity_table, action_schedule_id) VALUES ' . $valString;
              CRM_Core_DAO::executeQuery($query);
            }
          }
        }
      }
    }
  }

  /**
   * @param $field
   *
   * @return null|string
   */
  static function permissionedRelationships($field) {
    $query = '
SELECT    cm.id AS owner_id, cm.contact_id AS owner_contact, m.id AS slave_id, m.contact_id AS slave_contact, cmt.relationship_type_id AS relation_type, rel.contact_id_a, rel.contact_id_b, rel.is_permission_a_b, rel.is_permission_b_a
FROM      civicrm_membership m
LEFT JOIN civicrm_membership cm ON cm.id = m.owner_membership_id
LEFT JOIN civicrm_membership_type cmt ON cmt.id = m.membership_type_id
LEFT JOIN civicrm_relationship rel ON ( ( rel.contact_id_a = m.contact_id AND rel.contact_id_b = cm.contact_id AND rel.relationship_type_id = cmt.relationship_type_id )
                                        OR ( rel.contact_id_a = cm.contact_id AND rel.contact_id_b = m.contact_id AND rel.relationship_type_id = cmt.relationship_type_id ) )
WHERE     m.owner_membership_id IS NOT NULL AND
          ( rel.is_permission_a_b = 0 OR rel.is_permission_b_a = 0)

';
    $excludeIds = array();
    $dao = CRM_Core_DAO::executeQuery($query, array());
    while ($dao->fetch()) {
      if ($dao->slave_contact == $dao->contact_id_a && $dao->is_permission_a_b == 0) {
        $excludeIds[] = $dao->slave_contact;
      }
      elseif ($dao->slave_contact == $dao->contact_id_b && $dao->is_permission_b_a == 0) {
        $excludeIds[] = $dao->slave_contact;
      }
    }

    if (!empty($excludeIds)) {
      $clause = "AND {$field} NOT IN ( " .implode(', ', $excludeIds) . ' ) ';
      return  $clause;
    }
    return NULL;
  }

  /**
   * @param null $now
   * @param array $params
   *
   * @return array
   */
  static function processQueue($now = NULL, $params = array()) {
    $now = $now ? CRM_Utils_Time::setTime($now) : CRM_Utils_Time::getTime();

    $mappings = self::getMapping();
    foreach ($mappings as $mappingID => $mapping) {
      self::buildRecipientContacts($mappingID, $now, $params);
      self::sendMailings($mappingID, $now);
    }

    $result = array(
      'is_error' => 0,
      'messages' => ts('Sent all scheduled reminders successfully'),
    );
    return $result;
  }

  /**
   * @param $id
   * @param $mappingID
   *
   * @return null|string
   */
  static function isConfigured($id, $mappingID) {
    $queryString = "SELECT count(id) FROM civicrm_action_schedule
                        WHERE  mapping_id = %1 AND
                               entity_value = %2";

    $params = array(
      1 => array($mappingID, 'Integer'),
      2 => array($id, 'Integer'),
    );
    return CRM_Core_DAO::singleValueQuery($queryString, $params);
  }

  /**
   * @param $mappingID
   * @param $recipientType
   *
   * @return array
   */
  static function getRecipientListing($mappingID, $recipientType) {
    $options = array();
    if (!$mappingID || !$recipientType) {
      return $options;
    }

    $mapping = self::getMapping($mappingID);

    switch ($mapping['entity']) {
      case 'civicrm_participant':
        $eventContacts = CRM_Core_OptionGroup::values('event_contacts', FALSE, FALSE, FALSE, NULL, 'name');
        if (empty($eventContacts[$recipientType])) {
          return $options;
        }
        if ($eventContacts[$recipientType] == 'participant_role') {
          $options = CRM_Event_PseudoConstant::participantRole();
        }
        break;
    }

    return $options;
  }
}
