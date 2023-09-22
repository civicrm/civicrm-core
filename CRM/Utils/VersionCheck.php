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
class CRM_Utils_VersionCheck {
  const
    CACHEFILE_NAME = 'version-msgs-cache.json',
    // After which length of time we expire the cached version info (3 days).
    CACHEFILE_EXPIRE = 259200;

  /**
   * The version of the current (local) installation
   *
   * @var string
   */
  public $localVersion = NULL;

  /**
   * Info about available versions
   *
   * @var array
   */
  public $versionInfo = [];

  /**
   * @var bool
   */
  public $isInfoAvailable;

  /**
   * @var array
   */
  public $cronJob = [];

  /**
   * @var string
   */
  public $pingbackUrl = 'https://latest.civicrm.org/stable.php?format=summary';

  /**
   * Pingback params
   *
   * @var array
   */
  protected $stats = [];

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
    $this->cacheFile = CRM_Core_Config::singleton()->uploadDir . self::CACHEFILE_NAME;
  }

  /**
   * Self-populates version info
   *
   * @param bool $force
   * @throws Exception
   */
  public function initialize($force = FALSE) {
    $this->getJob();

    // Populate remote $versionInfo from cache file
    $this->isInfoAvailable = $this->readCacheFile();

    // Fallback if scheduled job is enabled but has failed to run.
    $expiryTime = time() - self::CACHEFILE_EXPIRE;
    if ($force || (!empty($this->cronJob['is_active']) &&
      (!$this->isInfoAvailable || filemtime($this->cacheFile) < $expiryTime)
    )) {
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
  protected function setVersionInfo($info) {
    $this->versionInfo = $info;
  }

  /**
   * @return array|NULL
   *   message: string
   *   title: string
   *   severity: string
   *     Ex: 'info', 'notice', 'warning', 'critical'.
   */
  public function getVersionMessages() {
    return $this->isInfoAvailable ? $this->versionInfo : NULL;
  }

  /**
   * Called by version_check cron job
   */
  public function fetch() {
    $this->getSiteStats();
    $this->pingBack();
  }

  /**
   * Collect info about the site to be sent as pingback data.
   */
  private function getSiteStats() {
    $config = CRM_Core_Config::singleton();
    $siteKey = md5(defined('CIVICRM_SITE_KEY') ? CIVICRM_SITE_KEY : '');

    // Calorie-free pingback for alphas
    $this->stats = ['version' => $this->localVersion];

    // Non-alpha versions get the full treatment
    if ($this->localVersion && !strpos($this->localVersion, 'alpha')) {
      $this->stats += [
        // Remove the hash after 2024-09-01 to allow the transition to sid
        'hash' => md5($siteKey . $config->userFrameworkBaseURL),
        'sid' => Civi::settings()->get('site_id'),
        'uf' => $config->userFramework,
        'environment' => CRM_Core_Config::environment(),
        'lang' => $config->lcMessages,
        'co' => $config->defaultContactCountry,
        'ufv' => $config->userSystem->getVersion(),
        'PHP' => phpversion(),
        'MySQL' => CRM_Core_DAO::singleValueQuery('SELECT VERSION()'),
        'communityMessagesUrl' => Civi::settings()->get('communityMessagesUrl'),
      ];
      $this->getDomainStats();
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
    $ppTypes = [];

    // Get title for all processor types
    // FIXME: This should probably be getName, but it has always returned translated label so we stick with that for now as it would affect stats
    while ($dao->fetch()) {
      $ppTypes[] = CRM_Core_PseudoConstant::getLabel('CRM_Financial_BAO_PaymentProcessor', 'payment_processor_type_id', $dao->payment_processor_type_id);
    }
    // add the .-separated list of the processor types
    $this->stats['PPTypes'] = implode(',', array_unique($ppTypes));
  }

  /**
   * Fetch counts from entity tables.
   * Add info to the 'entities' array
   */
  private function getEntityStats() {
    // FIXME hardcoded list = bad
    $tables = [
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
      'CRM_Mailing_Event_DAO_MailingEventDelivered' => NULL,
    ];
    // Provide continuity in wire format.
    $compat = ['MailingEventDelivered' => 'Delivered'];
    foreach ($tables as $daoName => $where) {
      if (class_exists($daoName)) {
        /** @var \CRM_Core_DAO $dao */
        $dao = new $daoName();
        if ($where) {
          $dao->whereAdd($where);
        }
        $short_name = substr($daoName, strrpos($daoName, '_') + 1);
        $this->stats['entities'][] = [
          'name' => $compat[$short_name] ?? $short_name,
          'size' => $dao->count(),
        ];
      }
    }
  }

  /**
   * Fetch stats about enabled components/extensions
   * Add info to the 'extensions' array
   */
  private function getExtensionStats() {
    // Core components
    foreach (Civi::settings()->get('enable_components') as $comp) {
      $this->stats['extensions'][] = [
        'name' => 'org.civicrm.component.' . strtolower($comp),
        'enabled' => 1,
        'version' => $this->stats['version'],
      ];
    }
    // Contrib extensions
    $mapper = CRM_Extension_System::singleton()->getMapper();
    $dao = new CRM_Core_DAO_Extension();
    $dao->find();
    while ($dao->fetch()) {
      $info = $mapper->keyToInfo($dao->full_name);
      $this->stats['extensions'][] = [
        'name' => $dao->full_name,
        'enabled' => $dao->is_active,
        'version' => $info->version ?? NULL,
      ];
    }
  }

  /**
   * Fetch stats about domain and add to 'stats' array.
   */
  private function getDomainStats() {
    // Start with default value NULL, then check to see if there's a better
    // value to be had.
    $this->stats['domain_isoCode'] = NULL;
    $params = [
      'id' => CRM_Core_Config::domainID(),
    ];
    $domain_result = civicrm_api3('domain', 'getsingle', $params);
    if (!empty($domain_result['contact_id'])) {
      $address_params = [
        'contact_id' => $domain_result['contact_id'],
        'is_primary' => 1,
        'sequential' => 1,
      ];
      $address_result = civicrm_api3('address', 'get', $address_params);
      if ($address_result['count'] == 1 && !empty($address_result['values'][0]['country_id'])) {
        $country_params = [
          'id' => $address_result['values'][0]['country_id'],
        ];
        $country_result = civicrm_api3('country', 'getsingle', $country_params);
        if (!empty($country_result['iso_code'])) {
          $this->stats['domain_isoCode'] = $country_result['iso_code'];
        }
      }
    }
  }

  /**
   * Send the request to civicrm.org
   * Store results in the cache file
   */
  private function pingBack() {
    $params = [
      'http' => [
        'method' => 'POST',
        'header' => 'Content-type: application/x-www-form-urlencoded',
        'content' => http_build_query($this->stats),
      ],
    ];
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
   * Removes cached version info.
   */
  public function flushCache() {
    if (file_exists($this->cacheFile)) {
      unlink($this->cacheFile);
    }
  }

  /**
   * Lookup version_check scheduled job
   */
  private function getJob() {
    $jobs = civicrm_api3('Job', 'get', [
      'sequential' => 1,
      'api_action' => "version_check",
      'api_entity' => "job",
    ]);
    $this->cronJob = CRM_Utils_Array::value(0, $jobs['values'], []);
  }

}
