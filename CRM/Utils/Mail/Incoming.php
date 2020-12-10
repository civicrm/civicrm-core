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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_Mail_Incoming {
  const
    EMAILPROCESSOR_CREATE_INDIVIDUAL = 1,
    EMAILPROCESSOR_OVERRIDE = 2,
    EMAILPROCESSOR_IGNORE = 3;

  /**
   * @param $mail
   * @param $attachments
   *
   * @return string
   */
  public static function formatMail($mail, &$attachments) {
    $t = '';
    $t .= "From:      " . self::formatAddress($mail->from) . "\n";
    $t .= "To:        " . self::formatAddresses($mail->to) . "\n";
    $t .= "Cc:        " . self::formatAddresses($mail->cc) . "\n";
    $t .= "Bcc:       " . self::formatAddresses($mail->bcc) . "\n";
    $t .= 'Date:      ' . date(DATE_RFC822, $mail->timestamp) . "\n";
    $t .= 'Subject:   ' . $mail->subject . "\n";
    $t .= "MessageId: " . $mail->messageId . "\n";
    $t .= "\n";
    $t .= self::formatMailPart($mail->body, $attachments);
    return $t;
  }

  /**
   * @param $part
   * @param $attachments
   *
   * @throws Exception
   */
  public static function formatMailPart($part, &$attachments) {
    if ($part instanceof ezcMail) {
      return self::formatMail($part, $attachments);
    }

    if ($part instanceof ezcMailText) {
      return self::formatMailText($part, $attachments);
    }

    if ($part instanceof ezcMailFile) {
      return self::formatMailFile($part, $attachments);
    }

    if ($part instanceof ezcMailRfc822Digest) {
      return self::formatMailRfc822Digest($part, $attachments);
    }

    if ($part instanceof ezcMailMultiPart) {
      return self::formatMailMultipart($part, $attachments);
    }

    if ($part instanceof ezcMailDeliveryStatus) {
      return self::formatMailDeliveryStatus($part);
    }

    // CRM-19111 - Handle blank emails with a subject.
    if (!$part) {
      return NULL;
    }

    return self::formatMailUnrecognisedPart($part);
  }

  /**
   * @param $part
   * @param $attachments
   *
   * @throws Exception
   */
  public static function formatMailMultipart($part, &$attachments) {
    if ($part instanceof ezcMailMultiPartAlternative) {
      return self::formatMailMultipartAlternative($part, $attachments);
    }

    if ($part instanceof ezcMailMultiPartDigest) {
      return self::formatMailMultipartDigest($part, $attachments);
    }

    if ($part instanceof ezcMailMultiPartRelated) {
      return self::formatMailMultipartRelated($part, $attachments);
    }

    if ($part instanceof ezcMailMultiPartMixed) {
      return self::formatMailMultipartMixed($part, $attachments);
    }

    if ($part instanceof ezcMailMultipartReport) {
      return self::formatMailMultipartReport($part, $attachments);
    }

    if ($part instanceof ezcMailDeliveryStatus) {
      return self::formatMailDeliveryStatus($part);
    }

    return self::formatMailUnrecognisedPart($part);
  }

  /**
   * @param $part
   * @param $attachments
   *
   * @return string
   */
  public static function formatMailMultipartMixed($part, &$attachments) {
    $t = '';
    foreach ($part->getParts() as $key => $alternativePart) {
      $t .= self::formatMailPart($alternativePart, $attachments);
    }
    return $t;
  }

  /**
   * @param $part
   * @param $attachments
   *
   * @return string
   */
  public static function formatMailMultipartRelated($part, &$attachments) {
    $t = '';
    $t .= "-RELATED MAIN PART-\n";
    $t .= self::formatMailPart($part->getMainPart(), $attachments);
    foreach ($part->getRelatedParts() as $key => $alternativePart) {
      $t .= "-RELATED PART $key-\n";
      $t .= self::formatMailPart($alternativePart, $attachments);
    }
    $t .= "-RELATED END-\n";
    return $t;
  }

  /**
   * @param $part
   * @param $attachments
   *
   * @return string
   */
  public static function formatMailMultipartDigest($part, &$attachments) {
    $t = '';
    foreach ($part->getParts() as $key => $alternativePart) {
      $t .= "-DIGEST-$key-\n";
      $t .= self::formatMailPart($alternativePart, $attachments);
    }
    $t .= "-DIGEST END---\n";
    return $t;
  }

  /**
   * @param $part
   * @param $attachments
   *
   * @return string
   */
  public static function formatMailRfc822Digest($part, &$attachments) {
    $t = '';
    $t .= "-DIGEST-ITEM-\n";
    $t .= "Item:\n\n";
    $t .= self::formatMailpart($part->mail, $attachments);
    $t .= "-DIGEST ITEM END-\n";
    return $t;
  }

  /**
   * @param $part
   * @param $attachments
   *
   * @return string
   */
  public static function formatMailMultipartAlternative($part, &$attachments) {
    $t = '';
    foreach ($part->getParts() as $key => $alternativePart) {
      $t .= "-ALTERNATIVE ITEM $key-\n";
      $t .= self::formatMailPart($alternativePart, $attachments);
    }
    $t .= "-ALTERNATIVE END-\n";
    return $t;
  }

  /**
   * @param $part
   * @param $attachments
   *
   * @return string
   */
  public static function formatMailText($part, &$attachments) {
    $t = "\n{$part->text}\n";
    return $t;
  }

  /**
   * @param $part
   * @param $attachments
   *
   * @return string
   */
  public static function formatMailMultipartReport($part, &$attachments) {
    $t = '';
    foreach ($part->getParts() as $key => $reportPart) {
      $t .= "-REPORT-$key-\n";
      $t .= self::formatMailPart($reportPart, $attachments);
    }
    $t .= "-REPORT END---\n";
    return $t;
  }

  /**
   * @param $part
   *
   * @return string
   */
  public static function formatMailDeliveryStatus($part) {
    $t = '';
    $t .= "-DELIVERY STATUS BEGIN-\n";
    $t .= $part->generateBody();
    $t .= "-DELIVERY STATUS END-\n";
    return $t;
  }

  /**
   * @param $part
   *
   * @return string
   */
  public function formatUnrecognisedPart($part) {
    CRM_Core_Error::debug_log_message(ts('CRM_Utils_Mail_Incoming: Unable to handle message part of type "%1".', [1 => get_class($part)]));
    return ts('Unrecognised message part of type "%1".', [1 => get_class($part)]);
  }

  /**
   * @param $part
   * @param $attachments
   *
   * @return null
   */
  public static function formatMailFile($part, &$attachments) {
    $attachments[] = [
      'dispositionType' => $part->dispositionType,
      'contentType' => $part->contentType,
      'mimeType' => $part->mimeType,
      'contentID' => $part->contentId,
      'fullName' => $part->fileName,
    ];
    return NULL;
  }

  /**
   * @param $addresses
   *
   * @return string
   */
  public static function formatAddresses($addresses) {
    $fa = [];
    foreach ($addresses as $address) {
      $fa[] = self::formatAddress($address);
    }
    return implode(', ', $fa);
  }

  /**
   * @param $address
   *
   * @return string
   */
  public static function formatAddress($address) {
    $name = '';
    if (!empty($address->name)) {
      $name = "{$address->name} ";
    }
    return $name . "<{$address->email}>";
  }

  /**
   * @param $file
   *
   * @return array
   * @throws Exception
   */
  public function &parse(&$file) {

    // check that the file exists and has some content
    if (!file_exists($file) ||
      !trim(file_get_contents($file))
    ) {
      return CRM_Core_Error::createAPIError(ts('%1 does not exists or is empty',
        [1 => $file]
      ));
    }

    // explode email to digestable format
    $set = new ezcMailFileSet([$file]);
    $parser = new ezcMailParser();
    $mail = $parser->parseMail($set);

    if (!$mail) {
      return CRM_Core_Error::createAPIError(ts('%1 could not be parsed',
        [1 => $file]
      ));
    }

    // since we only have one fileset
    $mail = $mail[0];

    $mailParams = self::parseMailingObject($mail);
    return $mailParams;
  }

  /**
   * @param $mail
   * @param $createContact
   * @param $requireContact
   *
   * @return array
   */
  public static function parseMailingObject(&$mail, $createContact = TRUE, $requireContact = TRUE) {

    $config = CRM_Core_Config::singleton();

    // get ready for collecting data about this email
    // and put it in a standardized format
    $params = ['is_error' => 0];

    // Sometimes $mail->from is unset because ezcMail didn't handle format
    // of From header. CRM-19215.
    if (!isset($mail->from)) {
      if (preg_match('/^([^ ]*)( (.*))?$/', $mail->getHeader('from'), $matches)) {
        $mail->from = new ezcMailAddress($matches[1], trim($matches[2]));
      }
    }

    $params['from'] = [];
    self::parseAddress($mail->from, $field, $params['from'], $mail, $createContact);

    // we definitely need a contact id for the from address
    // if we dont have one, skip this email
    if ($requireContact && empty($params['from']['id'])) {
      return NULL;
    }

    $emailFields = ['to', 'cc', 'bcc'];
    foreach ($emailFields as $field) {
      $value = $mail->$field;
      self::parseAddresses($value, $field, $params, $mail, $createContact);
    }

    // define other parameters
    $params['subject'] = $mail->subject;
    $params['date'] = date("YmdHi00",
      strtotime($mail->getHeader("Date"))
    );
    $attachments = [];
    $params['body'] = self::formatMailPart($mail->body, $attachments);

    // format and move attachments to the civicrm area
    if (!empty($attachments)) {
      $date = date('YmdHis');
      $config = CRM_Core_Config::singleton();
      for ($i = 0; $i < count($attachments); $i++) {
        $attachNum = $i + 1;
        $fileName = basename($attachments[$i]['fullName']);
        $newName = CRM_Utils_File::makeFileName($fileName);
        $location = $config->uploadDir . $newName;

        // move file to the civicrm upload directory
        rename($attachments[$i]['fullName'], $location);

        $mimeType = "{$attachments[$i]['contentType']}/{$attachments[$i]['mimeType']}";

        $params["attachFile_$attachNum"] = [
          'uri' => $fileName,
          'type' => $mimeType,
          'upload_date' => $date,
          'location' => $location,
        ];
      }
    }

    return $params;
  }

  /**
   * @param $address
   * @param array $params
   * @param $subParam
   * @param $mail
   * @param $createContact
   */
  public static function parseAddress(&$address, &$params, &$subParam, &$mail, $createContact = TRUE) {
    // CRM-9484
    if (empty($address->email)) {
      return;
    }

    $subParam['email'] = $address->email;
    $subParam['name'] = $address->name;

    $contactID = self::getContactID($subParam['email'],
      $subParam['name'],
      $createContact,
      $mail
    );
    $subParam['id'] = $contactID ? $contactID : NULL;
  }

  /**
   * @param $addresses
   * @param $token
   * @param array $params
   * @param $mail
   * @param $createContact
   */
  public static function parseAddresses(&$addresses, $token, &$params, &$mail, $createContact = TRUE) {
    $params[$token] = [];

    foreach ($addresses as $address) {
      $subParam = [];
      self::parseAddress($address, $params, $subParam, $mail, $createContact);
      $params[$token][] = $subParam;
    }
  }

  /**
   * Retrieve a contact ID and if not present.
   *
   * Create one with this email
   *
   * @param string $email
   * @param string $name
   * @param bool $create
   * @param string $mail
   *
   * @return int|null
   */
  public static function getContactID($email, $name = NULL, $create = TRUE, &$mail) {
    $dao = CRM_Contact_BAO_Contact::matchContactOnEmail($email, 'Individual');

    $contactID = NULL;
    if ($dao) {
      $contactID = $dao->contact_id;
    }

    $result = NULL;
    CRM_Utils_Hook::emailProcessorContact($email, $contactID, $result);

    if (!empty($result)) {
      if ($result['action'] == self::EMAILPROCESSOR_IGNORE) {
        return NULL;
      }
      if ($result['action'] == self::EMAILPROCESSOR_OVERRIDE) {
        return $result['contactID'];
      }

      // else this is now create individual
      // so we just fall out and do what we normally do
    }

    if ($contactID) {
      return $contactID;
    }

    if (!$create) {
      return NULL;
    }

    // contact does not exist, lets create it
    $params = [
      'contact_type' => 'Individual',
      'email-Primary' => $email,
    ];

    CRM_Utils_String::extractName($name, $params);

    return CRM_Contact_BAO_Contact::createProfileContact($params);
  }

}
