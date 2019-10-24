<?php

/**
 * Class CRM_Utils_versionCheckTest
 * @group headless
 */
class CRM_Utils_versionCheckTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * @var array
   */
  protected $sampleVersionInfo = [
    '4.2' => [
      'status' => 'eol',
      'releases' => [
        ['version' => '4.2.0', 'date' => '2012-08-20'],
        ['version' => '4.2.1', 'date' => '2012-09-12'],
        ['version' => '4.2.2', 'date' => '2012-09-27'],
        ['version' => '4.2.4', 'date' => '2012-10-18'],
        ['version' => '4.2.6', 'date' => '2012-11-01', 'security' => TRUE],
        ['version' => '4.2.7', 'date' => '2013-01-02', 'security' => TRUE],
        ['version' => '4.2.8', 'date' => '2013-02-20'],
        ['version' => '4.2.9', 'date' => '2013-04-03'],
        ['version' => '4.2.10', 'date' => '2013-07-29', 'security' => TRUE],
        ['version' => '4.2.11', 'date' => '2013-09-25'],
        ['version' => '4.2.12', 'date' => '2013-10-02', 'security' => TRUE],
        ['version' => '4.2.13', 'date' => '2013-11-06', 'security' => TRUE],
        ['version' => '4.2.14', 'date' => '2013-11-20'],
        ['version' => '4.2.15', 'date' => '2014-02-07', 'security' => TRUE],
        ['version' => '4.2.16', 'date' => '2014-02-18'],
        ['version' => '4.2.17', 'date' => '2014-07-01', 'security' => TRUE],
        ['version' => '4.2.18', 'date' => '2014-08-06'],
        ['version' => '4.2.19', 'date' => '2014-09-17', 'security' => TRUE],
      ],
    ],
    '4.3' => [
      'status' => 'lts',
      'releases' => [
        ['version' => '4.3.0', 'date' => '2013-04-10'],
        ['version' => '4.3.1', 'date' => '2013-04-18'],
        ['version' => '4.3.2', 'date' => '2013-05-02'],
        ['version' => '4.3.3', 'date' => '2013-05-08'],
        ['version' => '4.3.4', 'date' => '2013-06-10', 'security' => TRUE],
        ['version' => '4.3.5', 'date' => '2013-07-08', 'security' => TRUE],
        ['version' => '4.3.6', 'date' => '2013-09-25'],
        ['version' => '4.3.7', 'date' => '2013-10-02', 'security' => TRUE],
        ['version' => '4.3.8', 'date' => '2013-11-06', 'security' => TRUE],
        ['version' => '4.3.9', 'date' => '2014-09-07', 'security' => TRUE],
      ],
    ],
    '4.4' => [
      'status' => 'lts',
      'releases' => [
        ['version' => '4.4.0', 'date' => '2013-10-23'],
        ['version' => '4.4.1', 'date' => '2013-11-06', 'security' => TRUE],
        ['version' => '4.4.2', 'date' => '2013-11-20'],
        ['version' => '4.4.3', 'date' => '2013-12-05'],
        ['version' => '4.4.4', 'date' => '2014-02-07', 'security' => TRUE],
        ['version' => '4.4.5', 'date' => '2014-04-17'],
        ['version' => '4.4.6', 'date' => '2014-07-01', 'security' => TRUE],
        ['version' => '4.4.7', 'date' => '2014-09-17', 'security' => TRUE],
        ['version' => '4.4.8', 'date' => '2014-10-14'],
        ['version' => '4.4.9', 'date' => '2014-11-05'],
        ['version' => '4.4.10', 'date' => '2014-11-19'],
        ['version' => '4.4.11', 'date' => '2014-12-17', 'security' => TRUE],
      ],
    ],
    '4.5' => [
      'status' => 'stable',
      'releases' => [
        ['version' => '4.5.0', 'date' => '2014-09-18'],
        ['version' => '4.5.1', 'date' => '2014-10-09'],
        ['version' => '4.5.2', 'date' => '2014-10-14'],
        ['version' => '4.5.3', 'date' => '2014-11-05'],
        ['version' => '4.5.4', 'date' => '2014-11-19'],
        ['version' => '4.5.5', 'date' => '2014-12-17', 'security' => TRUE],
      ],
    ],
    '4.6' => [
      'status' => 'testing',
      'releases' => [
        ['version' => '4.6.alpha1', 'date' => '2015-02-01'],
        ['version' => '4.6.beta1', 'date' => '2015-03-01'],
      ],
    ],
  ];

  public function tearDown() {
    parent::tearDown();
    $vc = new CRM_Utils_VersionCheck();
    if (file_exists($vc->cacheFile)) {
      unlink($vc->cacheFile);
    }
  }

  public function testCronFallback() {
    // Fake "remote" source data
    $tmpSrc = '/tmp/versionCheckTestFile.json';
    file_put_contents($tmpSrc, json_encode($this->sampleVersionInfo));

    $vc = new CRM_Utils_VersionCheck();
    $vc->pingbackUrl = $tmpSrc;

    // If the cachefile doesn't exist, fallback should kick in
    if (file_exists($vc->cacheFile)) {
      unlink($vc->cacheFile);
    }
    $vc->initialize();
    $this->assertEquals($this->sampleVersionInfo, $vc->versionInfo);
    unset($vc);

    // Update "remote" source data
    $remoteData = ['4.3' => $this->sampleVersionInfo['4.3']];
    file_put_contents($tmpSrc, json_encode($remoteData));

    // Cache was just updated, so fallback should not happen - assert we are still using cached data
    $vc = new CRM_Utils_VersionCheck();
    $vc->pingbackUrl = $tmpSrc;
    $vc->initialize();
    $this->assertEquals($this->sampleVersionInfo, $vc->versionInfo);
    unset($vc);

    // Ensure fallback happens if file is too old
    $vc = new CRM_Utils_VersionCheck();
    $vc->pingbackUrl = $tmpSrc;
    // Set cachefile to be 1 minute older than expire time
    touch($vc->cacheFile, time() - 60 - $vc::CACHEFILE_EXPIRE);
    clearstatcache();
    $vc->initialize();
    $this->assertEquals($remoteData, $vc->versionInfo);
  }

  public function testGetSiteStats() {
    // Create domain address so the domain country will come up in the stats.
    $country_params = [
      'sequential' => 1,
      'options' => [
        'limit' => 1,
      ],
    ];
    $country_result = civicrm_api3('country', 'get', $country_params);
    $country = $country_result['values'][0];

    $domain_params = [
      'id' => CRM_Core_Config::domainID(),
    ];
    CRM_Core_BAO_Domain::retrieve($domain_params, $domain_defaults);
    $location_type = CRM_Core_BAO_LocationType::getDefault();
    $address_params = [
      'contact_id' => $domain_defaults['contact_id'],
      'location_type_id' => $location_type->id,
      'is_primary' => '1',
      'is_billing' => '0',
      'street_address' => '1 Main St.',
      'city' => 'Anywhere',
      'postal_code' => '99999',
      'country_id' => $country['id'],
    ];
    $address_result = civicrm_api3('address', 'create', $address_params);

    // Build stats and test them.
    $vc = new ReflectionClass('CRM_Utils_VersionCheck');
    $vc_instance = $vc->newInstance();

    $statsBuilder = $vc->getMethod('getSiteStats');
    $statsBuilder->setAccessible(TRUE);
    $statsBuilder->invoke($vc_instance, NULL);

    $statsProperty = $vc->getProperty('stats');
    $statsProperty->setAccessible(TRUE);
    $stats = $statsProperty->getValue($vc_instance);

    // Stats array should have correct elements.
    $this->assertArrayHasKey('version', $stats);

    // See CRM_Utils_VersionCheck::getSiteStats where alpha versions don't get
    // full stats generated
    if (array_key_exists('version', $stats) && strpos($stats['version'], 'alpha') === FALSE) {
      $this->assertArrayHasKey('hash', $stats);
      $this->assertArrayHasKey('uf', $stats);
      $this->assertArrayHasKey('lang', $stats);
      $this->assertArrayHasKey('co', $stats);
      $this->assertArrayHasKey('ufv', $stats);
      $this->assertArrayHasKey('PHP', $stats);
      $this->assertArrayHasKey('MySQL', $stats);
      $this->assertArrayHasKey('communityMessagesUrl', $stats);
      $this->assertArrayHasKey('domain_isoCode', $stats);
      $this->assertArrayHasKey('PPTypes', $stats);
      $this->assertArrayHasKey('entities', $stats);
      $this->assertArrayHasKey('extensions', $stats);
      $this->assertType('array', $stats['entities']);
      $this->assertType('array', $stats['extensions']);

      // Assert $stats['domain_isoCode'] is correct.
      $this->assertEquals($country['iso_code'], $stats['domain_isoCode']);

      $entity_names = [];
      foreach ($stats['entities'] as $entity) {
        $entity_names[] = $entity['name'];
        $this->assertType('int', $entity['size'], "Stats entity {$entity['name']} has integer size?");
      }

      $expected_entity_names = [
        'Activity',
        'Case',
        'Contact',
        'Relationship',
        'Campaign',
        'Contribution',
        'ContributionPage',
        'ContributionProduct',
        'Widget',
        'Discount',
        'PriceSetEntity',
        'UFGroup',
        'Event',
        'Participant',
        'Friend',
        'Grant',
        'Mailing',
        'Membership',
        'MembershipBlock',
        'Pledge',
        'PledgeBlock',
        'Delivered',
      ];
      sort($entity_names);
      sort($expected_entity_names);
      $this->assertEquals($expected_entity_names, $entity_names);

      // TODO: Also test for enabled extensions.
    }
  }

}
