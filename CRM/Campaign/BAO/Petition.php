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

use Civi\Api4\Group;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Campaign_BAO_Petition extends CRM_Campaign_BAO_Survey {

  /**
   * Length of the cookie's created by this class
   *
   * @var int
   */
  protected $cookieExpire;

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
    // expire cookie in one day
    $this->cookieExpire = (1 * 60 * 60 * 24);
  }

  /**
   * Takes an associative array and creates a petition signature activity.
   *
   * @param array $params
   *   an assoc array of name/value pairs.
   *
   * @return mixed
   *   CRM_Campaign_BAO_Petition or NULl or void
   */
  public function createSignature($params) {
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

      $activityParams = [
        'source_contact_id' => $params['contactId'],
        'target_contact_id' => $params['contactId'],
        'source_record_id' => $params['sid'],
        'subject' => $surveyInfo['title'],
        'activity_type_id' => $surveyInfo['activity_type_id'],
        'activity_date_time' => date("YmdHis"),
        'status_id' => $params['statusId'],
        'activity_campaign_id' => $params['activity_campaign_id'],
      ];

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
    // change activity status to completed
    \Civi\Api4\Activity::update(FALSE)
      ->addValue('status_id:name', 'Completed')
      ->addWhere('id', '=', $activity_id)
      ->execute();
    \Civi\Api4\ActivityContact::update(FALSE)
      ->addValue('contact_id', $contact_id)
      ->addWhere('activity_id', '=', $activity_id)
      ->addWhere('record_type_id:name', '=', 'Activity Source')
      ->execute();

    // remove 'Unconfirmed' tag for this contact
    \Civi\Api4\EntityTag::delete(FALSE)
      ->addWhere('tag_id:name', '=', Civi::settings()->get('tag_unconfirmed'))
      ->addWhere('entity_table', '=', 'civicrm_contact')
      ->addWhere('entity_id', '=', $contact_id)
      ->execute();

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
      throw new CRM_Core_Exception(ts('Petition Id and/or Activity Id is not of the type Positive.'));
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
    $countries = [];
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
    $params = [
      1 => [$surveyId, 'Integer'],
      2 => [$sourceID, 'Integer'],
    ];
    $sql .= " GROUP BY civicrm_address.country_id";
    $fields = ['total', 'country_id', 'country_iso', 'country'];

    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()) {
      $row = [];
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

    $statusTotal = [];
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
    $surveyInfo = [];

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
    $signature = [];

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

    $params = [1 => [$surveyId, 'Integer']];

    if ($status_id) {
      $sql .= " AND status_id = %2";
      $params[2] = [$status_id, 'Integer'];
    }
    $sql .= " ORDER BY  a.activity_date_time";

    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    $params[3] = [$sourceID, 'Integer'];

    $fields = [
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
    ];

    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()) {
      $row = [];
      foreach ($fields as $field) {
        $row[$field] = $dao->$field;
      }
      $signature[] = $row;
    }
    return $signature;
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
    $signature = [];
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
    $params = [
      1 => [$surveyInfo['title'], 'String'],
      2 => [$surveyId, 'Integer'],
      3 => [$surveyInfo['activity_type_id'], 'Integer'],
      4 => [$contactId, 'Integer'],
      5 => [$sourceID, 'Integer'],
    ];

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
   *   CRM_Campaign_Form_Petition_Signature::EMAIL_THANK or CRM_Campaign_Form_Petition_Signature::EMAIL_CONFIRM
   *
   * @throws CRM_Core_Exception
   */
  public static function sendEmail(array $params, int $sendEmailMode): void {
    $surveyID = $params['sid'];
    $contactID = $params['contactId'];
    $activityID = $params['activityId'] ?? NULL;
    $group_id = Group::get(FALSE)->addWhere('title', '=', Civi::settings()->get('petition_contacts'))->addSelect('id')->execute()->first()['id'] ?? NULL;
    if (!$group_id) {
      $group_id = Group::create(FALSE)->setValues([
        'title' => Civi::settings()->get('petition_contacts'),
        'visibility' => 'User and User Admin Only',
      ])->execute()->first()['id'];
    }

    // get petition info
    $petitionParams['id'] = $params['sid'];
    $petitionInfo = [];
    CRM_Campaign_BAO_Survey::retrieve($petitionParams, $petitionInfo);
    if (empty($petitionInfo)) {
      throw new CRM_Core_Exception('Petition doesn\'t exist.');
    }

    //get the default domain email address.
    [$domainEmailName, $domainEmailAddress] = CRM_Core_BAO_Domain::getNameAndEmail();

    $emailDomain = CRM_Core_BAO_MailSettings::defaultDomain();

    $toName = CRM_Contact_BAO_Contact::displayName($params['contactId']);

    $replyTo = CRM_Core_BAO_Domain::getNoReplyEmailAddress();

    // set additional general message template params (custom tokens to use in email msg templates)
    // tokens then available in msg template as {$petition.title}, etc
    $petitionTokens['title'] = $petitionInfo['title'];
    $petitionTokens['petitionId'] = $params['sid'];
    $tplParams['survey_id'] = $params['sid'];
    $tplParams['petitionTitle'] = $petitionInfo['title'];
    $tplParams['petition'] = $petitionTokens;

    switch ($sendEmailMode) {
      case CRM_Campaign_Form_Petition_Signature::EMAIL_THANK:
        CRM_Contact_BAO_GroupContact::addContactsToGroup([$contactID], $group_id, 'API');

        if ($params['email-Primary']) {
          CRM_Core_BAO_MessageTemplate::sendTemplate(
            [
              'workflow' => 'petition_sign',
              'modelProps' => ['surveyID' => $surveyID, 'contactID' => $contactID],
              'from' => "\"{$domainEmailName}\" <{$domainEmailAddress}>",
              'toName' => $toName,
              'toEmail' => $params['email-Primary'],
              'replyTo' => $replyTo,
            ]
          );
        }
        break;

      case CRM_Campaign_Form_Petition_Signature::EMAIL_CONFIRM:
        // create mailing event subscription record for this contact
        // this will allow using a hash key to confirm email address by sending a url link
        $se = CRM_Mailing_Event_BAO_MailingEventSubscribe::subscribe($group_id,
          $params['email-Primary'],
          $params['contactId'],
          'profile'
        );

        //    require_once 'CRM/Core/BAO/Domain.php';
        //    $domain = CRM_Core_BAO_Domain::getDomain();
        $config = CRM_Core_Config::singleton();
        $localpart = CRM_Core_BAO_MailSettings::defaultLocalpart();

        $replyTo = implode($config->verpSeparator,
            [
              $localpart . 'c',
              $se->contact_id,
              $se->id,
              $se->hash,
            ]
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
            [
              'groupName' => 'msg_tpl_workflow_petition',
              'workflow' => 'petition_confirmation_needed',
              'contactId' => $params['contactId'],
              'tplParams' => $tplParams,
              'from' => "\"{$domainEmailName}\" <{$domainEmailAddress}>",
              'toName' => $toName,
              'toEmail' => $params['email-Primary'],
              'replyTo' => $replyTo,
              'petitionId' => $params['sid'],
              'petitionTitle' => $petitionInfo['title'],
              'confirmUrl' => $confirmUrl,
            ]
          );
        }
        break;
    }
  }

}
