<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.7                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */

/**
 * This class contains functions for managing Scheduled Reminders
 */
class CRM_Core_BAO_ActionSchedule extends CRM_Core_DAO_ActionSchedule {

  /**
   * @param array $filters
   *   Filter by property (e.g. 'id').
   * @return array
   *   Array(scalar $id => Mapping $mapping).
   */
  public static function getMappings($filters = NULL) {
    static $_action_mapping;

    if ($_action_mapping === NULL) {
      $event = \Civi\Core\Container::singleton()->get('dispatcher')
        ->dispatch(\Civi\ActionSchedule\Events::MAPPINGS,
          new \Civi\ActionSchedule\Event\MappingRegisterEvent());
      $_action_mapping = $event->getMappings();
    }

    if (empty($filters)) {
      return $_action_mapping;
    }
    elseif (isset($filters['id'])) {
      return array(
        $filters['id'] => $_action_mapping[$filters['id']],
      );
    }
    else {
      throw new CRM_Core_Exception("getMappings() called with unsupported filter: " . implode(', ', array_keys($filters)));
    }
  }

  /**
   * Retrieve list of selections/drop downs for Scheduled Reminder form
   *
   * @param bool $id
   *   Mapping id.
   *
   * @return array
   *   associated array of all the drop downs in the form
   */
  public static function getSelection($id = NULL) {
    $mappings = CRM_Core_BAO_ActionSchedule::getMappings();

    $entityValueLabels = $entityStatusLabels = $dateFieldLabels = array();
    $entityRecipientLabels = $entityRecipientNames = array();

    if (!$id) {
      $id = 1;
    }

    foreach ($mappings as $mapping) {
      /** @var \Civi\ActionSchedule\Mapping $mapping */

      $mappingId = $mapping->getId();
      $entityValueLabels[$mappingId] = $mapping->getValueLabels();
      // Not sure why: everything *except* contact-dates have a $valueLabel.
      if ($mapping->getId() !== CRM_Contact_ActionMapping::CONTACT_MAPPING_ID) {
        $valueLabel = array('- ' . strtolower($mapping->getValueHeader()) . ' -');
        $entityValueLabels[$mapping->getId()] = $valueLabel + $entityValueLabels[$mapping->getId()];
      }

      if ($mapping->getId() == $id) {
        $dateFieldLabels = $mapping->getDateFields();
        $entityRecipientLabels = $mapping->getRecipientTypes();
        $entityRecipientNames = array_combine(array_keys($entityRecipientLabels), array_keys($entityRecipientLabels));
      }

      $statusLabel = array('- ' . strtolower($mapping->getStatusHeader()) . ' -');
      $entityStatusLabels[$mapping->getId()] = $entityValueLabels[$mapping->getId()];
      foreach ($entityStatusLabels[$mapping->getId()] as $kkey => & $vval) {
        $vval = $statusLabel + $mapping->getStatusLabels($kkey);
      }
    }

    $entityLabels = array_map(function ($v) {
      return $v->getLabel();
    }, $mappings);
    $entityNames = array_map(function ($v) {
      return $v->getEntity();
    }, $mappings);

    return array(
      'sel1' => $entityLabels,
      'sel2' => $entityValueLabels,
      'sel3' => $entityStatusLabels,
      'sel4' => $dateFieldLabels,
      'sel5' => $entityRecipientLabels,
      'entityMapping' => $entityNames,
      'recipientMapping' => $entityRecipientNames,
    );
  }

  /**
   * @param int $mappingId
   * @param int $isLimit
   *
   * @return array
   */
  public static function getSelection1($mappingId = NULL, $isLimit = NULL) {
    $mappings = CRM_Core_BAO_ActionSchedule::getMappings(array(
      'id' => $mappingId,
    ));
    $dateFieldLabels = $entityRecipientLabels = array();

    foreach ($mappings as $mapping) {
      /** @var \Civi\ActionSchedule\Mapping $mapping */
      $dateFieldLabels = $mapping->getDateFields();
      $entityRecipientLabels = $mapping->getRecipientTypes(!$isLimit);
    }

    return array(
      'sel4' => $dateFieldLabels,
      'sel5' => $entityRecipientLabels,
      'recipientMapping' => array_combine(array_keys($entityRecipientLabels), array_keys($entityRecipientLabels)),
    );
  }

  /**
   * Retrieve list of Scheduled Reminders.
   *
   * @param bool $namesOnly
   *   Return simple list of names.
   *
   * @param \Civi\ActionSchedule\Mapping|NULL $filterMapping
   *   Filter by the schedule's mapping type.
   * @param int $filterValue
   *   Filter by the schedule's entity_value.
   *
   * @return array
   *   (reference)   reminder list
   */
  public static function &getList($namesOnly = FALSE, $filterMapping = NULL, $filterValue = NULL) {
    $query = "
SELECT
       title,
       cas.id as id,
       cas.mapping_id,
       cas.entity_value as entityValueIds,
       cas.entity_status as entityStatusIds,
       cas.start_action_date as entityDate,
       cas.start_action_offset,
       cas.start_action_unit,
       cas.start_action_condition,
       cas.absolute_date,
       is_repeat,
       is_active

FROM civicrm_action_schedule cas
";
    $queryParams = array();
    $where = " WHERE 1 ";
    if ($filterMapping and $filterValue) {
      $where .= " AND cas.entity_value = %1 AND cas.mapping_id = %2";
      $queryParams[1] = array($filterValue, 'Integer');
      $queryParams[2] = array($filterMapping->getId(), 'Integer');
    }
    $where .= " AND cas.used_for IS NULL";
    $query .= $where;
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($dao->fetch()) {
      /** @var Civi\ActionSchedule\Mapping $filterMapping */
      $filterMapping = CRM_Utils_Array::first(self::getMappings(array(
        'id' => $dao->mapping_id,
      )));
      $list[$dao->id]['id'] = $dao->id;
      $list[$dao->id]['title'] = $dao->title;
      $list[$dao->id]['start_action_offset'] = $dao->start_action_offset;
      $list[$dao->id]['start_action_unit'] = $dao->start_action_unit;
      $list[$dao->id]['start_action_condition'] = $dao->start_action_condition;
      $list[$dao->id]['entityDate'] = ucwords(str_replace('_', ' ', $dao->entityDate));
      $list[$dao->id]['absolute_date'] = $dao->absolute_date;
      $list[$dao->id]['entity'] = $filterMapping->getLabel();
      $list[$dao->id]['value'] = implode(', ', CRM_Utils_Array::subset(
        $filterMapping->getValueLabels(),
        explode(CRM_Core_DAO::VALUE_SEPARATOR, $dao->entityValueIds)
      ));
      $list[$dao->id]['status'] = implode(', ', CRM_Utils_Array::subset(
        $filterMapping->getStatusLabels($dao->entityValueIds),
        explode(CRM_Core_DAO::VALUE_SEPARATOR, $dao->entityStatusIds)
      ));
      $list[$dao->id]['is_repeat'] = $dao->is_repeat;
      $list[$dao->id]['is_active'] = $dao->is_active;
    }

    return $list;
  }

  /**
   * Add the schedules reminders in the db.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $ids
   *   Unused variable.
   *
   * @return CRM_Core_DAO_ActionSchedule
   */
  public static function add(&$params, $ids = array()) {
    $actionSchedule = new CRM_Core_DAO_ActionSchedule();
    $actionSchedule->copyValues($params);

    return $actionSchedule->save();
  }

  /**
   * Retrieve DB object based on input parameters.
   *
   * It also stores all the retrieved values in the default array.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $values
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Core_DAO_ActionSchedule|null
   *   object on success, null otherwise
   */
  public static function retrieve(&$params, &$values) {
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
   * Delete a Reminder.
   *
   * @param int $id
   *   ID of the Reminder to be deleted.
   *
   */
  public static function del($id) {
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
   * Update the is_active flag in the db.
   *
   * @param int $id
   *   Id of the database record.
   * @param bool $is_active
   *   Value we want to set the is_active field.
   *
   * @return Object
   *   DAO object on success, null otherwise
   */
  public static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_ActionSchedule', $id, 'is_active', $is_active);
  }

  /**
   * @param int $mappingID
   * @param $now
   *
   * @throws CRM_Core_Exception
   */
  public static function sendMailings($mappingID, $now) {
    $mapping = CRM_Utils_Array::first(self::getMappings(array(
      'id' => $mappingID,
    )));

    $actionSchedule = new CRM_Core_DAO_ActionSchedule();
    $actionSchedule->mapping_id = $mappingID;
    $actionSchedule->is_active = 1;
    $actionSchedule->find(FALSE);

    while ($actionSchedule->fetch()) {
      $query = CRM_Core_BAO_ActionSchedule::prepareMailingQuery($mapping, $actionSchedule);
      $dao = CRM_Core_DAO::executeQuery($query,
        array(1 => array($actionSchedule->id, 'Integer'))
      );

      $multilingual = CRM_Core_I18n::isMultilingual();
      while ($dao->fetch()) {
        // switch language if necessary
        if ($multilingual) {
          $preferred_language = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $dao->contactID, 'preferred_language');
          CRM_Core_BAO_ActionSchedule::setCommunicationLanguage($actionSchedule->communication_language, $preferred_language);
        }

        $errors = array();
        try {
          $tokenProcessor = self::createTokenProcessor($actionSchedule, $mapping);
          $tokenProcessor->addRow()
            ->context('contactId', $dao->contactID)
            ->context('actionSearchResult', (object) $dao->toArray());
          foreach ($tokenProcessor->evaluate()->getRows() as $tokenRow) {
            if ($actionSchedule->mode == 'SMS' or $actionSchedule->mode == 'User_Preference') {
              CRM_Utils_Array::extend($errors, self::sendReminderSms($tokenRow, $actionSchedule, $dao->contactID));
            }

            if ($actionSchedule->mode == 'Email' or $actionSchedule->mode == 'User_Preference') {
              CRM_Utils_Array::extend($errors, self::sendReminderEmail($tokenRow, $actionSchedule, $dao->contactID));
            }
          }
        }
        catch (\Civi\Token\TokenException $e) {
          $errors['token_exception'] = $e->getMessage();
        }

        // update action log record
        $logParams = array(
          'id' => $dao->reminderID,
          'is_error' => !empty($errors),
          'message' => empty($errors) ? "null" : implode(' ', $errors),
          'action_date_time' => $now,
        );
        CRM_Core_BAO_ActionLog::create($logParams);

        // insert activity log record if needed
        if ($actionSchedule->record_activity && empty($errors)) {
          $caseID = empty($dao->case_id) ? NULL : $dao->case_id;
          CRM_Core_BAO_ActionSchedule::createMailingActivity($actionSchedule, $mapping, $dao->contactID, $dao->entityID, $caseID);
        }
      }

      $dao->free();
    }
  }

  /**
   * @param int $mappingID
   * @param $now
   * @param array $params
   *
   * @throws API_Exception
   */
  public static function buildRecipientContacts($mappingID, $now, $params = array()) {
    $actionSchedule = new CRM_Core_DAO_ActionSchedule();
    $actionSchedule->mapping_id = $mappingID;
    $actionSchedule->is_active = 1;
    if (!empty($params)) {
      _civicrm_api3_dao_set_filter($actionSchedule, $params, FALSE);
    }
    $actionSchedule->find();

    while ($actionSchedule->fetch()) {
      /** @var \Civi\ActionSchedule\Mapping $mapping */
      $mapping = CRM_Utils_Array::first(self::getMappings(array(
        'id' => $mappingID,
      )));
      $builder = new \Civi\ActionSchedule\RecipientBuilder($now, $actionSchedule, $mapping);
      $builder->build();
    }
  }

  /**
   * @param null $now
   * @param array $params
   *
   * @return array
   */
  public static function processQueue($now = NULL, $params = array()) {
    $now = $now ? CRM_Utils_Time::setTime($now) : CRM_Utils_Time::getTime();

    $mappings = CRM_Core_BAO_ActionSchedule::getMappings();
    foreach ($mappings as $mappingID => $mapping) {
      CRM_Core_BAO_ActionSchedule::buildRecipientContacts($mappingID, $now, $params);
      CRM_Core_BAO_ActionSchedule::sendMailings($mappingID, $now);
    }

    $result = array(
      'is_error' => 0,
      'messages' => ts('Sent all scheduled reminders successfully'),
    );
    return $result;
  }

  /**
   * @param int $id
   * @param int $mappingID
   *
   * @return null|string
   */
  public static function isConfigured($id, $mappingID) {
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
   * @param int $mappingID
   * @param $recipientType
   *
   * @return array
   */
  public static function getRecipientListing($mappingID, $recipientType) {
    if (!$mappingID) {
      return array();
    }

    /** @var \Civi\ActionSchedule\Mapping $mapping */
    $mapping = CRM_Utils_Array::first(CRM_Core_BAO_ActionSchedule::getMappings(array(
      'id' => $mappingID,
    )));
    return $mapping->getRecipientListing($recipientType);
  }

  /**
   * @param $communication_language
   * @param $preferred_language
   */
  public static function setCommunicationLanguage($communication_language, $preferred_language) {
    $config = CRM_Core_Config::singleton();
    $language = $config->lcMessages;

    // prepare the language for the email
    if ($communication_language == CRM_Core_I18n::AUTO) {
      if (!empty($preferred_language)) {
        $language = $preferred_language;
      }
    }
    else {
      $language = $communication_language;
    }

    // language not in the existing language, use default
    $languages = CRM_Core_I18n::languages(TRUE);
    if (!in_array($language, $languages)) {
      $language = $config->lcMessages;
    }

    // change the language
    $i18n = CRM_Core_I18n::singleton();
    $i18n->setLanguage($language);
  }

  /**
   * Save a record about the delivery of a reminder email.
   *
   * WISHLIST: Instead of saving $actionSchedule->body_html, call this immediately after
   * sending the message and pass in the fully rendered text of the message.
   *
   * @param CRM_Core_DAO_ActionSchedule $actionSchedule
   * @param Civi\ActionSchedule\Mapping $mapping
   * @param int $contactID
   * @param int $entityID
   * @param int|NULL $caseID
   * @throws CRM_Core_Exception
   */
  protected static function createMailingActivity($actionSchedule, $mapping, $contactID, $entityID, $caseID) {
    $session = CRM_Core_Session::singleton();

    if ($mapping->getEntity() == 'civicrm_membership') {
      $activityTypeID
        = CRM_Core_OptionGroup::getValue('activity_type', 'Membership Renewal Reminder', 'name');
    }
    else {
      $activityTypeID
        = CRM_Core_OptionGroup::getValue('activity_type', 'Reminder Sent', 'name');
    }

    $activityParams = array(
      'subject' => $actionSchedule->title,
      'details' => $actionSchedule->body_html,
      'source_contact_id' => $session->get('userID') ? $session->get('userID') : $contactID,
      'target_contact_id' => $contactID,
      'activity_date_time' => CRM_Utils_Time::getTime('YmdHis'),
      'status_id' => CRM_Core_OptionGroup::getValue('activity_status', 'Completed', 'name'),
      'activity_type_id' => $activityTypeID,
      'source_record_id' => $entityID,
    );
    $activity = CRM_Activity_BAO_Activity::create($activityParams);

    //file reminder on case if source activity is a case activity
    if (!empty($caseID)) {
      $caseActivityParams = array();
      $caseActivityParams['case_id'] = $caseID;
      $caseActivityParams['activity_id'] = $activity->id;
      CRM_Case_BAO_Case::processCaseActivity($caseActivityParams);
    }
  }

  /**
   * @param $mapping
   * @param $actionSchedule
   * @return string
   */
  protected static function prepareMailingQuery($mapping, $actionSchedule) {
    $select = CRM_Utils_SQL_Select::from('civicrm_action_log reminder', array('mode' => 'out'))
      ->select("reminder.id as reminderID, reminder.contact_id as contactID, reminder.entity_table as entityTable, reminder.*, e.id as entityID, e.*")
      ->where("reminder.action_schedule_id = #casScheduleId")
      ->param('casScheduleId', $actionSchedule->id)
      ->where("reminder.action_date_time IS NULL");

    if ($actionSchedule->limit_to == 0) {
      $entityJoinClause = "LEFT JOIN {$mapping->getEntity()} e ON e.id = reminder.entity_id";
      $select->where("e.id = reminder.entity_id OR reminder.entity_table = 'civicrm_contact'");
    }
    else {
      $entityJoinClause = "INNER JOIN {$mapping->getEntity()} e ON e.id = reminder.entity_id";
    }
    if ($mapping->getEntity() == 'civicrm_activity') {
      $entityJoinClause .= ' AND e.is_current_revision = 1 AND e.is_deleted = 0 ';
    }
    $select->join('a', $entityJoinClause);

    if ($mapping->getEntity() == 'civicrm_activity') {
      $compInfo = CRM_Core_Component::getEnabledComponents();
      $select->select('ov.label as activity_type, e.id as activity_id');

      $JOIN_TYPE = ($actionSchedule->limit_to == 0) ? 'LEFT JOIN' : 'INNER JOIN';
      $select->join("og", "$JOIN_TYPE civicrm_option_group og ON og.name = 'activity_type'");
      $select->join("ov", "$JOIN_TYPE civicrm_option_value ov ON e.activity_type_id = ov.value AND ov.option_group_id = og.id");

      // if CiviCase component is enabled, join for caseId.
      if (array_key_exists('CiviCase', $compInfo)) {
        $select->select("civicrm_case_activity.case_id as case_id");
        $select->join('civicrm_case_activity', "LEFT JOIN `civicrm_case_activity` ON `e`.`id` = `civicrm_case_activity`.`activity_id`");
      }
    }

    if ($mapping->getEntity() == 'civicrm_participant') {
      $select->select('ov.label as event_type, ev.title, ev.id as event_id, ev.start_date, ev.end_date, ev.summary, ev.description, address.street_address, address.city, address.state_province_id, address.postal_code, email.email as contact_email, phone.phone as contact_phone');

      $JOIN_TYPE = ($actionSchedule->limit_to == 0) ? 'LEFT JOIN' : 'INNER JOIN';
      $select->join('participant_stuff', "
$JOIN_TYPE civicrm_event ev ON e.event_id = ev.id
$JOIN_TYPE civicrm_option_group og ON og.name = 'event_type'
$JOIN_TYPE civicrm_option_value ov ON ev.event_type_id = ov.value AND ov.option_group_id = og.id
LEFT JOIN civicrm_loc_block lb ON lb.id = ev.loc_block_id
LEFT JOIN civicrm_address address ON address.id = lb.address_id
LEFT JOIN civicrm_email email ON email.id = lb.email_id
LEFT JOIN civicrm_phone phone ON phone.id = lb.phone_id
");
    }

    if ($mapping->getEntity() == 'civicrm_membership') {
      $select->select('mt.minimum_fee as fee, e.id as id , e.join_date, e.start_date, e.end_date, ms.name as status, mt.name as type');

      $JOIN_TYPE = ($actionSchedule->limit_to == 0) ? 'LEFT JOIN' : 'INNER JOIN';
      $select->join('mt', "$JOIN_TYPE civicrm_membership_type mt ON e.membership_type_id = mt.id");
      $select->join('ms', "$JOIN_TYPE civicrm_membership_status ms ON e.status_id = ms.id");
    }

    return $select->toSQL();
  }

  /**
   * @param TokenRow $tokenRow
   * @param CRM_Core_DAO_ActionSchedule $schedule
   * @param int $toContactID
   * @throws CRM_Core_Exception
   * @return array
   *   List of error messages.
   */
  protected static function sendReminderSms($tokenRow, $schedule, $toContactID) {
    $toPhoneNumber = self::pickSmsPhoneNumber($toContactID);
    if (!$toPhoneNumber) {
      return array("sms_phone_missing" => "Couldn't find recipient's phone number.");
    }

    $messageSubject = $tokenRow->render('subject');
    $sms_body_text = $tokenRow->render('sms_body_text');

    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID') ? $session->get('userID') : $tokenRow->context['contactId'];
    $smsParams = array(
      'To' => $toPhoneNumber,
      'provider_id' => $schedule->sms_provider_id,
      'activity_subject' => $messageSubject,
    );
    $activityTypeID = CRM_Core_OptionGroup::getValue('activity_type',
      'SMS',
      'name'
    );
    $activityParams = array(
      'source_contact_id' => $userID,
      'activity_type_id' => $activityTypeID,
      'activity_date_time' => date('YmdHis'),
      'subject' => $messageSubject,
      'details' => $sms_body_text,
      'status_id' => CRM_Core_OptionGroup::getValue('activity_status', 'Completed', 'name'),
    );

    $activity = CRM_Activity_BAO_Activity::create($activityParams);

    CRM_Activity_BAO_Activity::sendSMSMessage($tokenRow->context['contactId'],
      $sms_body_text,
      $smsParams,
      $activity->id,
      $userID
    );

    return array();
  }

  /**
   * @param CRM_Core_DAO_ActionSchedule $actionSchedule
   * @return string
   *   Ex: "Alice <alice@example.org>".
   */
  protected static function pickFromEmail($actionSchedule) {
    $domainValues = CRM_Core_BAO_Domain::getNameAndEmail();
    $fromEmailAddress = "$domainValues[0] <$domainValues[1]>";
    if ($actionSchedule->from_email) {
      $fromEmailAddress = "$actionSchedule->from_name <$actionSchedule->from_email>";
      return $fromEmailAddress;
    }
    return $fromEmailAddress;
  }

  /**
   * @param TokenRow $tokenRow
   * @param CRM_Core_DAO_ActionSchedule $schedule
   * @param int $toContactID
   * @return array
   *   List of error messages.
   */
  protected static function sendReminderEmail($tokenRow, $schedule, $toContactID) {
    $toEmail = CRM_Contact_BAO_Contact::getPrimaryEmail($toContactID);
    if (!$toEmail) {
      return array("email_missing" => "Couldn't find recipient's email address.");
    }

    $body_text = $tokenRow->render('body_text');
    $body_html = $tokenRow->render('body_html');
    if (!$schedule->body_text) {
      $body_text = CRM_Utils_String::htmlToText($body_html);
    }

    // set up the parameters for CRM_Utils_Mail::send
    $mailParams = array(
      'groupName' => 'Scheduled Reminder Sender',
      'from' => self::pickFromEmail($schedule),
      'toName' => $tokenRow->context['contact']['display_name'],
      'toEmail' => $toEmail,
      'subject' => $tokenRow->render('subject'),
      'entity' => 'action_schedule',
      'entity_id' => $schedule->id,
    );

    if (!$body_html || $tokenRow->context['contact']['preferred_mail_format'] == 'Text' ||
      $tokenRow->context['contact']['preferred_mail_format'] == 'Both'
    ) {
      // render the &amp; entities in text mode, so that the links work
      $mailParams['text'] = str_replace('&amp;', '&', $body_text);
    }
    if ($body_html && ($tokenRow->context['contact']['preferred_mail_format'] == 'HTML' ||
        $tokenRow->context['contact']['preferred_mail_format'] == 'Both'
      )
    ) {
      $mailParams['html'] = $body_html;
    }
    $result = CRM_Utils_Mail::send($mailParams);
    if (!$result || is_a($result, 'PEAR_Error')) {
      return array('email_fail' => 'Failed to send message');
    }

    return array();
  }

  /**
   * @param CRM_Core_DAO_ActionSchedule $schedule
   * @param \Civi\ActionSchedule\Mapping $mapping
   * @return \Civi\Token\TokenProcessor
   */
  protected static function createTokenProcessor($schedule, $mapping) {
    $tp = new \Civi\Token\TokenProcessor(\Civi\Core\Container::singleton()->get('dispatcher'), array(
      'controller' => __CLASS__,
      'actionSchedule' => $schedule,
      'actionMapping' => $mapping,
      'smarty' => TRUE,
    ));
    $tp->addMessage('body_text', $schedule->body_text, 'text/plain');
    $tp->addMessage('body_html', $schedule->body_html, 'text/html');
    $tp->addMessage('sms_body_text', $schedule->sms_body_text, 'text/plain');
    $tp->addMessage('subject', $schedule->subject, 'text/plain');
    return $tp;
  }

  /**
   * @param $dao
   * @return string|NULL
   */
  protected static function pickSmsPhoneNumber($smsToContactId) {
    $toPhoneNumbers = CRM_Core_BAO_Phone::allPhones($smsToContactId, FALSE, 'Mobile', array(
      'is_deceased' => 0,
      'is_deleted' => 0,
      'do_not_sms' => 0,
    ));
    //to get primary mobile ph,if not get a first mobile phONE
    if (!empty($toPhoneNumbers)) {
      $toPhoneNumberDetails = reset($toPhoneNumbers);
      $toPhoneNumber = CRM_Utils_Array::value('phone', $toPhoneNumberDetails);
      return $toPhoneNumber;
    }
    return NULL;
  }

}
