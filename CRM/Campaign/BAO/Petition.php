<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */
class CRM_Campaign_BAO_Petition extends CRM_Campaign_BAO_Survey {
  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
    // expire cookie in one day
    $this->cookieExpire = (1 * 60 * 60 * 24);
  }

  /**
   * Get Petition Details for dashboard.
   *
   * @param array $params
   * @param bool $onlyCount
   *
   * @return array|int
   */
  public static function getPetitionSummary($params = array(), $onlyCount = FALSE) {
    //build the limit and order clause.
    $limitClause = $orderByClause = $lookupTableJoins = NULL;
    if (!$onlyCount) {
      $sortParams = array(
        'sort' => 'created_date',
        'offset' => 0,
        'rowCount' => 10,
        'sortOrder' => 'desc',
      );
      foreach ($sortParams as $name => $default) {
        if (!empty($params[$name])) {
          $sortParams[$name] = $params[$name];
        }
      }

      //need to lookup tables.
      $orderOnPetitionTable = TRUE;
      if ($sortParams['sort'] == 'campaign') {
        $orderOnPetitionTable = FALSE;
        $lookupTableJoins = '
 LEFT JOIN civicrm_campaign campaign ON ( campaign.id = petition.campaign_id )';
        $orderByClause = "ORDER BY campaign.title {$sortParams['sortOrder']}";
      }
      elseif ($sortParams['sort'] == 'activity_type') {
        $orderOnPetitionTable = FALSE;
        $lookupTableJoins = "
 LEFT JOIN civicrm_option_value activity_type ON ( activity_type.value = petition.activity_type_id
                                                   OR petition.activity_type_id IS NULL )
INNER JOIN civicrm_option_group grp ON ( activity_type.option_group_id = grp.id AND grp.name = 'activity_type' )";
        $orderByClause = "ORDER BY activity_type.label {$sortParams['sortOrder']}";
      }
      elseif ($sortParams['sort'] == 'isActive') {
        $sortParams['sort'] = 'is_active';
      }
      if ($orderOnPetitionTable) {
        $orderByClause = "ORDER BY petition.{$sortParams['sort']} {$sortParams['sortOrder']}";
      }
      $limitClause = "LIMIT {$sortParams['offset']}, {$sortParams['rowCount']}";
    }

    //build the where clause.
    $queryParams = $where = array();

    //we only have activity type as a
    //difference between survey and petition.
    $petitionTypeID = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Petition');
    if ($petitionTypeID) {
      $where[] = "( petition.activity_type_id = %1 )";
      $queryParams[1] = array($petitionTypeID, 'Positive');
    }
    if (!empty($params['title'])) {
      $where[] = "( petition.title LIKE %2 )";
      $queryParams[2] = array('%' . trim($params['title']) . '%', 'String');
    }
    if (!empty($params['campaign_id'])) {
      $where[] = '( petition.campaign_id = %3 )';
      $queryParams[3] = array($params['campaign_id'], 'Positive');
    }
    $whereClause = NULL;
    if (!empty($where)) {
      $whereClause = ' WHERE ' . implode(" \nAND ", $where);
    }

    $selectClause = '
SELECT  petition.id                         as id,
        petition.title                      as title,
        petition.is_active                  as is_active,
        petition.result_id                  as result_id,
        petition.is_default                 as is_default,
        petition.campaign_id                as campaign_id,
        petition.activity_type_id           as activity_type_id';

    if ($onlyCount) {
      $selectClause = 'SELECT COUNT(*)';
    }
    $fromClause = 'FROM  civicrm_survey petition';

    $query = "{$selectClause} {$fromClause} {$whereClause} {$orderByClause} {$limitClause}";

    if ($onlyCount) {
      return (int) CRM_Core_DAO::singleValueQuery($query, $queryParams);
    }

    $petitions = array();
    $properties = array(
      'id',
      'title',
      'campaign_id',
      'is_active',
      'is_default',
      'result_id',
      'activity_type_id',
    );

    $petition = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($petition->fetch()) {
      foreach ($properties as $property) {
        $petitions[$petition->id][$property] = $petition->$property;
      }
    }

    return $petitions;
  }

  /**
   * Get the petition count.
   *
   */
  public static function getPetitionCount() {
    $whereClause = 'WHERE ( 1 )';
    $queryParams = array();
    $petitionTypeID = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Petition');
    if ($petitionTypeID) {
      $whereClause = "WHERE ( petition.activity_type_id = %1 )";
      $queryParams[1] = array($petitionTypeID, 'Positive');
    }
    $query = "SELECT COUNT(*) FROM civicrm_survey petition {$whereClause}";

    return (int) CRM_Core_DAO::singleValueQuery($query, $queryParams);
  }

  /**
   * Takes an associative array and creates a petition signature activity.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return CRM_Campaign_BAO_Petition
   */
  public function createSignature(&$params) {
    if (empty($params)) {
      return NULL;
    }

    if (!isset($params['sid'])) {
      $statusMsg = ts('No survey sid parameter. Cannot process signature.');
      CRM_Core_Session::setStatus($statusMsg, ts('Sorry'), 'error');
      return;
    }

    if (isset($params['contactId'])) {

      // add signature as activity with survey id as source id
      // get the activity type id associated with this survey
      $surveyInfo = CRM_Campaign_BAO_Petition::getSurveyInfo($params['sid']);

      // create activity
      // 1-Schedule, 2-Completed

      $activityParams = array(
        'source_contact_id' => $params['contactId'],
        'target_contact_id' => $params['contactId'],
        'source_record_id' => $params['sid'],
        'subject' => $surveyInfo['title'],
        'activity_type_id' => $surveyInfo['activity_type_id'],
        'activity_date_time' => date("YmdHis"),
        'status_id' => $params['statusId'],
        'activity_campaign_id' => $params['activity_campaign_id'],
      );

      //activity creation
      // *** check for activity using source id - if already signed
      $activity = CRM_Activity_BAO_Activity::create($activityParams);

      // save activity custom data
      if (!empty($params['custom']) &&
        is_array($params['custom'])
      ) {
        CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_activity', $activity->id);
      }

      // Set browser cookie to indicate this petition was already signed.
      $config = CRM_Core_Config::singleton();
      $url_parts = parse_url($config->userFrameworkBaseURL);
      setcookie('signed_' . $params['sid'], $activity->id, time() + $this->cookieExpire, $url_parts['path'], $url_parts['host'], CRM_Utils_System::isSSL());
    }

    return $activity;
  }

  /**
   * @param int $activity_id
   * @param int $contact_id
   * @param int $petition_id
   *
   * @return bool
   */
  public function confirmSignature($activity_id, $contact_id, $petition_id) {
    // change activity status to completed (status_id = 2)
    // I wonder why do we need contact_id when we have activity_id anyway? [chastell]
    $sql = 'UPDATE civicrm_activity SET status_id = 2 WHERE id = %1';
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    $params = array(
      1 => array($activity_id, 'Integer'),
      2 => array($contact_id, 'Integer'),
      3 => array($sourceID, 'Integer'),
    );
    CRM_Core_DAO::executeQuery($sql, $params);

    $sql = 'UPDATE civicrm_activity_contact SET contact_id = %2 WHERE activity_id = %1 AND record_type_id = %3';
    CRM_Core_DAO::executeQuery($sql, $params);
    // remove 'Unconfirmed' tag for this contact
    $tag_name = Civi::settings()->get('tag_unconfirmed');

    $sql = "
DELETE FROM civicrm_entity_tag
WHERE       entity_table = 'civicrm_contact'
AND         entity_id = %1
AND         tag_id = ( SELECT id FROM civicrm_tag WHERE name = %2 )";
    $params = array(
      1 => array($contact_id, 'Integer'),
      2 => array($tag_name, 'String'),
    );
    CRM_Core_DAO::executeQuery($sql, $params);
    // validate arguments to setcookie are numeric to prevent header manipulation
    if (isset($petition_id) && is_numeric($petition_id)
      && isset($activity_id) && is_numeric($activity_id)) {
      // set permanent cookie to indicate this users email address now confirmed
      $config = CRM_Core_Config::singleton();
      $url_parts = parse_url($config->userFrameworkBaseURL);
      setcookie("confirmed_{$petition_id}",
        $activity_id,
        time() + $this->cookieExpire,
        $url_parts['path'],
        $url_parts['host'],
        CRM_Utils_System::isSSL()
      );
      return TRUE;
    }
    else {
      CRM_Core_Error::fatal(ts('Petition Id and/or Activity Id is not of the type Positive.'));
      return FALSE;
    }
  }

  /**
   * Get Petition Signature Total.
   *
   * @param int $surveyId
   *
   * @return array
   */
  public static function getPetitionSignatureTotalbyCountry($surveyId) {
    $countries = array();
    $sql = "
            SELECT count(civicrm_address.country_id) as total,
                IFNULL(country_id,'') as country_id,IFNULL(iso_code,'') as country_iso, IFNULL(civicrm_country.name,'') as country
                FROM  ( civicrm_activity a, civicrm_survey, civicrm_contact )
                LEFT JOIN civicrm_address ON civicrm_address.contact_id = civicrm_contact.id AND civicrm_address.is_primary = 1
                LEFT JOIN civicrm_country ON civicrm_address.country_id = civicrm_country.id
                LEFT JOIN civicrm_activity_contact ac ON ( ac.activity_id = a.id AND  ac.record_type_id = %2 )
                WHERE
                ac.contact_id = civicrm_contact.id AND
                a.activity_type_id = civicrm_survey.activity_type_id AND
                civicrm_survey.id =  %1 AND
                a.source_record_id =  %1  ";

    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    $params = array(
      1 => array($surveyId, 'Integer'),
      2 => array($sourceID, 'Integer'),
    );
    $sql .= " GROUP BY civicrm_address.country_id";
    $fields = array('total', 'country_id', 'country_iso', 'country');

    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()) {
      $row = array();
      foreach ($fields as $field) {
        $row[$field] = $dao->$field;
      }
      $countries[] = $row;
    }
    return $countries;
  }

  /**
   * Get Petition Signature Total.
   *
   * @param int $surveyId
   *
   * @return array
   */
  public static function getPetitionSignatureTotal($surveyId) {
    $surveyInfo = CRM_Campaign_BAO_Petition::getSurveyInfo((int) $surveyId);
    //$activityTypeID = $surveyInfo['activity_type_id'];
    $sql = "
            SELECT
            status_id,count(id) as total
            FROM   civicrm_activity
            WHERE
            source_record_id = " . (int) $surveyId . " AND activity_type_id = " . (int) $surveyInfo['activity_type_id'] . " GROUP BY status_id";

    $statusTotal = array();
    $total = 0;
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $total += $dao->total;
      $statusTotal['status'][$dao->status_id] = $dao->total;
    }
    $statusTotal['count'] = $total;
    return $statusTotal;
  }


  /**
   * @param int $surveyId
   *
   * @return array
   */
  public static function getSurveyInfo($surveyId = NULL) {
    $surveyInfo = array();

    $sql = "
            SELECT  activity_type_id,
            campaign_id,
            s.title,
            ov.label AS activity_type
            FROM  civicrm_survey s, civicrm_option_value ov, civicrm_option_group og
            WHERE s.id = " . (int) $surveyId . "
            AND s.activity_type_id = ov.value
            AND ov.option_group_id = og.id
            AND og.name = 'activity_type'";

    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      //$survey['campaign_id'] = $dao->campaign_id;
      //$survey['campaign_name'] = $dao->campaign_name;
      $surveyInfo['activity_type'] = $dao->activity_type;
      $surveyInfo['activity_type_id'] = $dao->activity_type_id;
      $surveyInfo['title'] = $dao->title;
    }

    return $surveyInfo;
  }

  /**
   * Get Petition Signature Details.
   *
   * @param int $surveyId
   * @param int $status_id
   *
   * @return array
   */
  public static function getPetitionSignature($surveyId, $status_id = NULL) {

    // sql injection protection
    $surveyId = (int) $surveyId;
    $signature = array();

    $sql = "
            SELECT  a.id,
            a.source_record_id as survey_id,
            a.activity_date_time,
            a.status_id,
            civicrm_contact.id as contact_id,
            civicrm_contact.contact_type,civicrm_contact.contact_sub_type,image_URL,
            first_name,last_name,sort_name,
            employer_id,organization_name,
            household_name,
            IFNULL(gender_id,'') AS gender_id,
            IFNULL(state_province_id,'') AS state_province_id,
            IFNULL(country_id,'') as country_id,IFNULL(iso_code,'') as country_iso, IFNULL(civicrm_country.name,'') as country
            FROM (civicrm_activity a, civicrm_survey, civicrm_contact )
            LEFT JOIN civicrm_activity_contact ac ON ( ac.activity_id = a.id AND  ac.record_type_id = %3 )
            LEFT JOIN civicrm_address ON civicrm_address.contact_id = civicrm_contact.id  AND civicrm_address.is_primary = 1
            LEFT JOIN civicrm_country ON civicrm_address.country_id = civicrm_country.id
            WHERE
            ac.contact_id = civicrm_contact.id AND
            a.activity_type_id = civicrm_survey.activity_type_id AND
            civicrm_survey.id =  %1 AND
            a.source_record_id =  %1 ";

    $params = array(1 => array($surveyId, 'Integer'));

    if ($status_id) {
      $sql .= " AND status_id = %2";
      $params[2] = array($status_id, 'Integer');
    }
    $sql .= " ORDER BY  a.activity_date_time";

    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    $params[3] = array($sourceID, 'Integer');

    $fields = array(
      'id',
      'survey_id',
      'contact_id',
      'activity_date_time',
      'activity_type_id',
      'status_id',
      'first_name',
      'last_name',
      'sort_name',
      'gender_id',
      'country_id',
      'state_province_id',
      'country_iso',
      'country',
    );

    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()) {
      $row = array();
      foreach ($fields as $field) {
        $row[$field] = $dao->$field;
      }
      $signature[] = $row;
    }
    return $signature;
  }

  /**
   * This function returns all entities assigned to a specific tag.
   *
   * @param object $tag
   *   An object of a tag.
   *
   * @return array
   *   array of contact ids
   */
  public function getEntitiesByTag($tag) {
    $contactIds = array();
    $entityTagDAO = new CRM_Core_DAO_EntityTag();
    $entityTagDAO->tag_id = $tag['id'];
    $entityTagDAO->find();

    while ($entityTagDAO->fetch()) {
      $contactIds[] = $entityTagDAO->entity_id;
    }
    return $contactIds;
  }

  /**
   * Check if contact has signed this petition.
   *
   * @param int $surveyId
   * @param int $contactId
   *
   * @return array
   */
  public static function checkSignature($surveyId, $contactId) {

    $surveyInfo = CRM_Campaign_BAO_Petition::getSurveyInfo($surveyId);
    $signature = array();
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);

    $sql = "
            SELECT  a.id AS id,
            a.source_record_id AS source_record_id,
            ac.contact_id AS source_contact_id,
            a.activity_date_time AS activity_date_time,
            a.activity_type_id AS activity_type_id,
            a.status_id AS status_id,
            %1 AS survey_title
            FROM   civicrm_activity a
            INNER JOIN civicrm_activity_contact ac ON (ac.activity_id = a.id AND ac.record_type_id = %5)
            WHERE  a.source_record_id = %2
            AND a.activity_type_id = %3
            AND ac.contact_id = %4
";
    $params = array(
      1 => array($surveyInfo['title'], 'String'),
      2 => array($surveyId, 'Integer'),
      3 => array($surveyInfo['activity_type_id'], 'Integer'),
      4 => array($contactId, 'Integer'),
      5 => array($sourceID, 'Integer'),
    );

    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()) {
      $signature[$dao->id]['id'] = $dao->id;
      $signature[$dao->id]['source_record_id'] = $dao->source_record_id;
      $signature[$dao->id]['source_contact_id'] = CRM_Contact_BAO_Contact::displayName($dao->source_contact_id);
      $signature[$dao->id]['activity_date_time'] = $dao->activity_date_time;
      $signature[$dao->id]['activity_type_id'] = $dao->activity_type_id;
      $signature[$dao->id]['status_id'] = $dao->status_id;
      $signature[$dao->id]['survey_title'] = $dao->survey_title;
      $signature[$dao->id]['contactId'] = $dao->source_contact_id;
    }

    return $signature;
  }

  /**
   * Takes an associative array and sends a thank you or email verification email.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @param int $sendEmailMode
   *
   * @throws Exception
   */
  public static function sendEmail($params, $sendEmailMode) {

    /* sendEmailMode
     * CRM_Campaign_Form_Petition_Signature::EMAIL_THANK
     *   connected user via login/pwd - thank you
     *    or dedupe contact matched who doesn't have a tag CIVICRM_TAG_UNCONFIRMED - thank you
     *   or login using fb connect - thank you + click to add msg to fb wall
     *
     * CRM_Campaign_Form_Petition_Signature::EMAIL_CONFIRM
     *  send a confirmation request email
     */

    // check if the group defined by CIVICRM_PETITION_CONTACTS exists, else create it
    $petitionGroupName = Civi::settings()->get('petition_contacts');

    $dao = new CRM_Contact_DAO_Group();
    $dao->title = $petitionGroupName;
    if (!$dao->find(TRUE)) {
      $dao->is_active = 1;
      $dao->visibility = 'User and User Admin Only';
      $dao->save();
    }
    $group_id = $dao->id;

    // get petition info
    $petitionParams['id'] = $params['sid'];
    $petitionInfo = array();
    CRM_Campaign_BAO_Survey::retrieve($petitionParams, $petitionInfo);
    if (empty($petitionInfo)) {
      CRM_Core_Error::fatal('Petition doesn\'t exist.');
    }

    //get the default domain email address.
    list($domainEmailName, $domainEmailAddress) = CRM_Core_BAO_Domain::getNameAndEmail();

    $emailDomain = CRM_Core_BAO_MailSettings::defaultDomain();

    $toName = CRM_Contact_BAO_Contact::displayName($params['contactId']);

    $replyTo = CRM_Core_BAO_Domain::getNoReplyEmailAddress();

    // set additional general message template params (custom tokens to use in email msg templates)
    // tokens then available in msg template as {$petition.title}, etc
    $petitionTokens['title'] = $petitionInfo['title'];
    $petitionTokens['petitionId'] = $params['sid'];
    $tplParams['petition'] = $petitionTokens;

    switch ($sendEmailMode) {
      case CRM_Campaign_Form_Petition_Signature::EMAIL_THANK:

        // add this contact to the CIVICRM_PETITION_CONTACTS group
        // Cannot pass parameter 1 by reference
        $p = array($params['contactId']);
        CRM_Contact_BAO_GroupContact::addContactsToGroup($p, $group_id, 'API');

        if ($params['email-Primary']) {
          CRM_Core_BAO_MessageTemplate::sendTemplate(
            array(
              'groupName' => 'msg_tpl_workflow_petition',
              'valueName' => 'petition_sign',
              'contactId' => $params['contactId'],
              'tplParams' => $tplParams,
              'from' => "\"{$domainEmailName}\" <{$domainEmailAddress}>",
              'toName' => $toName,
              'toEmail' => $params['email-Primary'],
              'replyTo' => $replyTo,
              'petitionId' => $params['sid'],
              'petitionTitle' => $petitionInfo['title'],
            )
          );
        }
        break;

      case CRM_Campaign_Form_Petition_Signature::EMAIL_CONFIRM:
        // create mailing event subscription record for this contact
        // this will allow using a hash key to confirm email address by sending a url link
        $se = CRM_Mailing_Event_BAO_Subscribe::subscribe($group_id,
          $params['email-Primary'],
          $params['contactId'],
          'profile'
        );

        //    require_once 'CRM/Core/BAO/Domain.php';
        //    $domain = CRM_Core_BAO_Domain::getDomain();
        $config = CRM_Core_Config::singleton();
        $localpart = CRM_Core_BAO_MailSettings::defaultLocalpart();

        $replyTo = implode($config->verpSeparator,
            array(
              $localpart . 'c',
              $se->contact_id,
              $se->id,
              $se->hash,
            )
          ) . "@$emailDomain";

        $confirmUrl = CRM_Utils_System::url('civicrm/petition/confirm',
          "reset=1&cid={$se->contact_id}&sid={$se->id}&h={$se->hash}&a={$params['activityId']}&pid={$params['sid']}",
          TRUE
        );
        $confirmUrlPlainText = CRM_Utils_System::url('civicrm/petition/confirm',
          "reset=1&cid={$se->contact_id}&sid={$se->id}&h={$se->hash}&a={$params['activityId']}&pid={$params['sid']}",
          TRUE,
          NULL,
          FALSE
        );

        // set email specific message template params and assign to tplParams
        $petitionTokens['confirmUrl'] = $confirmUrl;
        $petitionTokens['confirmUrlPlainText'] = $confirmUrlPlainText;
        $tplParams['petition'] = $petitionTokens;

        if ($params['email-Primary']) {
          CRM_Core_BAO_MessageTemplate::sendTemplate(
            array(
              'groupName' => 'msg_tpl_workflow_petition',
              'valueName' => 'petition_confirmation_needed',
              'contactId' => $params['contactId'],
              'tplParams' => $tplParams,
              'from' => "\"{$domainEmailName}\" <{$domainEmailAddress}>",
              'toName' => $toName,
              'toEmail' => $params['email-Primary'],
              'replyTo' => $replyTo,
              'petitionId' => $params['sid'],
              'petitionTitle' => $petitionInfo['title'],
              'confirmUrl' => $confirmUrl,
            )
          );
        }
        break;
    }
  }

}
