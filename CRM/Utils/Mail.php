<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
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
class CRM_Utils_Mail {

  /**
   * Wrapper function to send mail in CiviCRM. Hooks are called from this function. The input parameter
   * is an associateive array which holds the values of field needed to send an email. These are:
   *
   * from    : complete from envelope
   * toName  : name of person to send email
   * toEmail : email address to send to
   * cc      : email addresses to cc
   * bcc     : email addresses to bcc
   * subject : subject of the email
   * text    : text of the message
   * html    : html version of the message
   * replyTo : reply-to header in the email
   * attachments: an associative array of
   *   fullPath : complete pathname to the file
   *   mime_type: mime type of the attachment
   *   cleanName: the user friendly name of the attachmment
   *
   * @param array $params (by reference)
   *
   * @access public
   *
   * @return boolean true if a mail was sent, else false
   */
  static function send(&$params) {
    $returnPath       = CRM_Core_BAO_MailSettings::defaultReturnPath();
    $includeMessageId = CRM_Core_BAO_MailSettings::includeMessageId();
    $emailDomain      = CRM_Core_BAO_MailSettings::defaultDomain();
    $from             = CRM_Utils_Array::value('from', $params);
    if (!$returnPath) {
      $returnPath = self::pluckEmailFromHeader($from);
    }
    $params['returnPath'] = $returnPath;

    // first call the mail alter hook
    CRM_Utils_Hook::alterMailParams($params);

    // check if any module has aborted mail sending
    if (
      CRM_Utils_Array::value('abortMailSend', $params) ||
      !CRM_Utils_Array::value('toEmail', $params)
    ) {
      return FALSE;
    }

    $textMessage = CRM_Utils_Array::value('text', $params);
    $htmlMessage = CRM_Utils_Array::value('html', $params);
    $attachments = CRM_Utils_Array::value('attachments', $params);

    // CRM-6224
    if (trim(CRM_Utils_String::htmlToText($htmlMessage)) == '') {
      $htmlMessage = FALSE;
    }

    $headers         = array();
    // CRM-10699 support custom email headers
    if (CRM_Utils_Array::value('headers', $params)) {
      $headers = array_merge($headers, $params['headers']);
    }
    $headers['From'] = $params['from'];
    $headers['To']   =
      self::formatRFC822Email(
        CRM_Utils_Array::value('toName', $params),
        CRM_Utils_Array::value('toEmail', $params),
        FALSE
      );
    $headers['Cc'] = CRM_Utils_Array::value('cc', $params);
    $headers['Bcc'] = CRM_Utils_Array::value('bcc', $params);
    $headers['Subject'] = CRM_Utils_Array::value('subject', $params);
    $headers['Content-Type'] = $htmlMessage ? 'multipart/mixed; charset=utf-8' : 'text/plain; charset=utf-8';
    $headers['Content-Disposition'] = 'inline';
    $headers['Content-Transfer-Encoding'] = '8bit';
    $headers['Return-Path'] = CRM_Utils_Array::value('returnPath', $params);

    // CRM-11295: Omit reply-to headers if empty; this avoids issues with overzealous mailservers
    $replyTo = CRM_Utils_Array::value('replyTo', $params, $from);

    if (!empty($replyTo)) {
      $headers['Reply-To'] = $replyTo;
    }
    $headers['Date'] = date('r');
    if ($includeMessageId) {
      $headers['Message-ID'] = '<' . uniqid('civicrm_', TRUE) . "@$emailDomain>";
    }
    if (CRM_Utils_Array::value('autoSubmitted', $params)) {
      $headers['Auto-Submitted'] = "Auto-Generated";
    }

    //make sure we has to have space, CRM-6977
    foreach (array('From', 'To', 'Cc', 'Bcc', 'Reply-To', 'Return-Path') as $fld) {
      if (isset($headers[$fld])) {
        $headers[$fld] = str_replace('"<', '" <', $headers[$fld]);
      }
    }

    // quote FROM, if comma is detected AND is not already quoted. CRM-7053
    if (strpos($headers['From'], ',') !== FALSE) {
      $from = explode(' <', $headers['From']);
      $headers['From'] = self::formatRFC822Email(
        $from[0],
        substr(trim($from[1]), 0, -1),
        TRUE
      );
    }

    require_once 'Mail/mime.php';
    $msg = new Mail_mime("\n");
    if ($textMessage) {
      $msg->setTxtBody($textMessage);
    }

    if ($htmlMessage) {
      $msg->setHTMLBody($htmlMessage);
    }

    if (!empty($attachments)) {
      foreach ($attachments as $fileID => $attach) {
        $msg->addAttachment(
          $attach['fullPath'],
          $attach['mime_type'],
          $attach['cleanName']
        );
      }
    }

    $message = self::setMimeParams($msg);
    $headers = &$msg->headers($headers);

    $to = array($params['toEmail']);

    //get emails from headers, since these are
    //combination of name and email addresses.
    if (CRM_Utils_Array::value('Cc', $headers)) {
      $to[] = CRM_Utils_Array::value('Cc', $headers);
    }
    if (CRM_Utils_Array::value('Bcc', $headers)) {
      $to[] = CRM_Utils_Array::value('Bcc', $headers);
      unset($headers['Bcc']);
    }

    $result = NULL;
    $mailer = CRM_Core_Config::getMailer();
    if (is_object($mailer)) {
      CRM_Core_Error::ignoreException();
      $result = $mailer->send($to, $headers, $message);
      CRM_Core_Error::setCallback();
      if (is_a($result, 'PEAR_Error')) {
        $message = self::errorMessage($mailer, $result);
        // append error message in case multiple calls are being made to
        // this method in the course of sending a batch of messages.
        CRM_Core_Session::setStatus($message, ts('Mailing Error'), 'error');
        return FALSE;
      }
      // CRM-10699
      CRM_Utils_Hook::postEmailSend($params);
      return TRUE;
    }
    return FALSE;
  }

  static function errorMessage($mailer, $result) {
    $message = '<p>' . ts('An error occurred when CiviCRM attempted to send an email (via %1). If you received this error after submitting on online contribution or event registration - the transaction was completed, but we were unable to send the email receipt.', array(
      1 => 'SMTP')) . '</p>' . '<p>' . ts('The mail library returned the following error message:') . '<br /><span class="font-red"><strong>' . $result->getMessage() . '</strong></span></p>' . '<p>' . ts('This is probably related to a problem in your Outbound Email Settings (Administer CiviCRM &raquo; System Settings &raquo; Outbound Email), OR the FROM email address specifically configured for your contribution page or event. Possible causes are:') . '</p>';

    if (is_a($mailer, 'Mail_smtp')) {
      $message .= '<ul>' . '<li>' . ts('Your SMTP Username or Password are incorrect.') . '</li>' . '<li>' . ts('Your SMTP Server (machine) name is incorrect.') . '</li>' . '<li>' . ts('You need to use a Port other than the default port 25 in your environment.') . '</li>' . '<li>' . ts('Your SMTP server is just not responding right now (it is down for some reason).') . '</li>';
    }
    else {
      $message .= '<ul>' . '<li>' . ts('Your Sendmail path is incorrect.') . '</li>' . '<li>' . ts('Your Sendmail argument is incorrect.') . '</li>';
    }

    $message .= '<li>' . ts('The FROM Email Address configured for this feature may not be a valid sender based on your email service provider rules.') . '</li>' . '</ul>' . '<p>' . ts('Check <a href="%1">this page</a> for more information.', array(
      1 => CRM_Utils_System::docURL2('user/initial-set-up/email-system-configuration', TRUE))) . '</p>';

    return $message;
  }

  static function logger(&$to, &$headers, &$message) {
    if (is_array($to)) {
      $toString = implode(', ', $to);
      $fileName = $to[0];
    }
    else {
      $toString = $fileName = $to;
    }
    $content = "To: " . $toString . "\n";
    foreach ($headers as $key => $val) {
      $content .= "$key: $val\n";
    }
    $content .= "\n" . $message . "\n";

    if (is_numeric(CIVICRM_MAIL_LOG)) {
      $config = CRM_Core_Config::singleton();
      // create the directory if not there
      $dirName = $config->configAndLogDir . 'mail' . DIRECTORY_SEPARATOR;
      CRM_Utils_File::createDir($dirName);
      $fileName = md5(uniqid(CRM_Utils_String::munge($fileName))) . '.txt';
      file_put_contents($dirName . $fileName,
        $content
      );
    }
    else {
      file_put_contents(CIVICRM_MAIL_LOG, $content, FILE_APPEND);
    }
  }

  /**
   * Get the email address itself from a formatted full name + address string
   *
   * Ugly but working.
   *
   * @param  string $header  the full name + email address string
   *
   * @return string          the plucked email address
   * @static
   */
  static function pluckEmailFromHeader($header) {
    preg_match('/<([^<]*)>$/', $header, $matches);

    if (isset($matches[1])) {
      return $matches[1];
    }
    return NULL;
  }

  /**
   * Get the Active outBound email
   *
   * @return boolean true if valid outBound email configuration found, false otherwise
   * @access public
   * @static
   */
  static function validOutBoundMail() {
    $mailingInfo = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
      'mailing_backend'
    );
    if ($mailingInfo['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_MAIL) {
      return TRUE;
    }
    elseif ($mailingInfo['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_SMTP) {
      if (!isset($mailingInfo['smtpServer']) || $mailingInfo['smtpServer'] == '' ||
        $mailingInfo['smtpServer'] == 'YOUR SMTP SERVER' ||
        ($mailingInfo['smtpAuth'] && ($mailingInfo['smtpUsername'] == '' || $mailingInfo['smtpPassword'] == ''))
      ) {
        return FALSE;
      }
      return TRUE;
    }
    elseif ($mailingInfo['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_SENDMAIL) {
      if (!$mailingInfo['sendmail_path'] || !$mailingInfo['sendmail_args']) {
        return FALSE;
      }
      return TRUE;
    }
    elseif ($mailingInfo['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_REDIRECT_TO_DB) {
      return TRUE;
    }
    return FALSE;
  }

  static function &setMimeParams(&$message, $params = NULL) {
    static $mimeParams = NULL;
    if (!$params) {
      if (!$mimeParams) {
        $mimeParams = array(
          'text_encoding' => '8bit',
          'html_encoding' => '8bit',
          'head_charset' => 'utf-8',
          'text_charset' => 'utf-8',
          'html_charset' => 'utf-8',
        );
      }
      $params = $mimeParams;
    }
    return $message->get($params);
  }

  static function formatRFC822Email($name, $email, $useQuote = FALSE) {
    $result = NULL;

    $name = trim($name);

    // strip out double quotes if present at the beginning AND end
    if (substr($name, 0, 1) == '"' &&
      substr($name, -1, 1) == '"'
    ) {
      $name = substr($name, 1, -1);
    }

    if (!empty($name)) {
      // escape the special characters
      $name = str_replace(array('<', '"', '>'),
        array('\<', '\"', '\>'),
        $name
      );
      if (strpos($name, ',') !== FALSE ||
        $useQuote
      ) {
        // quote the string if it has a comma
        $name = '"' . $name . '"';
      }

      $result = "$name ";
    }

    $result .= "<{$email}>";
    return $result;
  }

  /**
   * Takes a string and checks to see if it needs to be escaped / double quoted
   * and if so does the needful and return the formatted name
   *
   * This code has been copied and adapted from ezc/Mail/src/tools.php
   */
  static function formatRFC2822Name($name) {
    $name = trim($name);
    if (!empty($name)) {
      // remove the quotes around the name part if they are already there
      if (substr($name, 0, 1) == '"' && substr($name, -1) == '"') {
        $name = substr($name, 1, -1);
      }

      // add slashes to " and \ and surround the name part with quotes
      if (strpbrk($name, ",@<>:;'\"") !== FALSE) {
        $name = '"' . addcslashes($name, '\\"') . '"';
      }
    }

    return $name;
  }
}

