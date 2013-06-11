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
 * $Id: $
 *
 */
class CRM_Utils_VersionCheck {
  CONST
    LATEST_VERSION_AT = 'http://latest.civicrm.org/stable.php',
    // timeout for when the connection or the server is slow
    CHECK_TIMEOUT = 5,
    // relative to $civicrm_root
    LOCALFILE_NAME = 'civicrm-version.php',
    // relative to $config->uploadDir
    CACHEFILE_NAME = 'latest-version-cache.txt',
    // cachefile expiry time (in seconds) - one day
    CACHEFILE_EXPIRE = 86400;

  /**
   * We only need one instance of this object, so we use the
   * singleton pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  /**
   * The version of the current (local) installation
   *
   * @var string
   */
  public $localVersion = NULL;

  /**
   * The latest version of CiviCRM
   *
   * @var string
   */
  public $latestVersion = NULL;

  /**
   * Pingback params
   *
   * @var string
   */
  protected $stats = array();

  /**
   * Class constructor
   *
   * @access private
   */
  function __construct() {
    global $civicrm_root;
    $config = CRM_Core_Config::singleton();

    $localfile = $civicrm_root . DIRECTORY_SEPARATOR . self::LOCALFILE_NAME;
    $cachefile = $config->uploadDir . self::CACHEFILE_NAME;

    if (file_exists($localfile)) {
      require_once ($localfile);
      if (function_exists('civicrmVersion')) {
        $info = civicrmVersion();
        $this->localVersion = trim($info['version']);
      }
    }
    if ($config->versionCheck) {
      $expiryTime = time() - self::CACHEFILE_EXPIRE;

      // if there's a cachefile and it's not stale use it to
      // read the latestVersion, else read it from the Internet
      if (file_exists($cachefile) && (filemtime($cachefile) > $expiryTime)) {
        $this->latestVersion = trim(file_get_contents($cachefile));
      }
      else {
        $siteKey = md5(defined('CIVICRM_SITE_KEY') ? CIVICRM_SITE_KEY : '');

        $this->stats = array(
          'hash' => md5($siteKey . $config->userFrameworkBaseURL),
          'version' => $this->localVersion,
          'uf' => $config->userFramework,
          'lang' => $config->lcMessages,
          'co' => $config->defaultContactCountry,
          'ufv' => $config->userFrameworkVersion,
          'PHP' => phpversion(),
          'MySQL' => CRM_CORE_DAO::singleValueQuery('SELECT VERSION()'),
          'communityMessagesUrl' => CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'communityMessagesUrl', NULL, '*default*'),
        );

        // Add usage stats
        $this->payProcStats();
        $this->entityStats();
        $this->extensionStats();

        // Get the latest version and send site info
        $this->pingBack();

        // Update cache file
        if ($this->latestVersion) {
          $fp = @fopen($cachefile, 'w');
          if (!$fp) {
            if (CRM_Core_Permission::check('administer CiviCRM')) {
              CRM_Core_Session::setStatus(
                ts('Unable to write file') . ":$cachefile<br />" . ts('Please check your system file permissions.'),
                ts('File Error'), 'error');
            }
            return;
          }
          fwrite($fp, $this->latestVersion);
          fclose($fp);
        }
      }
    }
  }

  /**
   * Static instance provider
   *
   * Method providing static instance of CRM_Utils_VersionCheck,
   * as in Singleton pattern
   *
   * @return CRM_Utils_VersionCheck
   */
  static function &singleton() {
    if (!isset(self::$_singleton)) {
      self::$_singleton = new CRM_Utils_VersionCheck();
    }
    return self::$_singleton;
  }

  /**
   * Get the latest version number if it's newer than the local one
   *
   * @return string|null
   * Returns the newer version's number, or null if the versions are equal
   */
  public function newerVersion() {
    if ($this->latestVersion) {
      if (version_compare($this->localVersion, $this->latestVersion) < 0) {
        return $this->latestVersion;
      }
    }
    return NULL;
  }

  /**
   * Alert the site admin of new versions of CiviCRM
   * Show the message once a day
   */
  public function versionAlert() {
    if (CRM_Core_Permission::check('administer CiviCRM') && $this->newerVersion()
    && CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'versionAlert', NULL, TRUE)) {
      $session = CRM_Core_Session::singleton();
      if ($session->timer('version_alert', 24 * 60 * 60)) {
        $msg = ts('A newer version of CiviCRM is available: %1', array(1 => $this->latestVersion))
        . '<br />' . ts('<a href="%1">Download Now</a>', array(1 => 'http://civicrm.org/download'));
        $session->setStatus($msg, ts('Update Available'));
      }
    }
  }

  /**
   * Get active payment processor types
   */
  private function payProcStats() {
    $dao = new CRM_Financial_DAO_PaymentProcessor;
    $dao->is_active = 1;
    $dao->find();
    $ppTypes = array();

    // Get title and id for all processor types
    $ppTypeNames = CRM_Core_PseudoConstant::paymentProcessorType();

    while ($dao->fetch()) {
      $ppTypes[] = $ppTypeNames[$dao->payment_processor_type_id];
    }
    // add the .-separated list of the processor types
    $this->stats['PPTypes'] = implode(',', array_unique($ppTypes));

  }

  /**
   * Fetch counts from entity tables
   * Add info to the 'entities' array
   */
  private function entityStats() {
    $tables = array(
      'CRM_Activity_DAO_Activity' => 'is_test = 0',
      'CRM_Case_DAO_Case' => 'is_deleted = 0',
      'CRM_Contact_DAO_Contact' => 'is_deleted = 0',
      'CRM_Contact_DAO_Relationship' => NULL,
      'CRM_Campaign_DAO_Campaign' => NULL,
      'CRM_Contribute_DAO_Contribution' => 'is_test = 0',
      'CRM_Contribute_DAO_ContributionPage' => 'is_active = 1',
      'CRM_Contribute_DAO_ContributionProduct' => NULL,
      'CRM_Contribute_DAO_Widget' => 'is_active = 1',
      'CRM_Core_DAO_Discount' => NULL,
      'CRM_Price_DAO_SetEntity' => NULL,
      'CRM_Core_DAO_UFGroup' => 'is_active = 1',
      'CRM_Event_DAO_Event' => 'is_active = 1',
      'CRM_Event_DAO_Participant' => 'is_test = 0',
      'CRM_Friend_DAO_Friend' => 'is_active = 1',
      'CRM_Grant_DAO_Grant' => NULL,
      'CRM_Mailing_DAO_Mailing' => 'is_completed = 1',
      'CRM_Member_DAO_Membership' => 'is_test = 0',
      'CRM_Member_DAO_MembershipBlock' => 'is_active = 1',
      'CRM_Pledge_DAO_Pledge' => 'is_test = 0',
      'CRM_Pledge_DAO_PledgeBlock' => NULL,
    );
    foreach ($tables as $daoName => $where) {
      $dao = new $daoName;
      if ($where) {
        $dao->whereAdd($where);
      }
      $short_name = substr($daoName, strrpos($daoName, '_') + 1);
      $this->stats['entities'][] = array(
        'name' => $short_name,
        'size' => $dao->count(),
      );
    }
  }

  /**
   * Fetch stats about enabled components/extensions
   * Add info to the 'extensions' array
   */
  private function extensionStats() {
    // Core components
    $config = CRM_Core_Config::singleton();
    foreach ($config->enableComponents as $comp) {
      $this->stats['extensions'][] = array(
        'name' => 'org.civicrm.component.' . strtolower($comp),
        'enabled' => 1,
        'version' => $this->stats['version'],
      );
    }
    // Contrib extensions
    $mapper = CRM_Extension_System::singleton()->getMapper();
    $dao = new CRM_Core_DAO_Extension();
    $dao->find();
    while ($dao->fetch()) {
      $info = $mapper->keyToInfo($dao->full_name);
      $this->stats['extensions'][] = array(
        'name' => $dao->full_name,
        'enabled' => $dao->is_active,
        'version' => isset($info->version) ? $info->version : NULL,
      );
    }
  }

  /**
   * Send the request to civicrm.org
   * Set timeout and suppress errors
   */
  private function pingBack() {
    ini_set('default_socket_timeout', self::CHECK_TIMEOUT);
    $params = array(
      'http' => array(
        'method' => 'POST',
        'header' => 'Content-type: application/x-www-form-urlencoded',
        'content' => http_build_query($this->stats),
      ),
    );
    $ctx = stream_context_create($params);
    $this->latestVersion = @file_get_contents(self::LATEST_VERSION_AT, FALSE, $ctx);
    if (!preg_match('/^\d+\.\d+\.\d+$/', $this->latestVersion)) {
      $this->latestVersion = NULL;
    }
    else {
      $this->latestVersion = trim($this->latestVersion);
    }
    ini_restore('default_socket_timeout');
  }

}
