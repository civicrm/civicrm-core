<?php

namespace Civi\Core;

/**
 * Class CRM_Core_RegionTest
 *
 * @group headless
 */
class ThemesTest extends \CiviUnitTestCase {

  protected function setUp() {
    $this->useTransaction();
    parent::setUp();
  }

  public function getThemeExamples() {
    $cases = [];

    // --- Library of example themes which we can include in tests. ---

    $hookJudy = [
      'judy' => [
        'title' => 'Judy Garland',
        'ext' => 'civicrm',
        'prefix' => 'tests/phpunit/Civi/Core/Theme/judy/',
        'excludes' => ['test.extension.uitest-files/ignoreme.css'],
      ],
    ];
    $hookLiza = [
      'liza' => [
        'title' => 'Liza Minnelli',
        'prefix' => 'tests/phpunit/Civi/Core/Theme/liza/',
        'ext' => 'civicrm',
      ],
    ];
    $hookBlueMarine = [
      'bluemarine' => [
        'title' => 'Blue Marine',
        'url_callback' => [__CLASS__, 'fakeCallback'],
        'ext' => 'civicrm',
      ],
    ];
    $hookAquaMarine = [
      'aquamarine' => [
        'title' => 'Aqua Marine',
        'url_callback' => [__CLASS__, 'fakeCallback'],
        'ext' => 'civicrm',
        'search_order' => ['aquamarine', 'bluemarine', '_fallback_'],
      ],
    ];

    $civicrmBaseUrl = rtrim(\Civi::paths()->getVariable('civicrm.root', 'url'), '/');

    // --- Library of tests ---

    // Use the default theme, Greenwich.
    $cases[] = [
      [],
      'default',
      'Greenwich',
      [
        'civicrm-css/civicrm.css' => ["$civicrmBaseUrl/css/civicrm.css"],
        'civicrm-css/joomla.css' => ["$civicrmBaseUrl/css/joomla.css"],
        'test.extension.uitest-files/foo.css' => ["$civicrmBaseUrl/tests/extensions/test.extension.uitest/files/foo.css"],
      ],
    ];

    // judy is defined. Let's use judy.
    $cases[] = [
      // Example hook data
      $hookJudy,
      'judy',
      // Example theme to inspect
      'Judy Garland',
      [
        'civicrm-css/civicrm.css' => ["$civicrmBaseUrl/tests/phpunit/Civi/Core/Theme/judy/css/civicrm.css"],
        'civicrm-css/joomla.css' => ["$civicrmBaseUrl/css/joomla.css"],
        'test.extension.uitest-files/foo.css' => ["$civicrmBaseUrl/tests/extensions/test.extension.uitest/files/foo.css"],
        // excluded
        'test.extension.uitest-files/ignoreme.css' => [],
      ],
    ];

    // Misconfiguration: liza was previously used but then disappeared. Fallback to default, Greenwich.
    $cases[] = [
      $hookJudy,
      'liza',
      'Greenwich',
      [
        'civicrm-css/civicrm.css' => ["$civicrmBaseUrl/css/civicrm.css"],
        'civicrm-css/joomla.css' => ["$civicrmBaseUrl/css/joomla.css"],
        'test.extension.uitest-files/foo.css' => ["$civicrmBaseUrl/tests/extensions/test.extension.uitest/files/foo.css"],
      ],
    ];

    // We have some themes available, but the admin opted out.
    $cases[] = [
      $hookJudy,
      'none',
      'None (Unstyled)',
      [
        'civicrm-css/civicrm.css' => [],
        'civicrm-css/joomla.css' => ["$civicrmBaseUrl/css/joomla.css"],
        'test.extension.uitest-files/foo.css' => ["$civicrmBaseUrl/tests/extensions/test.extension.uitest/files/foo.css"],
      ],
    ];

    // Theme which overrides an extension's CSS file.
    $cases[] = [
      $hookJudy + $hookLiza,
      'liza',
      'Liza Minnelli',
      [
        // Warning: If your local system has overrides for the `debug_enabled`, these results may vary.
        'civicrm-css/civicrm.css' => ["$civicrmBaseUrl/tests/phpunit/Civi/Core/Theme/liza/css/civicrm.css"],
        'civicrm-css/civicrm.min.css' => ["$civicrmBaseUrl/tests/phpunit/Civi/Core/Theme/liza/css/civicrm.min.css"],
        'civicrm-css/joomla.css' => ["$civicrmBaseUrl/css/joomla.css"],
        'test.extension.uitest-files/foo.css' => ["$civicrmBaseUrl/tests/phpunit/Civi/Core/Theme/liza/test.extension.uitest-files/foo.css"],
      ],
    ];

    // Theme has a custom URL-lookup function.
    $cases[] = [
      $hookBlueMarine + $hookAquaMarine,
      'bluemarine',
      'Blue Marine',
      [
        'civicrm-css/civicrm.css' => ['http://example.com/blue/civicrm.css'],
        'civicrm-css/joomla.css' => ["$civicrmBaseUrl/css/joomla.css"],
        'test.extension.uitest-files/foo.css' => ['http://example.com/blue/foobar/foo.css'],
      ],
    ];

    // Theme is derived from another.
    $cases[] = [
      $hookBlueMarine + $hookAquaMarine,
      'aquamarine',
      'Aqua Marine',
      [
        'civicrm-css/civicrm.css' => ['http://example.com/aqua/civicrm.css'],
        'civicrm-css/joomla.css' => ["$civicrmBaseUrl/css/joomla.css"],
        'test.extension.uitest-files/foo.css' => ['http://example.com/blue/foobar/foo.css'],
      ],
    ];

    return $cases;
  }

  /**
   * Test theme.
   *
   * @param array $inputtedHook
   * @param string $themeKey
   * @param string $expectedTitle
   * @param array $expectedUrls
   *   List of files to lookup plus the expected URLs.
   *   Array("{$extName}-{$fileName}" => "{$expectUrl}").
   *
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

    /** @var \Civi\Core\Themes $themeSvc */
    $themeSvc = \Civi::service('themes');
    $theme = $themeSvc->get($themeSvc->getActiveThemeKey());
    if ($expectedTitle) {
      $this->assertEquals($expectedTitle, $theme['title']);
    }

    foreach ($expectedUrls as $inputFile => $expectedUrl) {
      list ($ext, $file) = explode('-', $inputFile, 2);
      $actualUrl = $themeSvc->resolveUrls($themeSvc->getActiveThemeKey(), $ext, $file);
      foreach (array_keys($actualUrl) as $k) {
        // Ignore cache revision key (`?r=abcd1234`).
        list ($actualUrl[$k]) = explode('?', $actualUrl[$k], 2);
      }
      $this->assertEquals($expectedUrl, $actualUrl, "Check URL for $inputFile");
    }
  }

  public static function fakeCallback($themes, $themeKey, $cssExt, $cssFile) {
    $map['bluemarine']['civicrm']['css/bootstrap.css'] = ['http://example.com/blue/bootstrap.css'];
    $map['bluemarine']['civicrm']['css/civicrm.css'] = ['http://example.com/blue/civicrm.css'];
    $map['bluemarine']['test.extension.uitest']['files/foo.css'] = ['http://example.com/blue/foobar/foo.css'];
    $map['aquamarine']['civicrm']['css/civicrm.css'] = ['http://example.com/aqua/civicrm.css'];
    return isset($map[$themeKey][$cssExt][$cssFile]) ? $map[$themeKey][$cssExt][$cssFile] : Themes::PASSTHRU;
  }

  public function testGetAll() {
    $all = \Civi::service('themes')->getAll();
    $this->assertTrue(isset($all['greenwich']));
    $this->assertTrue(isset($all['_fallback_']));
  }

  public function testGetAvailable() {
    $all = \Civi::service('themes')->getAvailable();
    $this->assertTrue(isset($all['greenwich']));
    $this->assertFalse(isset($all['_fallback_']));
  }

  public function testApiOptions() {
    $result = $this->callAPISuccess('Setting', 'getoptions', [
      'field' => 'theme_backend',
    ]);
    $this->assertTrue(isset($result['values']['greenwich']));
    $this->assertFalse(isset($result['values']['_fallback_']));
  }

}
