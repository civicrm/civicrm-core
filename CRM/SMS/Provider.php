<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */
abstract class CRM_SMS_Provider {

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   */
  static private $_singleton = array();
  const MAX_SMS_CHAR = 460;

  /**
   * Singleton function used to manage this object.
   *
   * @param array $providerParams
   * @param bool $force
   *
   * @return object
   */
  public static function &singleton($providerParams = array(), $force = FALSE) {
    $mailingID = CRM_Utils_Array::value('mailing_id', $providerParams);
    $providerID = CRM_Utils_Array::value('provider_id', $providerParams);
    $providerName = CRM_Utils_Array::value('provider', $providerParams);

    if (!$providerID && $mailingID) {
      $providerID = CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_Mailing', $mailingID, 'sms_provider_id', 'id');
      $providerParams['provider_id'] = $providerID;
    }
    if ($providerID) {
      $providerName = CRM_SMS_BAO_Provider::getProviderInfo($providerID, 'name');
    }

    if (!$providerName) {
      CRM_Core_Error::fatal('Provider not known or not provided.');
    }

    $providerName = CRM_Utils_Type::escape($providerName, 'String');
    $cacheKey = "{$providerName}_" . (int) $providerID . "_" . (int) $mailingID;

    if (!isset(self::$_singleton[$cacheKey]) || $force) {
      $ext = CRM_Extension_System::singleton()->getMapper();
      if ($ext->isExtensionKey($providerName)) {
        $providerClass = $ext->keyToClass($providerName);
        require_once "{$providerClass}.php";
      }
      else {
        // If we are running unit tests we simulate an SMS provider with the name "CiviTestSMSProvider"
        if ($providerName !== 'CiviTestSMSProvider') {
          CRM_Core_Error::fatal("Could not locate extension for {$providerName}.");
        }
        $providerClass = 'CiviTestSMSProvider';
      }

      self::$_singleton[$cacheKey] = $providerClass::singleton($providerParams, $force);
    }
    return self::$_singleton[$cacheKey];
  }

  /**
   * Send an SMS Message via the API Server.
   *
   * @param array $recipients
   * @param string $header
   * @param string $message
   * @param int $dncID
   */
  abstract public function send($recipients, $header, $message, $dncID = NULL);

  /**
   * Return message text.
   *
   * Child class could override this function to have better control over the message being sent.
   *
   * @param string $message
   * @param int $contactID
   * @param array $contactDetails
   *
   * @return string
   */
  public function getMessage($message, $contactID, $contactDetails) {
    $html = $message->getHTMLBody();
    $text = $message->getTXTBody();

    return $html ? $html : $text;
  }

  /**
   * Get recipient details.
   *
   * @param array $fields
   * @param array $additionalDetails
   *
   * @return mixed
   */
  public function getRecipientDetails($fields, $additionalDetails) {
    // we could do more altering here
    $fields['To'] = $fields['phone'];
    return $fields;
  }

  /**
   * @param int $apiMsgID
   * @param $message
   * @param array $headers
   * @param int $jobID
   * @param int $userID
   *
   * @return self|null|object
   * @throws CRM_Core_Exception
   */
  public function createActivity($apiMsgID, $message, $headers = array(), $jobID = NULL, $userID = NULL) {
    if ($jobID) {
      $sql = "
SELECT scheduled_id FROM civicrm_mailing m
INNER JOIN civicrm_mailing_job mj ON mj.mailing_id = m.id AND mj.id = %1";
      $sourceContactID = CRM_Core_DAO::singleValueQuery($sql, array(1 => array($jobID, 'Integer')));
    }
    elseif ($userID) {
      $sourceContactID = $userID;
    }
    else {
      $session = CRM_Core_Session::singleton();
      $sourceContactID = $session->get('userID');
    }

    if (!$sourceContactID) {
      $sourceContactID = CRM_Utils_Array::value('Contact', $headers);
    }
    if (!$sourceContactID) {
      return FALSE;
    }

    // note: lets not pass status here, assuming status will be updated by callback
    $activityParams = array(
      'source_contact_id' => $sourceContactID,
      'target_contact_id' => $headers['contact_id'],
      'activity_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'SMS delivery'),
      'activity_date_time' => date('YmdHis'),
      'details' => $message,
      'result' => $apiMsgID,
    );
    return CRM_Activity_BAO_Activity::create($activityParams);
  }

  /**
   * @param string $name
   * @param $type
   * @param bool $abort
   * @param null $default
   * @param string $location
   *
   * @return mixed
   */
  public function retrieve($name, $type, $abort = TRUE, $default = NULL, $location = 'REQUEST') {
    static $store = NULL;
    $value = CRM_Utils_Request::retrieve($name, $type, $store,
      FALSE, $default, $location
    );
    if ($abort && $value === NULL) {
      Civi::log()->warning("Could not find an entry for $name in $location");
      echo "Failure: Missing Parameter<p>";
      exit();
    }
    return $value;
  }

  /**
   * @param $from
   * @param $body
   * @param null $to
   * @param int $trackID
   *
   * @return self|null|object
   * @throws CRM_Core_Exception
   */
  public function processInbound($from, $body, $to = NULL, $trackID = NULL) {
    $message = new CRM_SMS_Message();
    $message->from = $from;
    $message->to = $to;
    $message->body = $body;
    $message->trackID = $trackID;
    // call hook_civicrm_inboundSMS
    CRM_Utils_Hook::inboundSMS($message);

    if (!$message->fromContactID) {
      // find sender by phone number if $fromContactID not set by hook
      $formatFrom = '%' . $this->formatPhone($this->stripPhone($message->from), $like, "like");
      $message->fromContactID = CRM_Core_DAO::singleValueQuery("SELECT contact_id FROM civicrm_phone JOIN civicrm_contact ON civicrm_contact.id = civicrm_phone.contact_id WHERE !civicrm_contact.is_deleted AND phone LIKE %1", array(
        1 => array($formatFrom, 'String'),
      ));
    }

    if (!$message->fromContactID) {
      // unknown mobile sender -- create new contact
      // use fake @mobile.sms email address for new contact since civi
      // requires email or name for all contacts
      $locationTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
      $phoneTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id');
      $phoneloc = array_search('Home', $locationTypes);
      $phonetype = array_search('Mobile', $phoneTypes);
      $stripFrom = $this->stripPhone($message->from);
      $contactparams = array(
        'contact_type' => 'Individual',
        'email' => array(
          1 => array(
            'location_type_id' => $phoneloc,
            'email' => $stripFrom . '@mobile.sms',
          ),
        ),
        'phone' => array(
          1 => array(
            'phone_type_id' => $phonetype,
            'location_type_id' => $phoneloc,
            'phone' => $stripFrom,
          ),
        ),
      );
      $fromContact = CRM_Contact_BAO_Contact::create($contactparams, FALSE, TRUE, FALSE);
      $message->fromContactID = $fromContact->id;
    }

    if (!$message->toContactID) {
      // find recipient if $toContactID not set by hook
      if ($message->to) {
        $message->toContactID = CRM_Core_DAO::singleValueQuery("SELECT contact_id FROM civicrm_phone JOIN civicrm_contact ON civicrm_contact.id = civicrm_phone.contact_id WHERE !civicrm_contact.is_deleted AND phone LIKE %1", array(
          1 => array('%' . $message->to, 'String'),
        ));
      }
      else {
        $message->toContactID = $message->fromContactID;
      }
    }

    if ($message->fromContactID) {
      // note: lets not pass status here, assuming status will be updated by callback
      $activityParams = array(
        'source_contact_id' => $message->toContactID,
        'target_contact_id' => $message->fromContactID,
        'activity_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Inbound SMS'),
        'activity_date_time' => date('YmdHis'),
        'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', 'Completed'),
        'details' => $message->body,
        'phone_number' => $message->from,
      );
      if ($message->trackID) {
        $activityParams['result'] = CRM_Utils_Type::escape($message->trackID, 'String');
      }

      $result = CRM_Activity_BAO_Activity::create($activityParams);
      Civi::log()->info("Inbound SMS recorded for cid={$message->fromContactID}.");
      return $result;
    }
  }

  /**
   * @param $phone
   *
   * @return mixed|string
   */
  public function stripPhone($phone) {
    $newphone = preg_replace('/[^0-9x]/', '', $phone);
    while (substr($newphone, 0, 1) == "1") {
      $newphone = substr($newphone, 1);
    }
    while (strpos($newphone, "xx") !== FALSE) {
      $newphone = str_replace("xx", "x", $newphone);
    }
    while (substr($newphone, -1) == "x") {
      $newphone = substr($newphone, 0, -1);
    }
    return $newphone;
  }

  /**
   * @param $phone
   * @param $kind
   * @param string $format
   *
   * @return mixed|string
   */
  public function formatPhone($phone, &$kind, $format = "dash") {
    $phoneA = explode("x", $phone);
    switch (strlen($phoneA[0])) {
      case 0:
        $kind = "XOnly";
        $area = "";
        $exch = "";
        $uniq = "";
        $ext = $phoneA[1];
        break;

      case 7:
        $kind = $phoneA[1] ? "LocalX" : "Local";
        $area = "";
        $exch = substr($phone, 0, 3);
        $uniq = substr($phone, 3, 4);
        $ext = $phoneA[1];
        break;

      case 10:
        $kind = $phoneA[1] ? "LongX" : "Long";
        $area = substr($phone, 0, 3);
        $exch = substr($phone, 3, 3);
        $uniq = substr($phone, 6, 4);
        $ext = $phoneA[1];
        break;

      default:
        $kind = "Unknown";
        return $phone;
    }

    switch ($format) {
      case "like":
        $newphone = '%' . $area . '%' . $exch . '%' . $uniq . '%' . $ext . '%';
        $newphone = str_replace('%%', '%', $newphone);
        $newphone = str_replace('%%', '%', $newphone);
        return $newphone;

      case "dash":
        $newphone = $area . "-" . $exch . "-" . $uniq . " x" . $ext;
        $newphone = trim(trim(trim($newphone, "x"), "-"));
        return $newphone;

      case "bare":
        $newphone = $area . $exch . $uniq . "x" . $ext;
        $newphone = trim(trim(trim($newphone, "x"), "-"));
        return $newphone;

      case "area":
        return $area;

      default:
        return $phone;
    }
  }

  /**
   * @param $values
   *
   * @return string
   */
  public function urlEncode($values) {
    $uri = '';
    foreach ($values as $key => $value) {
      $value = urlencode($value);
      $uri .= "&{$key}={$value}";
    }
    if (!empty($uri)) {
      $uri = substr($uri, 1);
    }
    return $uri;
  }

}
