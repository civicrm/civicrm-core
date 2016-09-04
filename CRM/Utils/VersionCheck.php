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
class CRM_Utils_VersionCheck {
  const
    CACHEFILE_NAME = 'version-info-cache.json',
    // after this length of time we fall back on poor-man's cron (7+ days)
    CACHEFILE_EXPIRE = 605000;

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
   * Info about available versions
   *
   * @var array
   */
  public $versionInfo = array();

  /**
   * @var bool
   */
  public $isInfoAvailable;

  /**
   * @var array
   */
  public $cronJob = array();

  /**
   * @var string
   */
  public $pingbackUrl = 'http://latest.civicrm.org/stable.php?format=json';

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
  public $cacheFile;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->localVersion = CRM_Utils_System::version();
    $this->localMajorVersion = $this->getMajorVersion($this->localVersion);
    $this->cacheFile = CRM_Core_Config::singleton()->uploadDir . self::CACHEFILE_NAME;
  }

  /**
   * Self-populates version info
   *
   * @throws \Exception
   */
  public function initialize() {
    $this->getJob();

    // Populate remote $versionInfo from cache file
    $this->isInfoAvailable = $this->readCacheFile();

    // Poor-man's cron fallback if scheduled job is enabled but has failed to run
    $expiryTime = time() - self::CACHEFILE_EXPIRE;
    if (!empty($this->cronJob['is_active']) &&
      (!$this->isInfoAvailable || filemtime($this->cacheFile) < $expiryTime)
    ) {
      // First try updating the files modification time, for 2 reasons:
      //  - if the file is not writeable, this saves the trouble of pinging back
      //  - if the remote server is down, this will prevent an immediate retry
      if (touch($this->cacheFile) === FALSE) {
        throw new Exception('File not writable');
      }
      $this->fetch();
    }
  }

  /**
   * Sets $versionInfo
   *
   * @param $info
   */
  public function setVersionInfo($info) {
    $this->versionInfo = (array) $info;
    // Sort version info in ascending order for easier comparisons
    ksort($this->versionInfo, SORT_NUMERIC);
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
   * Get the latest version number if it's newer than the local one
   *
   * @return array
   *   Returns version number of the latest release if it is greater than the local version,
   *   along with the type of upgrade (regular/security) needed and the status of the major
   *   version
   */
  public function isNewerVersionAvailable() {
    $return = array(
      'version' => NULL,
      'upgrade' => NULL,
      'status' => NULL,
    );

    if ($this->versionInfo && $this->localVersion) {
      if (isset($this->versionInfo[$this->localMajorVersion])) {
        switch (CRM_Utils_Array::value('status', $this->versionInfo[$this->localMajorVersion])) {
          case 'stable':
          case 'lts':
          case 'testing':
            // look for latest version in this major version
            $releases = $this->checkBranchForNewVersion($this->versionInfo[$this->localMajorVersion]);
            if ($releases['newest']) {
              $return['version'] = $releases['newest'];

              // check for intervening security releases
              $return['upgrade'] = ($releases['security']) ? 'security' : 'regular';
            }
            break;

          case 'eol':
          default:
            // look for latest version ever
            foreach ($this->versionInfo as $majorVersionNumber => $majorVersion) {
              if ($majorVersionNumber < $this->localMajorVersion || $majorVersion['status'] == 'testing') {
                continue;
              }
              $releases = $this->checkBranchForNewVersion($this->versionInfo[$majorVersionNumber]);

              if ($releases['newest']) {
                $return['version'] = $releases['newest'];

                // check for intervening security releases
                $return['upgrade'] = ($releases['security'] || $return['upgrade'] == 'security') ? 'security' : 'regular';
              }
            }
        }
        $return['status'] = $this->versionInfo[$this->localMajorVersion]['status'];
      }
      else {
        // Figure if the version is really old or really new
        $wayOld = TRUE;

        foreach ($this->versionInfo as $majorVersionNumber => $majorVersion) {
          $wayOld = ($this->localMajorVersion < $majorVersionNumber);
        }

        if ($wayOld) {
          $releases = $this->checkBranchForNewVersion($majorVersion);

          $return = array(
            'version' => $releases['newest'],
            'upgrade' => 'security',
            'status' => 'eol',
          );
        }
      }
    }

    return $return;
  }

  /**
   * Called by version_check cron job
   */
  public function fetch() {
    $this->getSiteStats();
    $this->pingBack();
  }

  /**
   * @param $majorVersion
   * @return null|string
   */
  private function checkBranchForNewVersion($majorVersion) {
    $newerVersion = array(
      'newest' => NULL,
      'security' => NULL,
    );
    if (!empty($majorVersion['releases'])) {
      foreach ($majorVersion['releases'] as $release) {
        if (version_compare($this->localVersion, $release['version']) < 0) {
          $newerVersion['newest'] = $release['version'];
          if (CRM_Utils_Array::value('security', $release)) {
            $newerVersion['security'] = $release['version'];
          }
        }
      }
    }
    return $newerVersion;
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
        'ufv' => $config->userSystem->getVersion(),
        'PHP' => phpversion(),
        'MySQL' => CRM_CORE_DAO::singleValueQuery('SELECT VERSION()'),
        'communityMessagesUrl' => Civi::settings()->get('communityMessagesUrl'),
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
   * Store results in the cache file
   */
  private function pingBack() {
    $params = array(
      'http' => array(
        'method' => 'POST',
        'header' => 'Content-type: application/x-www-form-urlencoded',
        'content' => http_build_query($this->stats),
      ),
    );
    $ctx = stream_context_create($params);
    $rawJson = file_get_contents($this->pingbackUrl, FALSE, $ctx);
    $versionInfo = $rawJson ? json_decode($rawJson, TRUE) : NULL;
    // If we couldn't fetch or parse the data $versionInfo will be NULL
    // Otherwise it will be an array and we'll cache it.
    // Note the array may be empty e.g. in the case of a pre-alpha with no releases
    $this->isInfoAvailable = $versionInfo !== NULL;
    if ($this->isInfoAvailable) {
      $this->writeCacheFile($rawJson);
      $this->setVersionInfo($versionInfo);
    }
  }

  /**
   * @return bool
   */
  private function readCacheFile() {
    if (file_exists($this->cacheFile)) {
      $this->setVersionInfo(json_decode(file_get_contents($this->cacheFile), TRUE));
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Save version info to file.
   * @param string $contents
   * @throws \Exception
   */
  private function writeCacheFile($contents) {
    if (file_put_contents($this->cacheFile, $contents) === FALSE) {
      throw new Exception('File not writable');
    }
  }

  /**
   * Lookup version_check scheduled job
   */
  private function getJob() {
    $jobs = civicrm_api3('Job', 'get', array(
      'sequential' => 1,
      'api_action' => "version_check",
      'api_entity' => "job",
    ));
    $this->cronJob = CRM_Utils_Array::value(0, $jobs['values'], array());
  }

}
