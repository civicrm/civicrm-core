<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Utils_Mail_Incoming {
  CONST
    EMAILPROCESSOR_CREATE_INDIVIDUAL = 1,
    EMAILPROCESSOR_OVERRIDE = 2,
    EMAILPROCESSOR_IGNORE = 3;

  function formatMail($mail, &$attachments) {
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

  function formatMailPart($part, &$attachments) {
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

    CRM_Core_Error::fatal(ts("No clue about the %1", array(1 => get_class($part))));
  }

  function formatMailMultipart($part, &$attachments) {
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

    CRM_Core_Error::fatal(ts("No clue about the %1", array(1 => get_class($part))));
  }

  function formatMailMultipartMixed($part, &$attachments) {
    $t = '';
    foreach ($part->getParts() as $key => $alternativePart) {
      $t .= self::formatMailPart($alternativePart, $attachments);
    }
    return $t;
  }

  function formatMailMultipartRelated($part, &$attachments) {
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

  function formatMailMultipartDigest($part, &$attachments) {
    $t = '';
    foreach ($part->getParts() as $key => $alternativePart) {
      $t .= "-DIGEST-$key-\n";
      $t .= self::formatMailPart($alternativePart, $attachments);
    }
    $t .= "-DIGEST END---\n";
    return $t;
  }

  function formatMailRfc822Digest($part, &$attachments) {
    $t = '';
    $t .= "-DIGEST-ITEM-\n";
    $t .= "Item:\n\n";
    $t .= self::formatMailpart($part->mail, $attachments);
    $t .= "-DIGEST ITEM END-\n";
    return $t;
  }

  function formatMailMultipartAlternative($part, &$attachments) {
    $t = '';
    foreach ($part->getParts() as $key => $alternativePart) {
      $t .= "-ALTERNATIVE ITEM $key-\n";
      $t .= self::formatMailPart($alternativePart, $attachments);
    }
    $t .= "-ALTERNATIVE END-\n";
    return $t;
  }

  function formatMailText($part, &$attachments) {
    $t = "\n{$part->text}\n";
    return $t;
  }

  function formatMailMultipartReport($part, &$attachments) {
    $t = '';
    foreach ($part->getParts() as $key => $reportPart) {
      $t .= "-REPORT-$key-\n";
      $t .= self::formatMailPart($reportPart, $attachments);
    }
    $t .= "-REPORT END---\n";
    return $t;
  }

  function formatMailFile($part, &$attachments) {
    $attachments[] = array(
      'dispositionType' => $part->dispositionType,
      'contentType' => $part->contentType,
      'mimeType' => $part->mimeType,
      'contentID' => $part->contentId,
      'fullName' => $part->fileName,
    );
    return NULL;
  }

  function formatAddresses($addresses) {
    $fa = array();
    foreach ($addresses as $address) {
      $fa[] = self::formatAddress($address);
    }
    return implode(', ', $fa);
  }

  function formatAddress($address) {
    $name = '';
    if (!empty($address->name)) {
      $name = "{$address->name} ";
    }
    return $name . "<{$address->email}>";
  }

  function &parse(&$file) {

    // check that the file exists and has some content
    if (!file_exists($file) ||
      !trim(file_get_contents($file))
    ) {
      return CRM_Core_Error::createAPIError(ts('%1 does not exists or is empty',
          array(1 => $file)
        ));
    }

    require_once 'ezc/Base/src/ezc_bootstrap.php';
    require_once 'ezc/autoload/mail_autoload.php';

    // explode email to digestable format
    $set    = new ezcMailFileSet(array($file));
    $parser = new ezcMailParser();
    $mail   = $parser->parseMail($set);

    if (!$mail) {
      return CRM_Core_Error::createAPIError(ts('%1 could not be parsed',
          array(1 => $file)
        ));
    }

    // since we only have one fileset
    $mail = $mail[0];

    $mailParams = self::parseMailingObject($mail);
    return $mailParams;
  }

  function parseMailingObject(&$mail) {

    $config = CRM_Core_Config::singleton();

    // get ready for collecting data about this email
    // and put it in a standardized format
    $params = array('is_error' => 0);

    $params['from'] = array();
    self::parseAddress($mail->from, $field, $params['from'], $mail);

    // we definitely need a contact id for the from address
    // if we dont have one, skip this email
    if (empty($params['from']['id'])) {
      return;
    }

    $emailFields = array('to', 'cc', 'bcc');
    foreach ($emailFields as $field) {
      $value = $mail->$field;
      self::parseAddresses($value, $field, $params, $mail);
    }

    // define other parameters
    $params['subject'] = $mail->subject;
    $params['date'] = date("YmdHi00",
      strtotime($mail->getHeader("Date"))
    );
    $attachments = array();
    $params['body'] = self::formatMailPart($mail->body, $attachments);

    // format and move attachments to the civicrm area
    if (!empty($attachments)) {
      $date = date('Ymdhis');
      $config = CRM_Core_Config::singleton();
      for ($i = 0; $i < count($attachments); $i++) {
        $attachNum = $i + 1;
        $fileName  = basename($attachments[$i]['fullName']);
        $newName   = CRM_Utils_File::makeFileName($fileName);
        $location  = $config->uploadDir . $newName;

        // move file to the civicrm upload directory
        rename($attachments[$i]['fullName'], $location);

        $mimeType = "{$attachments[$i]['contentType']}/{$attachments[$i]['mimeType']}";

        $params["attachFile_$attachNum"] = array(
          'uri' => $fileName,
          'type' => $mimeType,
          'upload_date' => $date,
          'location' => $location,
        );
      }
    }

    return $params;
  }

  function parseAddress(&$address, &$params, &$subParam, &$mail) {
    // CRM-9484
    if (empty($address->email)) {
      return;
    }

    $subParam['email'] = $address->email;
    $subParam['name'] = $address->name;


    $contactID = self::getContactID($subParam['email'],
      $subParam['name'],
      TRUE,
      $mail
    );
    $subParam['id'] = $contactID ? $contactID : NULL;
  }

  function parseAddresses(&$addresses, $token, &$params, &$mail) {
    $params[$token] = array();

    foreach ($addresses as $address) {
      $subParam = array();
      self::parseAddress($address, $params, $subParam, $mail);
      $params[$token][] = $subParam;
    }
  }

  /**
   * retrieve a contact ID and if not present
   * create one with this email
   */
  function getContactID($email, $name = NULL, $create = TRUE, &$mail) {
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
    $params = array(
      'contact_type' => 'Individual',
      'email-Primary' => $email,
    );

    CRM_Utils_String::extractName($name, $params);

    return CRM_Contact_BAO_Contact::createProfileContact($params,
      CRM_Core_DAO::$_nullArray
    );
  }
}

