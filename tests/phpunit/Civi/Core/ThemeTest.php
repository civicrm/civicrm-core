<?php
namespace Civi\Core;

/**
 * Class CRM_Core_RegionTest
 * @group headless
 */
class ThemeTest extends \CiviUnitTestCase {

  protected function setUp() {
    $this->useTransaction();
    parent::setUp();
  }

  public function getThemeExamples() {
    $cases = array();

    // judy is defined. Let's use judy.
    $cases[] = array(
      // Example hook data
      array(
        'judy' => array(
          'title' => 'Judy Garland',
          'ext' => 'civicrm',
          'prefix' => 'judy/',
        ),
      ),
      'judy', // Example theme to inspect
      'Judy Garland', // Expect: Title of the example theme
      array('/judy/css/bootstrap.css'), // Expect: URL of the bootstrap.css within judy
      array('/judy/css/civicrm.css'), // Expect: URL of the civicrm.css within judy
    );

    // Misconfiguration: liza was configured but then disappeared. Fallback to Greenwich.
    $cases[] = array(
      array(
        'judy' => array(
          'title' => 'Judy Garland',
          'ext' => 'civicrm',
        ),
      ),
      'liza',
      'Greenwich',
      array('/css/bootstrap.css'),
      array('/css/civicrm.css'),
    );

    // We have some themes available, but they were disabled by admin.
    $cases[] = array(
      array(
        'judy' => array(
          'title' => 'Judy Garland',
          'ext' => 'civicrm',
        ),
      ),
      'none',
      'Empty Theme',
      array(),
      array(),
    );

    // A custom theme with a different name
    $cases[] = array(
      array(
        'liza' => array(
          'title' => 'Liza Minnelli',
          'prefix' => 'super/secret/',
          'ext' => 'civicrm',
        ),
      ),
      'liza',
      'Liza Minnelli',
      array('/super/secret/css/bootstrap.css'),
      array('/super/secret/css/civicrm.css'),
    );

    // The theme is part of a multitheme extension.
    $cases[] = array(
      array(
        'judy' => array(
          'title' => 'Judy Garland',
          'ext' => 'civicrm',
          'prefix' => 'judy/',
        ),
        'liza' => array(
          'title' => 'Liza Minnelli',
          'ext' => 'civicrm',
          'prefix' => 'liza/',
        ),
      ),
      'liza',
      'Liza Minnelli',
      array('/liza/css/bootstrap.css'),
      array('/liza/css/civicrm.css'),
    );

    // Theme has a custom URL-lookup function.
    $cases[] = array(
      array(
        'bluemarine' => array(
          'title' => 'Blue Marine',
          'url_callback' => array(__CLASS__, 'fakeCallback'),
          'ext' => 'civicrm',
        ),
      ),
      'bluemarine',
      'Blue Marine',
      array('http://example.com/blue/bootstrap.css'),
      array('http://example.com/blue/civicrm.css'),
    );

    return $cases;
  }

  /**
   * @param array $inputtedHook
   * @param string $themeKey
   * @param string $expectedBootstrapUrl
   * @param string $expectedCivicrmUrl
   * @dataProvider getThemeExamples
   */
  public function testTheme($inputtedHook, $themeKey, $expectedTitle, $expectedBootstrapUrl, $expectedCivicrmUrl) {
    $this->hookClass->setHook('civicrm_themes', function (&$themes) use ($inputtedHook) {
      foreach ($inputtedHook as $key => $value) {
        $themes[$key] = $value;
      }
    });

    \Civi::settings()->set('theme_frontend', $themeKey);
    \Civi::settings()->set('theme_backend', $themeKey);

    $theme = \Civi::service('theme')->getActive();
    if ($expectedTitle) {
      $this->assertEquals($expectedTitle, $theme['title']);
    }

    $this->assertEquals($expectedBootstrapUrl, \Civi::service('theme')->getUrls('css/bootstrap.css'));
    $this->assertEquals($expectedCivicrmUrl, \Civi::service('theme')->getUrls('css/civicrm.css'));
  }

  public static function fakeCallback($theme, $cssKey) {
    $map['bluemarine']['css/bootstrap.css'] = array('http://example.com/blue/bootstrap.css');
    $map['bluemarine']['css/civicrm.css'] = array('http://example.com/blue/civicrm.css');
    return $map[$theme['name']][$cssKey];
  }

}
