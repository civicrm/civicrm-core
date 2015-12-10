<?php

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Class CRM_Utils_versionCheckTest
 */
class CRM_Utils_versionCheckTest extends CiviUnitTestCase {

  /**
   * @return array
   */
  public function get_info() {
    return array(
      'name' => 'VersionCheck Test',
      'description' => 'Test versionCheck functionality',
      'group' => 'CiviCRM BAO Tests',
    );
  }

  public function setUp() {
    parent::setUp();
  }

  /**
   * @var array
   */
  protected $sampleVersionInfo = array(
    '4.2' => array(
      'status' => 'eol',
      'releases' => array(
        array('version' => '4.2.0', 'date' => '2012-08-20'),
        array('version' => '4.2.1', 'date' => '2012-09-12'),
        array('version' => '4.2.2', 'date' => '2012-09-27'),
        array('version' => '4.2.4', 'date' => '2012-10-18'),
        array('version' => '4.2.6', 'date' => '2012-11-01', 'security' => TRUE),
        array('version' => '4.2.7', 'date' => '2013-01-02', 'security' => TRUE),
        array('version' => '4.2.8', 'date' => '2013-02-20'),
        array('version' => '4.2.9', 'date' => '2013-04-03'),
        array('version' => '4.2.10', 'date' => '2013-07-29', 'security' => TRUE),
        array('version' => '4.2.11', 'date' => '2013-09-25'),
        array('version' => '4.2.12', 'date' => '2013-10-02', 'security' => TRUE),
        array('version' => '4.2.13', 'date' => '2013-11-06', 'security' => TRUE),
        array('version' => '4.2.14', 'date' => '2013-11-20'),
        array('version' => '4.2.15', 'date' => '2014-02-07', 'security' => TRUE),
        array('version' => '4.2.16', 'date' => '2014-02-18'),
        array('version' => '4.2.17', 'date' => '2014-07-01', 'security' => TRUE),
        array('version' => '4.2.18', 'date' => '2014-08-06'),
        array('version' => '4.2.19', 'date' => '2014-09-17', 'security' => TRUE),
      ),
    ),
    '4.3' => array(
      'status' => 'lts',
      'releases' => array(
        array('version' => '4.3.0', 'date' => '2013-04-10'),
        array('version' => '4.3.1', 'date' => '2013-04-18'),
        array('version' => '4.3.2', 'date' => '2013-05-02'),
        array('version' => '4.3.3', 'date' => '2013-05-08'),
        array('version' => '4.3.4', 'date' => '2013-06-10', 'security' => TRUE),
        array('version' => '4.3.5', 'date' => '2013-07-08', 'security' => TRUE),
        array('version' => '4.3.6', 'date' => '2013-09-25'),
        array('version' => '4.3.7', 'date' => '2013-10-02', 'security' => TRUE),
        array('version' => '4.3.8', 'date' => '2013-11-06', 'security' => TRUE),
        array('version' => '4.3.9', 'date' => '2014-09-07', 'security' => TRUE),
      ),
    ),
    '4.4' => array(
      'status' => 'lts',
      'releases' => array(
        array('version' => '4.4.0', 'date' => '2013-10-23'),
        array('version' => '4.4.1', 'date' => '2013-11-06', 'security' => TRUE),
        array('version' => '4.4.2', 'date' => '2013-11-20'),
        array('version' => '4.4.3', 'date' => '2013-12-05'),
        array('version' => '4.4.4', 'date' => '2014-02-07', 'security' => TRUE),
        array('version' => '4.4.5', 'date' => '2014-04-17'),
        array('version' => '4.4.6', 'date' => '2014-07-01', 'security' => TRUE),
        array('version' => '4.4.7', 'date' => '2014-09-17', 'security' => TRUE),
        array('version' => '4.4.8', 'date' => '2014-10-14'),
        array('version' => '4.4.9', 'date' => '2014-11-05'),
        array('version' => '4.4.10', 'date' => '2014-11-19'),
        array('version' => '4.4.11', 'date' => '2014-12-17', 'security' => TRUE),
      ),
    ),
    '4.5' => array(
      'status' => 'stable',
      'releases' => array(
        array('version' => '4.5.0', 'date' => '2014-09-18'),
        array('version' => '4.5.1', 'date' => '2014-10-09'),
        array('version' => '4.5.2', 'date' => '2014-10-14'),
        array('version' => '4.5.3', 'date' => '2014-11-05'),
        array('version' => '4.5.4', 'date' => '2014-11-19'),
        array('version' => '4.5.5', 'date' => '2014-12-17', 'security' => TRUE),
      ),
    ),
    '4.6' => array(
      'status' => 'testing',
      'releases' => array(
        array('version' => '4.6.alpha1', 'date' => '2015-02-01'),
        array('version' => '4.6.beta1', 'date' => '2015-03-01'),
      ),
    ),
  );

  /**
   * @dataProvider newerVersionDataProvider
   * @param string $localVersion
   * @param array $versionInfo
   * @param mixed $expectedResult
   */
  public function testNewerVersion($localVersion, $versionInfo, $expectedResult) {
    $vc = new CRM_Utils_VersionCheck();
    // These values are set by the constructor but for testing we override them
    $vc->localVersion = $localVersion;
    $vc->localMajorVersion = $vc->getMajorVersion($localVersion);
    $vc->setVersionInfo($versionInfo);
    $available = $vc->isNewerVersionAvailable();
    $this->assertEquals($available['version'], $expectedResult);
  }

  /**
   * @return array
   *   (localVersion, versionInfo, expectedResult)
   */
  public function newerVersionDataProvider() {
    $data = array();

    // Make sure we do not get unstable release updates for a stable localVersion
    $data[] = array('4.5.5', $this->sampleVersionInfo, NULL);

    // Make sure we do get unstable release updates for unstable localVersion
    $data[] = array('4.6.alpha1', $this->sampleVersionInfo, '4.6.beta1');

    // Make sure we get nothing (and no errors) if no versionInfo available
    $data[] = array('4.7.beta1', array(), NULL);

    // Make sure alerts prioritize the localMajorVersion
    $data[] = array('4.4.1', $this->sampleVersionInfo, '4.4.11');

    // Make sure new security release on newest version doesn't trigger security
    // notice on site running LTS version that doesn't have a security release
    $data[] = array('4.3.9', $this->sampleVersionInfo, NULL);

    // Make sure new security release on newest version DOES trigger security
    // notice on site running EOL version that doesn't have a security release
    $data[] = array('4.2.19', $this->sampleVersionInfo, '4.5.5');

    return $data;
  }

  /**
   * @dataProvider securityUpdateDataProvider
   * @param string $localVersion
   * @param array $versionInfo
   * @param bool $expectedResult
   */
  public function testSecurityUpdate($localVersion, $versionInfo, $expectedResult) {
    $vc = new CRM_Utils_VersionCheck();
    // These values are set by the constructor but for testing we override them
    $vc->localVersion = $localVersion;
    $vc->localMajorVersion = $vc->getMajorVersion($localVersion);
    $vc->setVersionInfo($versionInfo);
    $available = $vc->isNewerVersionAvailable();
    $this->assertEquals($available['upgrade'], $expectedResult);
  }

  /**
   * @return array
   *   (localVersion, versionInfo, expectedResult)
   */
  public function securityUpdateDataProvider() {
    $data = array();

    // Make sure we get alerted if a security release is available
    $data[] = array('4.5.1', $this->sampleVersionInfo, 'security');

    // Make sure we do not get alerted if a security release is not available
    $data[] = array('4.5.5', $this->sampleVersionInfo, NULL);

    // Make sure we get false (and no errors) if no versionInfo available (this will be the case for pre-alphas)
    $data[] = array('4.7.alpha1', array(), NULL);

    // If there are 2 security updates on the same day (e.g. lts and stable majorVersions)
    // we should not get alerted to one if we are using the other
    $data[] = array('4.4.11', $this->sampleVersionInfo, FALSE);

    // This version predates the ones in the info array, it should be assumed to be EOL and insecure
    $data[] = array('4.0.1', $this->sampleVersionInfo, 'security');

    // Make sure new security release on newest version doesn't trigger security
    // notice on site running LTS version that doesn't have a security release
    $data[] = array('4.3.9', $this->sampleVersionInfo, NULL);

    // Make sure new security release on newest version DOES trigger security
    // notice on site running EOL version that doesn't have a security release
    $data[] = array('4.2.19', $this->sampleVersionInfo, 'security');

    return $data;
  }

}
