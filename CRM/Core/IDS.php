<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * @copyright CiviCRM LLC (c) 2004-2017
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
   * @var string
   */
  private $path;

  /**
   * Check function.
   *
   * This function includes the IDS vendor parts and runs the
   * detection routines on the request array.
   *
   * @param array $args
   *   List of path parts.
   *
   * @return bool
   */
  public function check($args) {
    // lets bypass a few civicrm urls from this check
    $skip = array('civicrm/admin/setting/updateConfigBackend', 'civicrm/admin/messageTemplates');
    CRM_Utils_Hook::idsException($skip);
    $this->path = implode('/', $args);
    if (in_array($this->path, $skip)) {
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

    // also clear the stat cache in case we are upgrading
    clearstatcache();

    $config = self::createStandardConfig();
    $contents = "\n";
    $lineTpl = "    %-19s = %s\n";
    foreach ($config as $section => $fields) {
      $contents .= "[$section]\n";
      foreach ($fields as $key => $value) {
        if ($key === 'scan_keys' && $value == '') {
          $value = 'false';
        }

        if (is_array($value)) {
          foreach ($value as $v) {
            $contents .= sprintf($lineTpl, $key . '[]', $v);
          }
        }
        else {
          $contents .= sprintf($lineTpl, $key, $value);
        }
      }
    }

    if (file_put_contents($configFile, $contents) === FALSE) {
      CRM_Core_Error::movedSiteError($configFile);
    }

    // also create the .htaccess file so we prevent the reading of the log and ini files
    // via a browser, CRM-3875
    CRM_Utils_File::restrictAccess($config->configAndLogDir);

    return $configFile;
  }

  /**
   * Create conservative, minimalist IDS configuration.
   *
   * @return array
   */
  public static function createBaseConfig() {
    $config = \CRM_Core_Config::singleton();
    $tmpDir = empty($config->uploadDir) ? CIVICRM_TEMPLATE_COMPILEDIR : $config->uploadDir;
    global $civicrm_root;

    return array(
      'General' => array(
        'filter_type' => 'xml',
        'filter_path' => "{$civicrm_root}/packages/IDS/default_filter.xml",
        'tmp_path' => $tmpDir,
        'HTML_Purifier_Path' => 'IDS/vendors/htmlpurifier/HTMLPurifier.auto.php',
        'HTML_Purifier_Cache' => $tmpDir,
        'scan_keys' => '',
        'exceptions' => array('__utmz', '__utmc'),
      ),
    );
  }

  /**
   * Create the standard, general-purpose IDS configuration used by many pages.
   *
   * @return array
   */
  public static function createStandardConfig() {
    $excs = array(
      'widget_code',
      'html_message',
      'text_message',
      'body_html',
      'msg_html',
      'msg_text',
      'msg_subject',
      'description',
      'intro',
      'thankyou_text',
      'intro_text',
      'body_text',
      'footer_text',
      'thankyou_text',
      'tf_thankyou_text',
      'thankyou_footer',
      'thankyou_footer_text',
      'new_text',
      'renewal_text',
      'help_pre',
      'help_post',
      'confirm_title',
      'confirm_text',
      'confirm_footer_text',
      'confirm_email_text',
      'report_header',
      'report_footer',
      'data',
      'json',
      'instructions',
      'suggested_message',
      'page_text',
      'details',
    );

    $result = self::createBaseConfig();

    $result['General']['exceptions'] = array_merge(
      $result['General']['exceptions'],
      $excs
    );

    return $result;
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
      $this->kick();
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
   * Create an error that prevents the user from continuing.
   *
   * @throws \Exception
   */
  private function kick() {
    $session = CRM_Core_Session::singleton();
    $session->reset(2);

    $msg = ts('There is a validation error with your HTML input. Your activity is a bit suspicious, hence aborting');

    if (in_array(
      $this->path,
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
