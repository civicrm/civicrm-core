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

    // --- Library of example themes which we can include in tests. ---

    $hookJudy = array(
      'judy' => array(
        'title' => 'Judy Garland',
        'ext' => 'civicrm',
        'prefix' => 'tests/phpunit/Civi/Core/Theme/judy/',
      ),
    );
    $hookLiza = array(
      'liza' => array(
        'title' => 'Liza Minnelli',
        'prefix' => 'tests/phpunit/Civi/Core/Theme/liza/',
        'ext' => 'civicrm',
      ),
    );
    $hookBlueMarine = array(
      'bluemarine' => array(
        'title' => 'Blue Marine',
        'url_callback' => array(__CLASS__, 'fakeCallback'),
        'ext' => 'civicrm',
      ),
    );
    $hookAquaMarine = array(
      'aquamarine' => array(
        'title' => 'Aqua Marine',
        'url_callback' => array(__CLASS__, 'fakeCallback'),
        'ext' => 'civicrm',
        'search_order' => array('aquamarine', 'bluemarine', '*fallback*'),
      ),
    );

    $civicrmBaseUrl = "";

    // --- Library of tests ---

    // Use the default theme, Greenwich.
    $cases[] = array(
      array(),
      'default',
      'Greenwich',
      array(
        'civicrm-css/civicrm.css' => array("$civicrmBaseUrl/css/civicrm.css"),
        'civicrm-css/joomla.css' => array("$civicrmBaseUrl/css/joomla.css"),
        'test.extension.uitest-files/foo.css' => array("/tests/extensions/test.extension.uitest/files/foo.css"),
      ),
    );

    // judy is defined. Let's use judy.
    $cases[] = array(
      // Example hook data
      $hookJudy,
      'judy',
      // Example theme to inspect
      'Judy Garland',
      array(
        'civicrm-css/civicrm.css' => array("$civicrmBaseUrl/tests/phpunit/Civi/Core/Theme/judy/css/civicrm.css"),
        'civicrm-css/joomla.css' => array("$civicrmBaseUrl/css/joomla.css"),
        'test.extension.uitest-files/foo.css' => array("/tests/extensions/test.extension.uitest/files/foo.css"),
      ),
    );

    // Misconfiguration: liza was previously used but then disappeared. Fallback to default, Greenwich.
    $cases[] = array(
      $hookJudy,
      'liza',
      'Greenwich',
      array(
        'civicrm-css/civicrm.css' => array("$civicrmBaseUrl/css/civicrm.css"),
        'civicrm-css/joomla.css' => array("$civicrmBaseUrl/css/joomla.css"),
        'test.extension.uitest-files/foo.css' => array("/tests/extensions/test.extension.uitest/files/foo.css"),
      ),
    );

    // We have some themes available, but the admin opted out.
    $cases[] = array(
      $hookJudy,
      'none',
      'Empty Theme',
      array(
        'civicrm-css/civicrm.css' => array(),
        'civicrm-css/joomla.css' => array("$civicrmBaseUrl/css/joomla.css"),
        'test.extension.uitest-files/foo.css' => array("/tests/extensions/test.extension.uitest/files/foo.css"),
      ),
    );

    // Theme which overrides an extension's CSS file.
    $cases[] = array(
      $hookJudy + $hookLiza,
      'liza',
      'Liza Minnelli',
      array(
        // Warning: If your local system has overrides for the `debug_enabled`, these results may vary.
        'civicrm-css/civicrm.css' => array("$civicrmBaseUrl/tests/phpunit/Civi/Core/Theme/liza/css/civicrm.css"),
        'civicrm-css/civicrm.min.css' => array("$civicrmBaseUrl/tests/phpunit/Civi/Core/Theme/liza/css/civicrm.min.css"),
        'civicrm-css/joomla.css' => array("$civicrmBaseUrl/css/joomla.css"),
        'test.extension.uitest-files/foo.css' => array("/tests/phpunit/Civi/Core/Theme/liza/test.extension.uitest-files/foo.css"),
      ),
    );

    // Theme has a custom URL-lookup function.
    $cases[] = array(
      $hookBlueMarine + $hookAquaMarine,
      'bluemarine',
      'Blue Marine',
      array(
        'civicrm-css/civicrm.css' => array('http://example.com/blue/civicrm.css'),
        'civicrm-css/joomla.css' => array("$civicrmBaseUrl/css/joomla.css"),
        'test.extension.uitest-files/foo.css' => array('http://example.com/blue/foobar/foo.css'),
      ),
    );

    // Theme is derived from another.
    $cases[] = array(
      $hookBlueMarine + $hookAquaMarine,
      'aquamarine',
      'Aqua Marine',
      array(
        'civicrm-css/civicrm.css' => array('http://example.com/aqua/civicrm.css'),
        'civicrm-css/joomla.css' => array("$civicrmBaseUrl/css/joomla.css"),
        'test.extension.uitest-files/foo.css' => array('http://example.com/blue/foobar/foo.css'),
      ),
    );

    return $cases;
  }

  /**
   * @param array $inputtedHook
   * @param string $themeKey
   * @param array $expectedUrls
   *   List of files to lookup plus the expected URLs.
   *   Array("{$extName}-{$fileName}" => "{$expectUrl}").
   * @dataProvider getThemeExamples
   */
  public function testTheme($inputtedHook, $themeKey, $expectedTitle, $expectedUrls) {
    $this->hookClass->setHook('civicrm_themes', function (&$themes) use ($inputtedHook) {
      foreach ($inputtedHook as $key => $value) {
        $themes[$key] = $value;
      }
    });

    \Civi::settings()->set('theme_frontend', $themeKey);
    \Civi::settings()->set('theme_backend', $themeKey);

    /** @var \Civi\Core\Theme $themeSvc */
    $themeSvc = \Civi::service('theme');
    $theme = $themeSvc->get($themeSvc->getActiveThemeKey());
    if ($expectedTitle) {
      $this->assertEquals($expectedTitle, $theme['title']);
    }

    foreach ($expectedUrls as $inputFile => $expectedUrl) {
      list ($ext, $file) = explode('-', $inputFile, 2);
      $actualUrl = $themeSvc->resolveUrls($themeSvc->getActiveThemeKey(), $ext, $file);
      $this->assertEquals($expectedUrl, $actualUrl, "Check URL for $inputFile");
    }
  }

  public static function fakeCallback($themes, $themeKey, $cssExt, $cssFile) {
    $map['bluemarine']['civicrm']['css/bootstrap.css'] = array('http://example.com/blue/bootstrap.css');
    $map['bluemarine']['civicrm']['css/civicrm.css'] = array('http://example.com/blue/civicrm.css');
    $map['bluemarine']['test.extension.uitest']['files/foo.css'] = array('http://example.com/blue/foobar/foo.css');
    $map['aquamarine']['civicrm']['css/civicrm.css'] = array('http://example.com/aqua/civicrm.css');
    return isset($map[$themeKey][$cssExt][$cssFile]) ? $map[$themeKey][$cssExt][$cssFile] : Theme::PASSTHRU;
  }

}
