<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * Tests for linking to resource files
 * @group headless
 */
class CRM_Core_ResourcesTest extends CiviUnitTestCase {

  /**
   * @var CRM_Core_Resources
   */
  protected $res;

  /**
   * @var CRM_Extension_Mapper
   */
  protected $mapper;

  /**
   * @var string for testing cache buster generation
   */
  protected $cacheBusterString = 'xBkdk3';

  protected $originalRequest;
  protected $originalGet;

  public function setUp() {
    parent::setUp();

    list ($this->basedir, $this->container, $this->mapper) = $this->_createMapper();
    $cache = new CRM_Utils_Cache_Arraycache(array());
    $this->res = new CRM_Core_Resources($this->mapper, $cache, NULL);
    $this->res->setCacheCode('resTest');
    CRM_Core_Resources::singleton($this->res);

    // Templates injected into regions should normally be file names, but for unit-testing it's handy to use "string:" notation
    require_once 'CRM/Core/Smarty/resources/String.php';
    civicrm_smarty_register_string_resource();

    $this->originalRequest = $_REQUEST;
    $this->originalGet = $_GET;
  }

  /**
   * Restore globals so this test doesn't interfere with others.
   */
  public function tearDown() {
    $_REQUEST = $this->originalRequest;
    $_GET = $this->originalGet;
  }

  public function testAddScriptFile() {
    $this->res
      ->addScriptFile('com.example.ext', 'foo%20bar.js', 0, 'testAddScriptFile')
      // extra
      ->addScriptFile('com.example.ext', 'foo%20bar.js', 0, 'testAddScriptFile')
      ->addScriptFile('civicrm', 'foo%20bar.js', 0, 'testAddScriptFile');

    $smarty = CRM_Core_Smarty::singleton();
    $actual = $smarty->fetch('string:{crmRegion name=testAddScriptFile}{/crmRegion}');
    // stable ordering: alphabetical by (snippet.weight,snippet.name)
    $expected = ""
      . "<script type=\"text/javascript\" src=\"http://core-app/foo%20bar.js?r=resTest\">\n</script>\n"
      . "<script type=\"text/javascript\" src=\"http://ext-dir/com.example.ext/foo%20bar.js?r=resTest\">\n</script>\n";
    $this->assertEquals($expected, $actual);
  }

  /**
   * When adding a script file, any ts() expressions should be translated and added to the 'strings'
   *
   * FIXME: This can't work because the tests run in English and CRM_Core_Resources optimizes
   * away the English data from $settings['strings']
   * public function testAddScriptFile_strings() {
   * file_put_contents($this->mapper->keyToBasePath('com.example.ext') . '/hello.js', 'alert(ts("Hello world"));');
   * $this->res->addScriptFile('com.example.ext', 'hello.js', 0, 'testAddScriptFile_strings');
   * $settings = $this->res->getSettings();
   * $expected = array('Hello world');
   * $this->assertEquals($expected, $settings['strings']);
   * }
   */

  /**
   * Ensure that adding a script URL creates expected markup.
   */
  public function testAddScriptURL() {
    $this->res
      ->addScriptUrl('/whiz/foo%20bar.js', 0, 'testAddScriptURL')
      // extra
      ->addScriptUrl('/whiz/foo%20bar.js', 0, 'testAddScriptURL')
      ->addScriptUrl('/whizbang/foo%20bar.js', 0, 'testAddScriptURL');

    $smarty = CRM_Core_Smarty::singleton();
    $actual = $smarty->fetch('string:{crmRegion name=testAddScriptURL}{/crmRegion}');
    // stable ordering: alphabetical by (snippet.weight,snippet.name)
    $expected = ""
      . "<script type=\"text/javascript\" src=\"/whiz/foo%20bar.js\">\n</script>\n"
      . "<script type=\"text/javascript\" src=\"/whizbang/foo%20bar.js\">\n</script>\n";
    $this->assertEquals($expected, $actual);
  }

  public function testAddScript() {
    $this->res
      ->addScript('alert("hi");', 0, 'testAddScript')
      ->addScript('alert("there");', 0, 'testAddScript');

    $smarty = CRM_Core_Smarty::singleton();
    $actual = $smarty->fetch('string:{crmRegion name=testAddScript}{/crmRegion}');
    $expected = ""
      . "<script type=\"text/javascript\">\nalert(\"hi\");\n</script>\n"
      . "<script type=\"text/javascript\">\nalert(\"there\");\n</script>\n";
    $this->assertEquals($expected, $actual);
  }

  public function testAddVars() {
    $this->res
      ->addVars('food', array('fruit' => array('mine' => 'apple', 'ours' => 'banana')))
      ->addVars('food', array('fruit' => array('mine' => 'new apple', 'yours' => 'orange')));
    $this->assertTreeEquals(
      array(
        'vars' => array(
          'food' => array(
            'fruit' => array(
              'yours' => 'orange',
              'mine' => 'new apple',
              'ours' => 'banana',
            ),
          ),
        ),
      ),
      $this->res->getSettings()
    );
  }

  public function testAddSetting() {
    $this->res
      ->addSetting(array('fruit' => array('mine' => 'apple')))
      ->addSetting(array('fruit' => array('yours' => 'orange')));
    $this->assertTreeEquals(
      array('fruit' => array('yours' => 'orange', 'mine' => 'apple')),
      $this->res->getSettings()
    );
    $actual = $this->res->renderSetting();
    $expected = json_encode(array('fruit' => array('yours' => 'orange', 'mine' => 'apple')));
    $this->assertTrue(strpos($actual, $expected) !== FALSE);
  }

  public function testAddSettingHook() {
    $test = $this;
    Civi::dispatcher()->addListener('hook_civicrm_alterResourceSettings', function($event) use ($test) {
      $test->assertEquals('apple', $event->data['fruit']['mine']);
      $event->data['fruit']['mine'] = 'banana';
    });
    $this->res->addSetting(array('fruit' => array('mine' => 'apple')));
    $settings = $this->res->getSettings();
    $this->assertTreeEquals(array('fruit' => array('mine' => 'banana')), $settings);
  }

  public function testAddSettingFactory() {
    $this->res->addSettingsFactory(function () {
      return array('fruit' => array('yours' => 'orange'));
    });
    $this->res->addSettingsFactory(function () {
      return array('fruit' => array('mine' => 'apple'));
    });

    $actual = $this->res->getSettings();
    $expected = array('fruit' => array('yours' => 'orange', 'mine' => 'apple'));
    $this->assertTreeEquals($expected, $actual);
  }

  public function testAddSettingAndSettingFactory() {
    $this->res->addSetting(array('fruit' => array('mine' => 'apple')));

    $muckableValue = array('fruit' => array('yours' => 'orange', 'theirs' => 'apricot'));
    $this->res->addSettingsFactory(function () use (&$muckableValue) {
      return $muckableValue;
    });
    $actual = $this->res->getSettings();
    $expected = array('fruit' => array('mine' => 'apple', 'yours' => 'orange', 'theirs' => 'apricot'));
    $this->assertTreeEquals($expected, $actual);

    // note: the setting is not fixed based on what the factory returns when registered; it's based
    // on what the factory returns when getSettings is called
    $muckableValue = array('fruit' => array('yours' => 'banana'));
    $actual = $this->res->getSettings();
    $expected = array('fruit' => array('mine' => 'apple', 'yours' => 'banana'));
    $this->assertTreeEquals($expected, $actual);
  }

  public function testCrmJS() {
    $smarty = CRM_Core_Smarty::singleton();

    $actual = $smarty->fetch('string:{crmScript ext=com.example.ext file=foo%20bar.js region=testCrmJS}');
    $this->assertEquals('', $actual);

    $actual = $smarty->fetch('string:{crmScript url=/whiz/foo%20bar.js region=testCrmJS weight=1}');
    $this->assertEquals('', $actual);

    $actual = $smarty->fetch('string:{crmRegion name=testCrmJS}{/crmRegion}');
    // stable ordering: alphabetical by (snippet.weight,snippet.name)
    $expected = ""
      . "<script type=\"text/javascript\" src=\"http://ext-dir/com.example.ext/foo%20bar.js?r=resTest\">\n</script>\n"
      . "<script type=\"text/javascript\" src=\"/whiz/foo%20bar.js\">\n</script>\n";
    $this->assertEquals($expected, $actual);
  }

  public function testAddStyleFile() {
    $this->res
      ->addStyleFile('com.example.ext', 'foo%20bar.css', 0, 'testAddStyleFile')
      // extra
      ->addStyleFile('com.example.ext', 'foo%20bar.css', 0, 'testAddStyleFile')
      ->addStyleFile('civicrm', 'foo%20bar.css', 0, 'testAddStyleFile');

    $smarty = CRM_Core_Smarty::singleton();
    $actual = $smarty->fetch('string:{crmRegion name=testAddStyleFile}{/crmRegion}');
    // stable ordering: alphabetical by (snippet.weight,snippet.name)
    $expected = ""
      . "<link href=\"http://core-app/foo%20bar.css?r=resTest\" rel=\"stylesheet\" type=\"text/css\"/>\n"
      . "<link href=\"http://ext-dir/com.example.ext/foo%20bar.css?r=resTest\" rel=\"stylesheet\" type=\"text/css\"/>\n";
    $this->assertEquals($expected, $actual);
  }

  public function testAddStyleURL() {
    $this->res
      ->addStyleUrl('/whiz/foo%20bar.css', 0, 'testAddStyleURL')
      // extra
      ->addStyleUrl('/whiz/foo%20bar.css', 0, 'testAddStyleURL')
      ->addStyleUrl('/whizbang/foo%20bar.css', 0, 'testAddStyleURL');

    $smarty = CRM_Core_Smarty::singleton();
    $actual = $smarty->fetch('string:{crmRegion name=testAddStyleURL}{/crmRegion}');
    // stable ordering: alphabetical by (snippet.weight,snippet.name)
    $expected = ""
      . "<link href=\"/whiz/foo%20bar.css\" rel=\"stylesheet\" type=\"text/css\"/>\n"
      . "<link href=\"/whizbang/foo%20bar.css\" rel=\"stylesheet\" type=\"text/css\"/>\n";
    $this->assertEquals($expected, $actual);
  }

  public function testAddStyle() {
    $this->res
      ->addStyle('body { background: black; }', 0, 'testAddStyle')
      ->addStyle('body { text-color: black; }', 0, 'testAddStyle');

    $smarty = CRM_Core_Smarty::singleton();
    $actual = $smarty->fetch('string:{crmRegion name=testAddStyle}{/crmRegion}');
    $expected = ""
      . "<style type=\"text/css\">\nbody { background: black; }\n</style>\n"
      . "<style type=\"text/css\">\nbody { text-color: black; }\n</style>\n";
    $this->assertEquals($expected, $actual);
  }

  public function testCrmCSS() {
    $smarty = CRM_Core_Smarty::singleton();

    $actual = $smarty->fetch('string:{crmStyle ext=com.example.ext file=foo%20bar.css region=testCrmCSS}');
    $this->assertEquals('', $actual);

    $actual = $smarty->fetch('string:{crmStyle url=/whiz/foo%20bar.css region=testCrmCSS weight=1}');
    $this->assertEquals('', $actual);

    $actual = $smarty->fetch('string:{crmRegion name=testCrmCSS}{/crmRegion}');
    // stable ordering: alphabetical by (snippet.weight,snippet.name)
    $expected = ""
      . "<link href=\"http://ext-dir/com.example.ext/foo%20bar.css?r=resTest\" rel=\"stylesheet\" type=\"text/css\"/>\n"
      . "<link href=\"/whiz/foo%20bar.css\" rel=\"stylesheet\" type=\"text/css\"/>\n";
    $this->assertEquals($expected, $actual);
  }

  public function testGetURL() {
    $this->assertEquals(
      'http://core-app/dir/file%20name.txt',
      $this->res->getURL('civicrm', 'dir/file%20name.txt')
    );
    $this->assertEquals(
      'http://ext-dir/com.example.ext/dir/file%20name.txt',
      $this->res->getURL('com.example.ext', 'dir/file%20name.txt')
    );
    $this->assertEquals(
      'http://core-app/',
      $this->res->getURL('civicrm')
    );
    $this->assertEquals(
      'http://ext-dir/com.example.ext/',
      $this->res->getURL('com.example.ext')
    );
  }

  public function testCrmResURL() {
    $smarty = CRM_Core_Smarty::singleton();

    $actual = $smarty->fetch('string:{crmResURL ext=com.example.ext file=foo%20bar.png}');
    $this->assertEquals('http://ext-dir/com.example.ext/foo%20bar.png', $actual);

    $actual = $smarty->fetch('string:{crmResURL ext=com.example.ext file=foo%20bar.png addCacheCode=1}');
    $this->assertEquals('http://ext-dir/com.example.ext/foo%20bar.png?r=resTest', $actual);

    $actual = $smarty->fetch('string:{crmResURL ext=com.example.ext}');
    $this->assertEquals('http://ext-dir/com.example.ext/', $actual);
  }

  public function testGlob() {
    $this->assertEquals(
      array('info.xml'),
      $this->res->glob('com.example.ext', 'info.xml')
    );
    $this->assertEquals(
      array('js/example.js'),
      $this->res->glob('com.example.ext', 'js/*.js')
    );
    $this->assertEquals(
      array('js/example.js'),
      $this->res->glob('com.example.ext', array('js/*.js'))
    );
  }

  /**
   * @dataProvider ajaxModeData
   */
  public function testIsAjaxMode($query, $result) {
    $_REQUEST = $_GET = $query;
    $this->assertEquals($result, CRM_Core_Resources::isAjaxMode());
  }

  public function ajaxModeData() {
    return array(
      array(array('q' => 'civicrm/ajax/foo'), TRUE),
      array(array('q' => 'civicrm/angularprofiles/template'), TRUE),
      array(array('q' => 'civicrm/test/page'), FALSE),
      array(array('q' => 'civicrm/test/page', 'snippet' => 'json'), TRUE),
      array(array('q' => 'civicrm/test/page', 'snippet' => 'foo'), FALSE),
    );
  }

  /**
   * @param CRM_Utils_Cache_Interface $cache
   * @param string $cacheKey
   *
   * @return array
   *   [string $basedir, CRM_Extension_Container_Interface, CRM_Extension_Mapper]
   */
  public function _createMapper(CRM_Utils_Cache_Interface $cache = NULL, $cacheKey = NULL) {
    $basedir = rtrim($this->createTempDir('ext-'), '/');
    mkdir("$basedir/com.example.ext");
    mkdir("$basedir/com.example.ext/js");
    file_put_contents("$basedir/com.example.ext/info.xml", "<extension key='com.example.ext' type='report'><file>oddball</file></extension>");
    file_put_contents("$basedir/com.example.ext/js/example.js", "alert('Boo!');");
    // not needed for now // file_put_contents("$basedir/weird/bar/oddball.php", "<?php\n");
    $c = new CRM_Extension_Container_Basic($basedir, 'http://ext-dir', $cache, $cacheKey);
    $mapper = new CRM_Extension_Mapper($c, NULL, NULL, '/pathto/civicrm', 'http://core-app');
    return array($basedir, $c, $mapper);
  }

  /**
   * @param string $url
   * @param string $expected
   *
   * @dataProvider urlForCacheCodeProvider
   */
  public function testAddingCacheCode($url, $expected) {
    $resources = CRM_Core_Resources::singleton();
    $resources->setCacheCode($this->cacheBusterString);
    $this->assertEquals($expected, $resources->addCacheCode($url));
  }

  /**
   * @return array
   */
  public function urlForCacheCodeProvider() {
    return array(
      array(
        'http://www.civicrm.org',
        'http://www.civicrm.org?r=' . $this->cacheBusterString,
      ),
      array(
        'www.civicrm.org/custom.css?foo=bar',
        'www.civicrm.org/custom.css?foo=bar&r=' . $this->cacheBusterString,
      ),
      array(
        'civicrm.org/custom.css?car=blue&foo=bar',
        'civicrm.org/custom.css?car=blue&foo=bar&r=' . $this->cacheBusterString,
      ),
    );
  }

  /**
   * return array
   */
  public function urlsToCheckIfFullyFormed() {
    return [
      ['civicrm/test/page', FALSE],
      ['#', FALSE],
      ['', FALSE],
      ['/civicrm/test/page', TRUE],
      ['http://test.com/civicrm/test/page', TRUE],
      ['https://test.com/civicrm/test/page', TRUE],
    ];
  }

  /**
   * @param string $url
   * @param string $expected
   *
   * @dataProvider urlsToCheckIfFullyFormed
   */
  public function testIsFullyFormedUrl($url, $expected) {
    $this->assertEquals($expected, CRM_Core_Resources::isFullyFormedUrl($url));
  }

}
