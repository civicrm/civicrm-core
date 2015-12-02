<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
 * $Id: $
 *
 */
class CRM_Utils_VersionCheck {
  const
    PINGBACK_URL = 'http://latest.civicrm.org/stable.php?format=json',
    // timeout for when the connection or the server is slow
    CHECK_TIMEOUT = 1,
    // relative to $civicrm_root
    LOCALFILE_NAME = 'civicrm-version.php',
    // relative to $config->uploadDir
    CACHEFILE_NAME = 'version-info-cache.json',
    // cachefile expiry time (in seconds) - one day
    CACHEFILE_EXPIRE = 86400;

  /**
   * We only need one instance of this object, so we use the
   * singleton pattern and cache the instance in this variable
   *
   * @var object
   */
  static private $_singleton = NULL;

  /**
   * The version of the current (local) installation
   *
   * @var string
   */
  public $localVersion = NULL;

  /**
   * The major version (branch name) of the local version
   *
   * @var string
   */
  public $localMajorVersion;

  /**
   * User setting to skip updates prior to a certain date
   *
   * @var string
   */
  public $ignoreDate;

  /**
   * Info about available versions
   *
   * @var array
   */
  public $versionInfo = array();

  /**
   * Pingback params
   *
   * @var array
   */
  protected $stats = array();

  /**
   * Path to cache file
   *
   * @var string
   */
  protected $cacheFile;

  /**
   * Class constructor.
   */
  public function __construct() {
    global $civicrm_root;
    $config = CRM_Core_Config::singleton();

    $localFile = $civicrm_root . DIRECTORY_SEPARATOR . self::LOCALFILE_NAME;
    $this->cacheFile = $config->uploadDir . self::CACHEFILE_NAME;

    if (file_exists($localFile)) {
      require_once $localFile;
    }
    if (function_exists('civicrmVersion')) {
      $info = civicrmVersion();
      $this->localVersion = trim($info['version']);
      $this->localMajorVersion = $this->getMajorVersion($this->localVersion);
    }
    // Populate $versionInfo
    if (CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'versionCheck', NULL, 1)) {
      // Use cached data if available and not stale
      if (!$this->readCacheFile()) {
        // Collect stats for pingback
        $this->getSiteStats();

        // Get the latest version and send site info
        $this->pingBack();
      }
      $this->ignoreDate = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'versionCheckIgnoreDate');

      // Sort version info in ascending order for easier comparisons
      ksort($this->versionInfo, SORT_NUMERIC);
    }
  }

  /**
   * Static instance provider.
   *
   * Method providing static instance of CRM_Utils_VersionCheck,
   * as in Singleton pattern
   *
   * @return CRM_Utils_VersionCheck
   */
  public static function &singleton() {
    if (!isset(self::$_singleton)) {
      self::$_singleton = new CRM_Utils_VersionCheck();
    }
    return self::$_singleton;
  }

  /**
   * Finds the release info for a minor version.
   * @param string $version
   * @return array|null
   */
  public function getReleaseInfo($version) {
    $majorVersion = $this->getMajorVersion($version);
    if (isset($this->versionInfo[$majorVersion])) {
      foreach ($this->versionInfo[$majorVersion]['releases'] as $info) {
        if ($info['version'] == $version) {
          return $info;
        }
      }
    }
    return NULL;
  }

  /**
   * @param $minorVersion
   * @return string
   */
  public function getMajorVersion($minorVersion) {
    if (!$minorVersion) {
      return NULL;
    }
    list($a, $b) = explode('.', $minorVersion);
    return "$a.$b";
  }

  /**
   * @return bool
   */
  public function isSecurityUpdateAvailable() {
    $thisVersion = $this->getReleaseInfo($this->localVersion);
    $localVersionDate = CRM_Utils_Array::value('date', $thisVersion, 0);
    foreach ($this->versionInfo as $majorVersion) {
      foreach ($majorVersion['releases'] as $release) {
        if (!empty($release['security']) && $release['date'] > $localVersionDate
          && version_compare($this->localVersion, $release['version']) < 0
        ) {
          if (!$this->ignoreDate || $this->ignoreDate < $release['date']) {
            return TRUE;
          }
        }
      }
    }
  }

  /**
   * Get the latest version number if it's newer than the local one
   *
   * @return string|null
   *   Returns version number of the latest release if it is greater than the local version
   */
  public function isNewerVersionAvailable() {
    $newerVersion = NULL;
    if ($this->versionInfo && $this->localVersion) {
      foreach ($this->versionInfo as $majorVersionNumber => $majorVersion) {
        $release = $this->checkBranchForNewVersion($majorVersion);
        if ($release) {
          // If we have a release with the same majorVersion as local, return it
          if ($majorVersionNumber == $this->localMajorVersion) {
            return $release;
          }
          // Search outside the local majorVersion (excluding non-stable)
          elseif ($majorVersion['status'] != 'testing') {
            // We found a new release but don't return yet, keep searching newer majorVersions
            $newerVersion = $release;
          }
        }
      }
    }
    return $newerVersion;
  }

  /**
   * @param $majorVersion
   * @return null|string
   */
  private function checkBranchForNewVersion($majorVersion) {
    $newerVersion = NULL;
    if (!empty($majorVersion['releases'])) {
      foreach ($majorVersion['releases'] as $release) {
        if (version_compare($this->localVersion, $release['version']) < 0) {
          if (!$this->ignoreDate || $this->ignoreDate < $release['date']) {
            $newerVersion = $release['version'];
          }
        }
      }
    }
    return $newerVersion;
  }

  /**
   * Alert the site admin of new versions of CiviCRM.
   * Show the message once a day
   */
  public function versionAlert() {
    $versionAlertSetting = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'versionAlert', NULL, 1);
    $securityAlertSetting = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'securityUpdateAlert', NULL, 3);
    $settingsUrl = CRM_Utils_System::url('civicrm/admin/setting/misc', 'reset=1', FALSE, NULL, FALSE, FALSE, TRUE);
    if (CRM_Core_Permission::check('administer CiviCRM') && $securityAlertSetting > 1 && $this->isSecurityUpdateAvailable()) {
      $session = CRM_Core_Session::singleton();
      if ($session->timer('version_alert', 24 * 60 * 60)) {
        $msg = ts('This version of CiviCRM requires a security update.') .
          '<ul>
            <li><a href="https://civicrm.org/advisory">' . ts('Read advisory') . '</a></li>
            <li><a href="https://civicrm.org/download">' . ts('Download now') . '</a></li>
            <li><a class="crm-setVersionCheckIgnoreDate" href="' . $settingsUrl . '">' . ts('Suppress this message') . '</a></li>
          </ul>';
        $session->setStatus($msg, ts('Security Alert'), 'alert');
        CRM_Core_Resources::singleton()
          ->addScriptFile('civicrm', 'templates/CRM/Admin/Form/Setting/versionCheckOptions.js');
      }
    }
    elseif (CRM_Core_Permission::check('administer CiviCRM') && $versionAlertSetting > 1) {
      $newerVersion = $this->isNewerVersionAvailable();
      if ($newerVersion) {
        $session = CRM_Core_Session::singleton();
        if ($session->timer('version_alert', 24 * 60 * 60)) {
          $msg = ts('A newer version of CiviCRM is available: %1', array(1 => $newerVersion)) .
            '<ul>
              <li><a href="https://civicrm.org/download">' . ts('Download now') . '</a></li>
              <li><a class="crm-setVersionCheckIgnoreDate" href="' . $settingsUrl . '">' . ts('Suppress this message') . '</a></li>
            </ul>';
          $session->setStatus($msg, ts('Update Available'), 'info');
          CRM_Core_Resources::singleton()
            ->addScriptFile('civicrm', 'templates/CRM/Admin/Form/Setting/versionCheckOptions.js');
        }
      }
    }
  }

  /**
   * Collect info about the site to be sent as pingback data.
   */
  private function getSiteStats() {
    $config = CRM_Core_Config::singleton();
    $siteKey = md5(defined('CIVICRM_SITE_KEY') ? CIVICRM_SITE_KEY : '');

    // Calorie-free pingback for alphas
    $this->stats = array('version' => $this->localVersion);

    // Non-alpha versions get the full treatment
    if ($this->localVersion && !strpos($this->localVersion, 'alpha')) {
      $this->stats += array(
        'hash' => md5($siteKey . $config->userFrameworkBaseURL),
        'uf' => $config->userFramework,
        'lang' => $config->lcMessages,
        'co' => $config->defaultContactCountry,
        'ufv' => $config->userFrameworkVersion,
        'PHP' => phpversion(),
        'MySQL' => CRM_CORE_DAO::singleValueQuery('SELECT VERSION()'),
        'communityMessagesUrl' => CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'communityMessagesUrl', NULL, '*default*'),
      );
      $this->getPayProcStats();
      $this->getEntityStats();
      $this->getExtensionStats();
    }
  }

  /**
   * Get active payment processor types.
   */
  private function getPayProcStats() {
    $dao = new CRM_Financial_DAO_PaymentProcessor();
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
   * Fetch counts from entity tables.
   * Add info to the 'entities' array
   */
  private function getEntityStats() {
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
      'CRM_Price_DAO_PriceSetEntity' => NULL,
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
      $dao = new $daoName();
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
  private function getExtensionStats() {
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
   * Store results in the cache file
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
    $rawJson = @file_get_contents(self::PINGBACK_URL, FALSE, $ctx);
    $versionInfo = $rawJson ? json_decode($rawJson, TRUE) : NULL;
    // If we couldn't fetch or parse the data $versionInfo will be NULL
    // Otherwise it will be an array and we'll cache it.
    // Note the array may be empty e.g. in the case of a pre-alpha with no releases
    if ($versionInfo !== NULL) {
      $this->writeCacheFile($rawJson);
      $this->versionInfo = $versionInfo;
    }
    ini_restore('default_socket_timeout');
  }

  /**
   * @return bool
   */
  private function readCacheFile() {
    $expiryTime = time() - self::CACHEFILE_EXPIRE;

    // if there's a cachefile and it's not stale, use it
    if (file_exists($this->cacheFile) && (filemtime($this->cacheFile) > $expiryTime)) {
      $this->versionInfo = (array) json_decode(file_get_contents($this->cacheFile), TRUE);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Save version info to file.
   * @param string $contents
   */
  private function writeCacheFile($contents) {
    $fp = @fopen($this->cacheFile, 'w');
    if (!$fp) {
      if (CRM_Core_Permission::check('administer CiviCRM')) {
        CRM_Core_Session::setStatus(
          ts('Unable to write file') . ": $this->cacheFile<br />" . ts('Please check your system file permissions.'),
          ts('File Error'), 'error');
      }
      return;
    }
    fwrite($fp, $contents);
    fclose($fp);
  }

}
