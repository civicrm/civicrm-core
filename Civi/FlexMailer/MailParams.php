<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
namespace Civi\FlexMailer;

/**
 * Class MailParams
 *
 * Within CiviMail, we have a few competing data-structures to chose from when
 * representing a mail message:
 *
 *  - ezcMail
 *  - Mail_mime
 *  - $mailParams (from Hook::alterMailParams)
 *
 * The $mailParams data-structure is probably the quirkiest, but it's also
 * the one for which we have the strongest obligation (e.g. it's part of
 * a published hook). This class includes helper functions for
 * converting or validating the $mailParams format.
 *
 * @see \CRM_Utils_Hook::alterMailParams
 */
class MailParams {

  /**
   * Convert from "mail params" to PEAR's Mail_mime.
   *
   * The data-structure which represents a message for purposes of
   * hook_civicrm_alterMailParams does not match the data structure for
   * Mail_mime.
   *
   * @param array $mailParams
   * @return \Mail_mime
   * @see \CRM_Utils_Hook::alterMailParams
   */
  public static function convertMailParamsToMime($mailParams) {
    // The general assumption is that key-value pairs in $mailParams should
    // pass through as email headers, but there are several special-cases
    // (e.g. 'toName', 'toEmail', 'text', 'html', 'attachments', 'headers').

    $message = new \Mail_mime("\n");

    // 1. Consolidate: 'toName' and 'toEmail' should be 'To'.
    $toName = trim($mailParams['toName']);
    $toEmail = trim($mailParams['toEmail']);
    if ($toName == $toEmail || strpos($toName, '@') !== FALSE) {
      $toName = NULL;
    }
    else {
      $toName = \CRM_Utils_Mail::formatRFC2822Name($toName);
    }
    unset($mailParams['toName']);
    unset($mailParams['toEmail']);
    $mailParams['To'] = "$toName <$toEmail>";

    // 2. Apply the other fields.
    foreach ($mailParams as $key => $value) {
      if (empty($value)) {
        continue;
      }

      switch ($key) {
        case 'text':
          $message->setTxtBody($mailParams['text']);
          break;

        case 'html':
          $message->setHTMLBody($mailParams['html']);
          break;

        case 'attachments':
          foreach ($mailParams['attachments'] as $fileID => $attach) {
            $message->addAttachment($attach['fullPath'],
              $attach['mime_type'],
              $attach['cleanName']
            );
          }
          break;

        case 'headers':
          $message->headers($value);
          break;

        default:
          $message->headers(array($key => $value), TRUE);
      }
    }

    \CRM_Utils_Mail::setMimeParams($message);

    return $message;
  }

}
