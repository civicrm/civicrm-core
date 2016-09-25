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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 */
class CRM_Utils_Mail {

  /**
   * Create a new mailer to send any mail from the application.
   *
   * Note: The mailer is opened in persistent mode.
   *
   * Note: You probably don't want to call this directly. Get a reference
   * to the mailer through the container.
   *
   * @return Mail
   */
  public static function createMailer() {
    $mailingInfo = Civi::settings()->get('mailing_backend');

    if ($mailingInfo['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_REDIRECT_TO_DB ||
      (defined('CIVICRM_MAILER_SPOOL') && CIVICRM_MAILER_SPOOL)
    ) {
      $mailer = self::_createMailer('CRM_Mailing_BAO_Spool', array());
    }
    elseif ($mailingInfo['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_SMTP) {
      if ($mailingInfo['smtpServer'] == '' || !$mailingInfo['smtpServer']) {
        CRM_Core_Error::debug_log_message(ts('There is no valid smtp server setting. Click <a href=\'%1\'>Administer >> System Setting >> Outbound Email</a> to set the SMTP Server.', array(1 => CRM_Utils_System::url('civicrm/admin/setting/smtp', 'reset=1'))));
        CRM_Core_Error::fatal(ts('There is no valid smtp server setting. Click <a href=\'%1\'>Administer >> System Setting >> Outbound Email</a> to set the SMTP Server.', array(1 => CRM_Utils_System::url('civicrm/admin/setting/smtp', 'reset=1'))));
      }

      $params['host'] = $mailingInfo['smtpServer'] ? $mailingInfo['smtpServer'] : 'localhost';
      $params['port'] = $mailingInfo['smtpPort'] ? $mailingInfo['smtpPort'] : 25;

      if ($mailingInfo['smtpAuth']) {
        $params['username'] = $mailingInfo['smtpUsername'];
        $params['password'] = CRM_Utils_Crypt::decrypt($mailingInfo['smtpPassword']);
        $params['auth'] = TRUE;
      }
      else {
        $params['auth'] = FALSE;
      }

      // set the localhost value, CRM-3153
      $params['localhost'] = CRM_Utils_Array::value('SERVER_NAME', $_SERVER, 'localhost');

      // also set the timeout value, lets set it to 30 seconds
      // CRM-7510
      $params['timeout'] = 30;

      // CRM-9349
      $params['persist'] = TRUE;

      $mailer = self::_createMailer('smtp', $params);
    }
    elseif ($mailingInfo['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_SENDMAIL) {
      if ($mailingInfo['sendmail_path'] == '' ||
        !$mailingInfo['sendmail_path']
      ) {
        CRM_Core_Error::debug_log_message(ts('There is no valid sendmail path setting. Click <a href=\'%1\'>Administer >> System Setting >> Outbound Email</a> to set the sendmail server.', array(1 => CRM_Utils_System::url('civicrm/admin/setting/smtp', 'reset=1'))));
        CRM_Core_Error::fatal(ts('There is no valid sendmail path setting. Click <a href=\'%1\'>Administer >> System Setting >> Outbound Email</a> to set the sendmail server.', array(1 => CRM_Utils_System::url('civicrm/admin/setting/smtp', 'reset=1'))));
      }
      $params['sendmail_path'] = $mailingInfo['sendmail_path'];
      $params['sendmail_args'] = $mailingInfo['sendmail_args'];

      $mailer = self::_createMailer('sendmail', $params);
    }
    elseif ($mailingInfo['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_MAIL) {
      $mailer = self::_createMailer('mail', array());
    }
    elseif ($mailingInfo['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_MOCK) {
      $mailer = self::_createMailer('mock', array());
    }
    elseif ($mailingInfo['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_DISABLED) {
      CRM_Core_Error::debug_log_message(ts('Outbound mail has been disabled. Click <a href=\'%1\'>Administer >> System Setting >> Outbound Email</a> to set the OutBound Email.', array(1 => CRM_Utils_System::url('civicrm/admin/setting/smtp', 'reset=1'))));
      CRM_Core_Session::setStatus(ts('Outbound mail has been disabled. Click <a href=\'%1\'>Administer >> System Setting >> Outbound Email</a> to set the OutBound Email.', array(1 => CRM_Utils_System::url('civicrm/admin/setting/smtp', 'reset=1'))));
    }
    else {
      CRM_Core_Error::debug_log_message(ts('There is no valid SMTP server Setting Or SendMail path setting. Click <a href=\'%1\'>Administer >> System Setting >> Outbound Email</a> to set the OutBound Email.', array(1 => CRM_Utils_System::url('civicrm/admin/setting/smtp', 'reset=1'))));
      CRM_Core_Session::setStatus(ts('There is no valid SMTP server Setting Or sendMail path setting. Click <a href=\'%1\'>Administer >> System Setting >> Outbound Email</a> to set the OutBound Email.', array(1 => CRM_Utils_System::url('civicrm/admin/setting/smtp', 'reset=1'))));
      CRM_Core_Error::debug_var('mailing_info', $mailingInfo);
    }
    return $mailer;
  }

  /**
   * Create a new instance of a PEAR Mail driver.
   *
   * @param string $driver
   *   'CRM_Mailing_BAO_Spool' or a name suitable for Mail::factory().
   * @param array $params
   * @return object
   *   More specifically, a class which implements the "send()" function
   */
  public static function _createMailer($driver, $params) {
    if ($driver == 'CRM_Mailing_BAO_Spool') {
      $mailer = new CRM_Mailing_BAO_Spool($params);
    }
    else {
      $mailer = Mail::factory($driver, $params);
    }
    CRM_Utils_Hook::alterMailer($mailer, $driver, $params);
    return $mailer;
  }

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
   * @param array $params
   *   (by reference).
   *
   * @return bool
   *   TRUE if a mail was sent, else FALSE.
   */
  public static function send(&$params) {
    $defaultReturnPath = CRM_Core_BAO_MailSettings::defaultReturnPath();
    $includeMessageId = CRM_Core_BAO_MailSettings::includeMessageId();
    $emailDomain = CRM_Core_BAO_MailSettings::defaultDomain();
    $from = CRM_Utils_Array::value('from', $params);
    if (!$defaultReturnPath) {
      $defaultReturnPath = self::pluckEmailFromHeader($from);
    }

    // first call the mail alter hook
    CRM_Utils_Hook::alterMailParams($params, 'singleEmail');

    // check if any module has aborted mail sending
    if (!empty($params['abortMailSend']) || empty($params['toEmail'])) {
      return FALSE;
    }

    $textMessage = CRM_Utils_Array::value('text', $params);
    $htmlMessage = CRM_Utils_Array::value('html', $params);
    $attachments = CRM_Utils_Array::value('attachments', $params);

    // CRM-6224
    if (trim(CRM_Utils_String::htmlToText($htmlMessage)) == '') {
      $htmlMessage = FALSE;
    }

    $headers = array();
    // CRM-10699 support custom email headers
    if (!empty($params['headers'])) {
      $headers = array_merge($headers, $params['headers']);
    }
    $headers['From'] = $params['from'];
    $headers['To'] = self::formatRFC822Email(
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
    $headers['Return-Path'] = CRM_Utils_Array::value('returnPath', $params, $defaultReturnPath);

    // CRM-11295: Omit reply-to headers if empty; this avoids issues with overzealous mailservers
    $replyTo = CRM_Utils_Array::value('replyTo', $params, CRM_Utils_Array::value('from', $params));

    if (!empty($replyTo)) {
      $headers['Reply-To'] = $replyTo;
    }
    $headers['Date'] = date('r');
    if ($includeMessageId) {
      $headers['Message-ID'] = '<' . uniqid('civicrm_', TRUE) . "@$emailDomain>";
    }
    if (!empty($params['autoSubmitted'])) {
      $headers['Auto-Submitted'] = "Auto-Generated";
    }

    // make sure we has to have space, CRM-6977
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
    $result = NULL;
    $mailer = \Civi::service('pear_mail');

    // Mail_smtp and Mail_sendmail mailers require Bcc anc Cc emails
    // be included in both $to and $headers['Cc', 'Bcc']
    if (get_class($mailer) != "Mail_mail") {
      // get emails from headers, since these are
      // combination of name and email addresses.
      if (!empty($headers['Cc'])) {
        $to[] = CRM_Utils_Array::value('Cc', $headers);
      }
      if (!empty($headers['Bcc'])) {
        $to[] = CRM_Utils_Array::value('Bcc', $headers);
        unset($headers['Bcc']);
      }
    }
    if (is_object($mailer)) {
      $errorScope = CRM_Core_TemporaryErrorScope::ignoreException();
      $result = $mailer->send($to, $headers, $message);
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

  /**
   * @param $mailer
   * @param $result
   *
   * @return string
   */
  public static function errorMessage($mailer, $result) {
    $message = '<p>' . ts('An error occurred when CiviCRM attempted to send an email (via %1). If you received this error after submitting on online contribution or event registration - the transaction was completed, but we were unable to send the email receipt.', array(
        1 => 'SMTP',
      )) . '</p>' . '<p>' . ts('The mail library returned the following error message:') . '<br /><span class="font-red"><strong>' . $result->getMessage() . '</strong></span></p>' . '<p>' . ts('This is probably related to a problem in your Outbound Email Settings (Administer CiviCRM &raquo; System Settings &raquo; Outbound Email), OR the FROM email address specifically configured for your contribution page or event. Possible causes are:') . '</p>';

    if (is_a($mailer, 'Mail_smtp')) {
      $message .= '<ul>' . '<li>' . ts('Your SMTP Username or Password are incorrect.') . '</li>' . '<li>' . ts('Your SMTP Server (machine) name is incorrect.') . '</li>' . '<li>' . ts('You need to use a Port other than the default port 25 in your environment.') . '</li>' . '<li>' . ts('Your SMTP server is just not responding right now (it is down for some reason).') . '</li>';
    }
    else {
      $message .= '<ul>' . '<li>' . ts('Your Sendmail path is incorrect.') . '</li>' . '<li>' . ts('Your Sendmail argument is incorrect.') . '</li>';
    }

    $message .= '<li>' . ts('The FROM Email Address configured for this feature may not be a valid sender based on your email service provider rules.') . '</li>' . '</ul>' . '<p>' . ts('Check <a href="%1">this page</a> for more information.', array(
        1 => CRM_Utils_System::docURL2('user/advanced-configuration/email-system-configuration', TRUE),
      )) . '</p>';

    return $message;
  }

  /**
   * @param $to
   * @param $headers
   * @param $message
   */
  public static function logger(&$to, &$headers, &$message) {
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
   * @param string $header
   *   The full name + email address string.
   *
   * @return string
   *   the plucked email address
   */
  public static function pluckEmailFromHeader($header) {
    preg_match('/<([^<]*)>$/', $header, $matches);

    if (isset($matches[1])) {
      return $matches[1];
    }
    return NULL;
  }

  /**
   * Get the Active outBound email.
   *
   * @return bool
   *   TRUE if valid outBound email configuration found, false otherwise.
   */
  public static function validOutBoundMail() {
    $mailingInfo = Civi::settings()->get('mailing_backend');
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

  /**
   * @param $message
   * @param array $params
   *
   * @return mixed
   */
  public static function &setMimeParams(&$message, $params = NULL) {
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

  /**
   * @param string $name
   * @param $email
   * @param bool $useQuote
   *
   * @return null|string
   */
  public static function formatRFC822Email($name, $email, $useQuote = FALSE) {
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
   *
   * @param string $name
   *
   * @return string
   */
  public static function formatRFC2822Name($name) {
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

  /**
   * @param string $fileName
   * @param string $html
   * @param string $format
   *
   * @return array
   */
  public static function appendPDF($fileName, $html, $format = NULL) {
    $pdf_filename = CRM_Core_Config::singleton()->templateCompileDir . CRM_Utils_File::makeFileName($fileName);

    // FIXME : CRM-7894
    // xmlns attribute is required in XHTML but it is invalid in HTML,
    // Also the namespace "xmlns=http://www.w3.org/1999/xhtml" is default,
    // and will be added to the <html> tag even if you do not include it.
    $html = preg_replace('/(<html)(.+?xmlns=["\'].[^\s]+["\'])(.+)?(>)/', '\1\3\4', $html);

    file_put_contents($pdf_filename, CRM_Utils_PDF_Utils::html2pdf($html,
        $fileName,
        TRUE,
        $format)
    );
    return array(
      'fullPath' => $pdf_filename,
      'mime_type' => 'application/pdf',
      'cleanName' => $fileName,
    );
  }

}
