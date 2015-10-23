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
 */
class CRM_Core_IDS {

  /**
   * Define the threshold for the ids reactions.
   */
  private $threshold = array(
    'log' => 25,
    'warn' => 50,
    'kick' => 75,
  );

  /**
   * The init object
   */
  private $init = NULL;

  /**
   * Check function.
   *
   * This function includes the IDS vendor parts and runs the
   * detection routines on the request array.
   *
   * @param object $args cake controller object
   *
   * @return bool
   */
  public function check(&$args) {
    // lets bypass a few civicrm urls from this check
    static $skip = array('civicrm/admin/setting/updateConfigBackend', 'civicrm/admin/messageTemplates');
    $path = implode('/', $args);
    if (in_array($path, $skip)) {
      return NULL;
    }

    // Add request url and user agent.
    $_REQUEST['IDS_request_uri'] = $_SERVER['REQUEST_URI'];
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
      $_REQUEST['IDS_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    }

    $configFile = self::createConfigFile(FALSE);

    // init the PHPIDS and pass the REQUEST array
    require_once 'IDS/Init.php';
    try {
      $init = IDS_Init::init($configFile);
      $ids = new IDS_Monitor($_REQUEST, $init);
    }
    catch (Exception $e) {
      // might be an old stale copy of Config.IDS.ini
      // lets try to rebuild it again and see if it works
      $configFile = self::createConfigFile(TRUE);
      $init = IDS_Init::init($configFile);
      $ids = new IDS_Monitor($_REQUEST, $init);
    }

    $result = $ids->run();
    if (!$result->isEmpty()) {
      $this->react($result);
    }

    return TRUE;
  }

  /**
   * Create the default config file for the IDS system.
   *
   * @param bool $force
   *   Should we recreate it irrespective if it exists or not.
   *
   * @return string
   *   the full path to the config file
   */
  public static function createConfigFile($force = FALSE) {
    $config = CRM_Core_Config::singleton();
    $configFile = $config->configAndLogDir . 'Config.IDS.ini';
    if (!$force && file_exists($configFile)) {
      return $configFile;
    }

    $tmpDir = empty($config->uploadDir) ? CIVICRM_TEMPLATE_COMPILEDIR : $config->uploadDir;

    // also clear the stat cache in case we are upgrading
    clearstatcache();

    global $civicrm_root;
    $contents = "
[General]
    filter_type         = xml
    filter_path         = {$civicrm_root}/packages/IDS/default_filter.xml
    tmp_path            = $tmpDir
    HTML_Purifier_Path  = IDS/vendors/htmlpurifier/HTMLPurifier.auto.php
    HTML_Purifier_Cache = $tmpDir
    scan_keys           = false
    exceptions[]        = __utmz
    exceptions[]        = __utmc
    exceptions[]        = widget_code
    exceptions[]        = html_message
    exceptions[]        = text_message
    exceptions[]        = body_html
    exceptions[]        = msg_html
    exceptions[]        = msg_text
    exceptions[]        = msg_subject
    exceptions[]        = description
    exceptions[]        = intro
    exceptions[]        = thankyou_text
    exceptions[]        = intro_text
    exceptions[]        = body_text
    exceptions[]        = footer_text
    exceptions[]        = thankyou_text
    exceptions[]        = tf_thankyou_text
    exceptions[]        = thankyou_footer
    exceptions[]        = thankyou_footer_text
    exceptions[]        = new_text
    exceptions[]        = renewal_text
    exceptions[]        = help_pre
    exceptions[]        = help_post
    exceptions[]        = confirm_title
    exceptions[]        = confirm_text
    exceptions[]        = confirm_footer_text
    exceptions[]        = confirm_email_text
    exceptions[]        = report_header
    exceptions[]        = report_footer
    exceptions[]        = data
    exceptions[]        = json
    exceptions[]        = instructions
    exceptions[]        = suggested_message
    exceptions[]        = page_text
";
    if (file_put_contents($configFile, $contents) === FALSE) {
      CRM_Core_Error::movedSiteError($configFile);
    }

    // also create the .htaccess file so we prevent the reading of the log and ini files
    // via a browser, CRM-3875
    CRM_Utils_File::restrictAccess($config->configAndLogDir);

    return $configFile;
  }

  /**
   * This function reacts on the values in the incoming results array.
   *
   * Depending on the impact value certain actions are
   * performed.
   *
   * @param IDS_Report $result
   *
   * @return bool
   */
  private function react(IDS_Report $result) {

    $impact = $result->getImpact();
    if ($impact >= $this->threshold['kick']) {
      $this->log($result, 3, $impact);
      $this->kick($result);
      return TRUE;
    }
    elseif ($impact >= $this->threshold['warn']) {
      $this->log($result, 2, $impact);
      $this->warn($result);
      return TRUE;
    }
    elseif ($impact >= $this->threshold['log']) {
      $this->log($result, 0, $impact);
      return TRUE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * This function writes an entry about the intrusion to the database.
   *
   * @param array $result
   * @param int $reaction
   *
   * @return bool
   */
  private function log($result, $reaction = 0) {
    $ip = (isset($_SERVER['SERVER_ADDR']) &&
      $_SERVER['SERVER_ADDR'] != '127.0.0.1') ? $_SERVER['SERVER_ADDR'] : (
      isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '127.0.0.1'
      );

    $data = array();
    $session = CRM_Core_Session::singleton();
    foreach ($result as $event) {
      $data[] = array(
        'name' => $event->getName(),
        'value' => stripslashes($event->getValue()),
        'page' => $_SERVER['REQUEST_URI'],
        'userid' => $session->get('userID'),
        'session' => session_id() ? session_id() : '0',
        'ip' => $ip,
        'reaction' => $reaction,
        'impact' => $result->getImpact(),
      );
    }

    CRM_Core_Error::debug_var('IDS Detector Details', $data);
    return TRUE;
  }

  /**
   * Warn about IDS.
   *
   * @param array $result
   *
   * @return array
   */
  private function warn($result) {
    return $result;
  }

  /**
   * Kick (whatever that means!).
   *
   * @param array $result
   *
   * @throws \Exception
   */
  private function kick($result) {
    $session = CRM_Core_Session::singleton();
    $session->reset(2);

    $msg = ts('There is a validation error with your HTML input. Your activity is a bit suspicious, hence aborting');

    $path = implode('/', $args);
    if (in_array(
      $path,
      array("civicrm/ajax/rest", "civicrm/api/json")
    )) {
      require_once "api/v3/utils.php";
      $error = civicrm_api3_create_error(
        $msg,
        array(
          'IP' => $_SERVER['REMOTE_ADDR'],
          'error_code' => 'IDS_KICK',
          'level' => 'security',
          'referer' => $_SERVER['HTTP_REFERER'],
          'reason' => 'XSS suspected',
        )
      );
      CRM_Utils_JSON::output($error);
    }
    CRM_Core_Error::fatal($msg);
  }

}
