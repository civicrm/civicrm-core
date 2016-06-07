<?php
namespace Civi\Core;

/**
 * Class CRM_Core_RegionTest
 * @group headless
 */
class ThemeTest extends \CiviUnitTestCase {

  public function getThemeExamples() {
    $cases = array();

    $cases[] = array(
      array(
        'judy' => array(
          'title' => 'Judy Garland',
          'ext' => 'civicrm',
        ),
      ),
      'judy',
      'Judy Garland',
      array('/css/bootstrap.css'),
      array('/css/civicrm.css'),
    );
    $cases[] = array(
      array(
        'judy' => array(
          'title' => 'Judy Garland',
          'ext' => 'civicrm',
        ),
      ),
      'liza',
      NULL,
      array(),
      array(),
    );
    $cases[] = array(
      array(
        'judy' => array(
          'title' => 'Judy Garland',
          'ext' => 'civicrm',
        ),
        'classic' => array(
          'title' => 'Classical Musical Actresses',
          'subdir' => 'classic/',
          'ext' => 'civicrm',
        ),
      ),
      'liza',
      NULL,
      array('/classic/bootstrap.css'),
      array('/classic/civicrm.css'),
    );
    $cases[] = array(
      array(
        'judy' => array(
          'title' => 'Judy Garland',
          'ext' => 'civicrm',
        ),
        'classic' => array(
          'title' => 'Classical Musical Actresses',
          'subdir' => 'classic/',
          'ext' => 'civicrm',
        ),
      ),
      'none',
      'No theming',
      array(),
      array(),
    );
    $cases[] = array(
      array(
        'liza' => array(
          'title' => 'Liza Minnelli',
          'subdir' => 'super/secret',
          'ext' => 'civicrm',
        ),
      ),
      'liza',
      'Liza Minnelli',
      array('/super/secret/bootstrap.css'),
      array('/super/secret/civicrm.css'),
    );
    $cases[] = array(
      array(
        'judy' => array(
          'title' => 'Judy Garland',
          'ext' => 'civicrm',
        ),
        'liza' => array(
          'title' => 'Liza Minnelli',
          'subdir' => 'super/secret',
          'ext' => 'civicrm',
        ),
      ),
      'liza',
      'Liza Minnelli',
      array('/super/secret/bootstrap.css'),
      array('/super/secret/civicrm.css'),
    );
    $cases[] = array(
      array(
        'bluemarine' => array(
          'title' => 'Blue Marine',
          'css_callback' => array(__CLASS__, 'fakeCallback'),
          'ext' => 'civicrm',
        ),
      ),
      'bluemarine',
      'Blue Marine',
      array('http://cdn.com/bootstrap.css'),
      array('http://cdn.com/civicrm.css'),
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

    $themes = Theme::getThemes();
    if ($expectedTitle) {
      $this->assertEquals($expectedTitle, $themes[$themeKey]['title']);
    }

    $this->assertEquals($expectedBootstrapUrl, Theme::getCssUrls($themeKey, 'bootstrap.css'));
    $this->assertEquals($expectedCivicrmUrl, Theme::getCssUrls($themeKey, 'civicrm.css'));
  }

  public static function fakeCallback($themeKey, $cssKey) {
    $map = array(
      'bootstrap.css' => array('http://cdn.com/bootstrap.css'),
      'civicrm.css' => array('http://cdn.com/civicrm.css'),
    );
    return $map[$cssKey];
  }

}
