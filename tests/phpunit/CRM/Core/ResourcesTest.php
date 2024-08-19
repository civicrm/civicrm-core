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

use Civi\Test\Invasive;

/**
 * Tests for linking to resource files
 * @group headless
 * @group resources
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
   * @var string
   * For testing cache buster generation
   */
  protected $cacheBusterString = 'xBkdk3';

  protected $originalRequest;
  protected $originalGet;

  public function setUp(): void {
    parent::setUp();

    $this->mapper = $this->createMapper();
    $cache = new CRM_Utils_Cache_ArrayCache([]);
    $this->res = new CRM_Core_Resources($this->mapper, new CRM_Core_Resources_Strings($cache), NULL);
    $this->res->setCacheCode('resTest');
    CRM_Core_Resources::singleton($this->res);

    $this->originalRequest = $_REQUEST;
    $this->originalGet = $_GET;
  }

  /**
   * Restore globals so this test doesn't interfere with others.
   */
  public function tearDown(): void {
    $_REQUEST = $this->originalRequest;
    $_GET = $this->originalGet;
    parent::tearDown();
  }

  public function testCreateBasicBundle(): void {
    $hits = [];

    $init = function(CRM_Core_Resources_Bundle $b) use (&$hits) {
      $hits[] = 'init_' . $b->name;
      $b->addScript('doStuff();');
    };
    $alter = function ($e) use (&$hits) {
      $hits[] = 'alter_' . $e->bundle->name;
      $e->bundle->addScript('alert();');
    };

    Civi::dispatcher()->addListener('hook_civicrm_alterBundle', $alter);
    $b = CRM_Core_Resources_Common::createBasicBundle('cheese', $init);
    $this->assertEquals('cheese', $b->name);
    $this->assertEquals(['init_cheese', 'alter_cheese'], $hits);
    $this->assertEquals(['doStuff();', 'alert();'], array_values(CRM_Utils_Array::collect('script', $b->getAll())));
  }

  /**
   * Make two bundles (multi-regional). Add them to CRM_Core_Resources.
   * Ensure that the resources land in the right regions.
   */
  public function testAddBundle(): void {
    $foo = new CRM_Core_Resources_Bundle('foo', ['scriptUrl', 'styleUrl', 'markup']);
    $bar = new CRM_Core_Resources_Bundle('bar', ['scriptUrl', 'styleUrl', 'markup']);

    $foo->addScriptUrl('http://example.com/foo.js', 100, 'testAddBundle_foo');
    $foo->add(['markup' => 'Hello, foo', 'region' => 'page-header']);
    $bar->addScriptUrl('http://example.com/bar.js', 100, 'testAddBundle_bar');
    $bar->add(['markup' => 'Hello, bar', 'region' => 'page-header']);
    $foo->addStyleUrl('http://example.com/shoes.css');

    $this->res->addBundle($foo);
    $this->res->addBundle([$bar]);

    $getPropsByRegion = function($region, $key) {
      $props = [];
      foreach (CRM_Core_Region::instance($region)->getAll() as $snippet) {
        if (isset($snippet[$key])) {
          $props[] = $snippet[$key];
        }
      }
      return $props;
    };

    $this->assertEquals(
      ['http://example.com/foo.js'],
      $getPropsByRegion('testAddBundle_foo', 'scriptUrl')
    );
    $this->assertEquals(
      ['http://example.com/bar.js'],
      $getPropsByRegion('testAddBundle_bar', 'scriptUrl')
    );
    $this->assertEquals(
      ['', 'Hello, foo', 'Hello, bar'],
      $getPropsByRegion('page-header', 'markup')
    );
    $this->assertEquals(
      ['http://example.com/shoes.css'],
      $getPropsByRegion('page-footer', 'styleUrl')
    );
  }

  public function testAddScriptFile(): void {
    $this->res
      ->addScriptFile('com.example.ext', 'foo%20bar.js', 0, 'testAddScriptFile')
      // extra
      ->addScriptFile('com.example.ext', 'foo%20bar.js', 0, 'testAddScriptFile')
      ->addScriptFile('civicrm', 'foo%20bar.js', 0, 'testAddScriptFile');

    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{crmRegion name="testAddScriptFile"}{/crmRegion}');
    // stable ordering: alphabetical by (snippet.weight,snippet.name)
    $expected = ""
      . "<script type=\"text/javascript\" src=\"http://core-app/foo%20bar.js?r=resTesten_US\">\n</script>\n"
      . "<script type=\"text/javascript\" src=\"http://ext-dir/com.example.ext/foo%20bar.js?r=resTesten_US\">\n</script>\n";
    $this->assertEquals($expected, $actual);
  }

  /**
   * Ensure that adding a script URL creates expected markup.
   */
  public function testAddScriptURL(): void {
    $this->res
      ->addScriptUrl('/whiz/foo%20bar.js', 0, 'testAddScriptURL')
      // extra
      ->addScriptUrl('/whiz/foo%20bar.js', 0, 'testAddScriptURL')
      ->addScriptUrl('/whizbang/foo%20bar.js', 0, 'testAddScriptURL');

    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{crmRegion name="testAddScriptURL"}{/crmRegion}');
    // stable ordering: alphabetical by (snippet.weight,snippet.name)
    $expected = ""
      . "<script type=\"text/javascript\" src=\"/whiz/foo%20bar.js\">\n</script>\n"
      . "<script type=\"text/javascript\" src=\"/whizbang/foo%20bar.js\">\n</script>\n";
    $this->assertEquals($expected, $actual);
  }

  public function testAddScript(): void {
    $this->res
      ->addScript('alert("hi");', 0, 'testAddScript')
      ->addScript('alert("there");', 0, 'testAddScript');

    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{crmRegion name="testAddScript"}{/crmRegion}');
    $expected = ""
      . "<script type=\"text/javascript\">\nalert(\"hi\");\n</script>\n"
      . "<script type=\"text/javascript\">\nalert(\"there\");\n</script>\n";
    $this->assertEquals($expected, $actual);
  }

  public function testAddVars(): void {
    $this->res
      ->addVars('food', ['fruit' => ['mine' => 'apple', 'ours' => 'banana']])
      ->addVars('food', ['fruit' => ['mine' => 'new apple', 'yours' => 'orange']]);
    $this->assertTreeEquals(
      [
        'vars' => [
          'food' => [
            'fruit' => [
              'yours' => 'orange',
              'mine' => 'new apple',
              'ours' => 'banana',
            ],
          ],
        ],
      ],
      $this->res->getSettings('html-header')
    );
  }

  public function testAddSetting(): void {
    $this->res
      ->addSetting(['fruit' => ['mine' => 'apple']])
      ->addSetting(['fruit' => ['yours' => 'orange']]);
    $this->assertTreeEquals(
      ['fruit' => ['yours' => 'orange', 'mine' => 'apple']],
      $this->res->getSettings('html-header')
    );
    $actual = CRM_Core_Region::instance('html-header')->render('');
    $expected = '})(' . json_encode(['fruit' => ['yours' => 'orange', 'mine' => 'apple']]) . ')';
    $this->assertTrue(strpos($actual, $expected) !== FALSE);
  }

  public function testAddSettingToBillingBlock(): void {
    $this->res
      ->addSetting(['cheese' => ['cheddar' => 'yellow']], 'billing-block')
      ->addSetting(['cheese' => ['edam' => 'red']], 'billing-block');
    $this->assertTreeEquals(
      ['cheese' => ['edam' => 'red', 'cheddar' => 'yellow']],
      $this->res->getSettings('billing-block')
    );
    $actual = CRM_Core_Region::instance('billing-block')->render('');
    $expected = '})(' . json_encode(['cheese' => ['edam' => 'red', 'cheddar' => 'yellow']]) . ')';
    $this->assertTrue(strpos($actual, $expected) !== FALSE);
  }

  public function testAddSettingHook(): void {
    $test = $this;
    Civi::dispatcher()->addListener('hook_civicrm_alterResourceSettings', function($event) use ($test) {
      $test->assertEquals('apple', $event->data['fruit']['mine']);
      $event->data['fruit']['mine'] = 'banana';
    });
    $this->res->addSetting(['fruit' => ['mine' => 'apple']]);
    $settings = $this->res->getSettings('html-header');
    $this->assertTreeEquals(['fruit' => ['mine' => 'banana']], $settings);
  }

  public function testAddSettingFactory(): void {
    $this->res->addSettingsFactory(function () {
      return ['fruit' => ['yours' => 'orange']];
    });
    $this->res->addSettingsFactory(function () {
      return ['fruit' => ['mine' => 'apple']];
    });

    $actual = $this->res->getSettings('html-header');
    $expected = ['fruit' => ['yours' => 'orange', 'mine' => 'apple']];
    $this->assertTreeEquals($expected, $actual);
  }

  public function testAddSettingAndSettingFactory(): void {
    $this->res->addSetting(['fruit' => ['mine' => 'apple']]);

    $muckableValue = ['fruit' => ['yours' => 'orange', 'theirs' => 'apricot']];
    $this->res->addSettingsFactory(function () use (&$muckableValue) {
      return $muckableValue;
    });
    $actual = $this->res->getSettings('html-header');
    $expected = ['fruit' => ['mine' => 'apple', 'yours' => 'orange', 'theirs' => 'apricot']];
    $this->assertTreeEquals($expected, $actual);

    // note: the setting is not fixed based on what the factory returns when registered; it's based
    // on what the factory returns when getSettings is called
    $muckableValue = ['fruit' => ['yours' => 'banana']];
    $actual = $this->res->getSettings('html-header');
    $expected = ['fruit' => ['mine' => 'apple', 'yours' => 'banana']];
    $this->assertTreeEquals($expected, $actual);
  }

  public function testCrmJS(): void {
    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{crmScript ext="com.example.ext" file="foo%20bar.js" region="testCrmJS"}');
    $this->assertEquals('', $actual);

    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{crmScript url="/whiz/foo%20bar.js" region="testCrmJS" weight=1}');
    $this->assertEquals('', $actual);

    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{crmRegion name="testCrmJS"}{/crmRegion}');
    // stable ordering: alphabetical by (snippet.weight,snippet.name)
    $expected = ""
      . "<script type=\"text/javascript\" src=\"http://ext-dir/com.example.ext/foo%20bar.js?r=resTesten_US\">\n</script>\n"
      . "<script type=\"text/javascript\" src=\"/whiz/foo%20bar.js\">\n</script>\n";
    $this->assertEquals($expected, $actual);
  }

  public function testAddStyleFile(): void {
    $this->res
      ->addStyleFile('com.example.ext', 'foo%20bar.css', 0, 'testAddStyleFile')
      // extra
      ->addStyleFile('com.example.ext', 'foo%20bar.css', 0, 'testAddStyleFile')
      ->addStyleFile('civicrm', 'foo%20bar.css', 0, 'testAddStyleFile');

    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{crmRegion name="testAddStyleFile"}{/crmRegion}');
    // stable ordering: alphabetical by (snippet.weight,snippet.name)
    $expected = ""
      . "<link href=\"http://core-app/foo%20bar.css?r=resTesten_US\" rel=\"stylesheet\" type=\"text/css\"/>\n"
      . "<link href=\"http://ext-dir/com.example.ext/foo%20bar.css?r=resTesten_US\" rel=\"stylesheet\" type=\"text/css\"/>\n";
    $this->assertEquals($expected, $actual);
  }

  public function testAddStyleURL(): void {
    $this->res
      ->addStyleUrl('/whiz/foo%20bar.css', 0, 'testAddStyleURL')
      // extra
      ->addStyleUrl('/whiz/foo%20bar.css', 0, 'testAddStyleURL')
      ->addStyleUrl('/whizbang/foo%20bar.css', 0, 'testAddStyleURL');

    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{crmRegion name="testAddStyleURL"}{/crmRegion}');
    // stable ordering: alphabetical by (snippet.weight,snippet.name)
    $expected = ""
      . "<link href=\"/whiz/foo%20bar.css\" rel=\"stylesheet\" type=\"text/css\"/>\n"
      . "<link href=\"/whizbang/foo%20bar.css\" rel=\"stylesheet\" type=\"text/css\"/>\n";
    $this->assertEquals($expected, $actual);
  }

  public function testAddStyle(): void {
    $this->res
      ->addStyle('body { background: black; }', 0, 'testAddStyle')
      ->addStyle('body { text-color: black; }', 0, 'testAddStyle');

    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{crmRegion name="testAddStyle"}{/crmRegion}');
    $expected = ""
      . "<style type=\"text/css\">\nbody { background: black; }\n</style>\n"
      . "<style type=\"text/css\">\nbody { text-color: black; }\n</style>\n";
    $this->assertEquals($expected, $actual);
  }

  public function testCrmCSS(): void {
    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{crmStyle ext="com.example.ext" file="foo%20bar.css" region=testCrmCSS}');
    $this->assertEquals('', $actual);

    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{crmStyle url="/whiz/foo%20bar.css" region="testCrmCSS" weight=1}');
    $this->assertEquals('', $actual);

    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{crmRegion name="testCrmCSS"}{/crmRegion}');
    // stable ordering: alphabetical by (snippet.weight,snippet.name)
    $expected = ""
      . "<link href=\"http://ext-dir/com.example.ext/foo%20bar.css?r=resTesten_US\" rel=\"stylesheet\" type=\"text/css\"/>\n"
      . "<link href=\"/whiz/foo%20bar.css\" rel=\"stylesheet\" type=\"text/css\"/>\n";
    $this->assertEquals($expected, $actual);
  }

  public function testGetURL(): void {
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

  public function testCrmResURL(): void {
    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{crmResURL ext="com.example.ext" file="foo%20bar.png"}');
    $this->assertEquals('http://ext-dir/com.example.ext/foo%20bar.png', $actual);

    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{crmResURL ext="com.example.ext" file="foo%20bar.png" addCacheCode=1}');
    $this->assertEquals('http://ext-dir/com.example.ext/foo%20bar.png?r=resTesten_US', $actual);

    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{crmResURL ext="com.example.ext"}');
    $this->assertEquals('http://ext-dir/com.example.ext/', $actual);

    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{crmResURL expr="[civicrm.root]/foo"}');
    $this->assertEquals(Civi::paths()->getUrl('[civicrm.root]/foo'), $actual);
  }

  public function testGlob(): void {
    $this->assertEquals(
      ['info.xml'],
      $this->res->glob('com.example.ext', 'info.xml')
    );
    $this->assertEquals(
      ['js/example.js'],
      $this->res->glob('com.example.ext', 'js/*.js')
    );
    $this->assertEquals(
      ['js/example.js'],
      $this->res->glob('com.example.ext', ['js/*.js'])
    );
  }

  /**
   * @dataProvider ajaxModeData
   */
  public function testIsAjaxMode($query, $result) {
    $_REQUEST = $_GET = $query;
    $this->assertEquals($result, CRM_Core_Resources::isAjaxMode());
  }

  public function ajaxModeData(): array {
    return [
      [['q' => 'civicrm/ajax/foo'], TRUE],
      [['q' => 'civicrm/case/ajax/foo'], TRUE],
      [['q' => 'civicrm/angularprofiles/template'], TRUE],
      [['q' => 'civicrm/asset/builder'], TRUE],
      [['q' => 'civicrm/test/page'], FALSE],
      [['q' => 'civicrm/test/page', 'snippet' => 'json'], TRUE],
      [['q' => 'civicrm/test/page', 'snippet' => 'foo'], FALSE],
    ];
  }

  /**
   * @return CRM_Extension_Mapper
   */
  private function createMapper(): CRM_Extension_Mapper {
    $basedir = rtrim($this->createTempDir('ext-'), '/');
    mkdir("$basedir/com.example.ext");
    mkdir("$basedir/com.example.ext/js");
    file_put_contents("$basedir/com.example.ext/info.xml", "<extension key='com.example.ext' type='report'><file>oddball</file></extension>");
    file_put_contents("$basedir/com.example.ext/js/example.js", "alert('Boo!');");
    // not needed for now // file_put_contents("$basedir/weird/bar/oddball.php", "<?php\n");
    $c = new CRM_Extension_Container_Basic($basedir, 'http://ext-dir', NULL, NULL);
    $mapper = new CRM_Extension_Mapper($c, NULL, NULL, '/pathto/civicrm', 'http://core-app');
    return $mapper;
  }

  /**
   * @param string $url
   * @param string $expected
   *
   * @dataProvider urlForCacheCodeProvider
   */
  public function testAddingCacheCode(string $url, string $expected): void {
    $resources = CRM_Core_Resources::singleton();
    $resources->setCacheCode($this->cacheBusterString);
    $this->assertEquals($expected, $resources->addCacheCode($url));
  }

  /**
   * @return array
   */
  public function urlForCacheCodeProvider(): array {
    $cacheBusterString = Civi::resources()
      ->setCacheCode($this->cacheBusterString)
      ->getCacheCode();
    return [
      [
        'http://www.civicrm.org',
        'http://www.civicrm.org?r=' . $cacheBusterString,
      ],
      [
        'www.civicrm.org/custom.css?foo=bar',
        'www.civicrm.org/custom.css?foo=bar&r=' . $cacheBusterString,
      ],
      [
        'civicrm.org/custom.css?car=blue&foo=bar',
        'civicrm.org/custom.css?car=blue&foo=bar&r=' . $cacheBusterString,
      ],
    ];
  }

  /**
   * return array
   */
  public function urlsToCheckIfFullyFormed(): array {
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
  public function testIsFullyFormedUrl($url, $expected): void {
    $this->assertEquals($expected, CRM_Core_Resources::isFullyFormedUrl($url));
  }

  /**
   * Test for hook_civicrm_entityRefFilters().
   *
   */
  public function testEntityRefFiltersHook(): void {
    CRM_Utils_Hook_UnitTests::singleton()->setHook('civicrm_entityRefFilters', [$this, 'entityRefFilters']);
    $data = Invasive::call(['CRM_Core_Resources', 'getEntityRefMetadata']);
    $this->assertEquals(count($data['links']['Contact']), 4);
    $this->assertEquals(!empty($data['links']['Contact']['new_staff']), TRUE);
  }

  /**
   * @param array $filters
   * @param array $links
   */
  public function entityRefFilters(&$filters, &$links) {
    $links['Contact']['new_staff'] = [
      'label' => ts('New Staff'),
      'url' => '/civicrm/profile/create&reset=1&context=dialog&gid=5',
      'type' => 'Individual',
      'icon' => 'fa-user',
    ];
  }

}
