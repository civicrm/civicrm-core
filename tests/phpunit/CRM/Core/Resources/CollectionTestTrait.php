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
 * Class CRM_Core_Resources_CollectionTestTrait
 *
 * If you have a concrete class which uses CollectionTrait, this helper
 * should make it easier to write a unit-test.
 */
trait CRM_Core_Resources_CollectionTestTrait {

  /**
   * @return \CRM_Core_Resources_CollectionInterface
   */
  abstract public function createEmptyCollection();

  public function getSnippetExamples() {
    $allowsMarkup = ($this instanceof CRM_Core_RegionTest);
    $defaultCount = ($this instanceof CRM_Core_RegionTest) ? 1 : 0;
    $es = [];

    /**
     * Private helper to generate several similar examples.
     * @param array $callbacks
     *   List of callbacks which can be used to add a resource to the bundle.
     * @param array $expect
     *   The fully formed resource that should be created as a result.
     */
    $addCases = function($callbacks, $expect) use (&$es) {
      foreach ($callbacks as $key => $callback) {
        if (isset($es[$key])) {
          throw new \RuntimeException("Cannot prepare examples: Case \"$key\" defined twice");
        }
        $es[$key] = [$callback, $expect];
      }
    };

    $addCases(
      // List of equivalent method calls
      [
        'add(scriptUrl): dfl' => ['add', ['scriptUrl' => 'http://example.com/foo.js']],
        'addScriptUrl(): dfl' => ['addScriptUrl', 'http://example.com/foo.js'],
        'addScriptUrl(): pos dfl-wgt' => ['addScriptUrl', 'http://example.com/foo.js', 1],
      ],
      // Fully-formed result expected for this call
      [
        'name' => 'http://example.com/foo.js',
        'disabled' => FALSE,
        'weight' => 1,
        'type' => 'scriptUrl',
        'scriptUrl' => 'http://example.com/foo.js',
      ]
    );

    // For historical reasons, the `add(scriptUrl)` and `addScriptUrl()` calls
    // differ very slightly in how data is ordered.
    $addCases(
      ['add(scriptUrl): dfl: sortId' => ['add', ['scriptUrl' => 'http://example.com/foo.js']]],
      ['sortId' => ($this instanceof CRM_Core_RegionTest ? 2 : 1)]
    );
    $addCases(
      ['addScriptUrl(): dfl: sortId' => ['addScriptUrl', 'http://example.com/foo.js']],
      ['sortId' => 'http://example.com/foo.js']
    );

    $addCases(
      [
        'add(scriptUrl): wgt' => ['add', ['scriptUrl' => 'http://example.com/foo.js', 'weight' => 100]],
        'addScriptUrl(): arr wgt' => ['addScriptUrl', 'http://example.com/foo.js', ['weight' => 100]],
        'addScriptUrl(): pos wgt' => ['addScriptUrl', 'http://example.com/foo.js', 100],
      ],
      [
        'name' => 'http://example.com/foo.js',
        'disabled' => FALSE,
        'weight' => 100,
        'type' => 'scriptUrl',
        'scriptUrl' => 'http://example.com/foo.js',
      ]
    );

    $addCases(
      [
        'add(styleUrl)' => ['add', ['styleUrl' => 'http://example.com/foo.css']],
        'addStyleUrl()' => ['addStyleUrl', 'http://example.com/foo.css'],
      ],
      [
        'name' => 'http://example.com/foo.css',
        'disabled' => FALSE,
        'weight' => 1,
        'type' => 'styleUrl',
        'styleUrl' => 'http://example.com/foo.css',
      ]
    );

    $addCases(
      [
        'add(styleFile)' => ['add', ['styleFile' => ['civicrm', 'css/civicrm.css']]],
        'addStyleFile()' => ['addStyleFile', 'civicrm', 'css/civicrm.css'],
      ],
      [
        'name' => 'civicrm:css/civicrm.css',
        'disabled' => FALSE,
        'weight' => 1,
        'type' => 'styleFile',
        'styleFile' => ['civicrm', 'css/civicrm.css'],
        'styleFileUrls' => [
          Civi::paths()->getUrl('[civicrm.root]/css/civicrm.css?r=XXXX'),
        ],
      ]
    );

    $basicFooJs = [
      'name' => 'civicrm:js/foo.js',
      'disabled' => FALSE,
      'type' => 'scriptFile',
      'scriptFile' => ['civicrm', 'js/foo.js'],
      'scriptFileUrls' => [
        Civi::paths()->getUrl('[civicrm.root]/js/foo.js?r=XXXX'),
      ],
    ];

    $addCases(
      [
        'add(scriptFile): dfl' => ['add', ['scriptFile' => ['civicrm', 'js/foo.js']]],
        'addScriptFile(): dfl' => ['addScriptFile', 'civicrm', 'js/foo.js'],
        'addScriptFile(): dfl pos-wgt' => ['addScriptFile', 'civicrm', 'js/foo.js', 1],
      ],
      $basicFooJs + ['weight' => 1, 'translate' => TRUE]
    );

    $addCases(
      [
        'add(scriptFile): wgt-rgn' => ['add', ['scriptFile' => ['civicrm', 'js/foo.js'], 'weight' => 100, 'region' => 'zoo']],
        'addScriptFile(): arr wgt-rgn' => ['addScriptFile', 'civicrm', 'js/foo.js', ['weight' => 100, 'region' => 'zoo']],
        'addScriptFile(): pos wgt-rgn' => ['addScriptFile', 'civicrm', 'js/foo.js', 100, 'zoo'],
        'addScriptFile(): pos wgt-rgn-trn' => ['addScriptFile', 'civicrm', 'js/foo.js', 100, 'zoo', TRUE],
      ],
      $basicFooJs + ['weight' => 100, 'region' => 'zoo', 'translate' => TRUE]
    );

    $addCases(
      [
        'add(scriptFile): wgt-rgn-trnOff' => ['add', ['scriptFile' => ['civicrm', 'js/foo.js'], 'weight' => -200, 'region' => 'zoo', 'translate' => FALSE]],
        'addScriptFile(): arr wgt-rgn-trnOff' => ['addScriptFile', 'civicrm', 'js/foo.js', ['weight' => -200, 'region' => 'zoo', 'translate' => FALSE]],
        'addScriptFile(): pos wgt-rgn-trnOff' => ['addScriptFile', 'civicrm', 'js/foo.js', -200, 'zoo', FALSE],
      ],
      $basicFooJs + ['weight' => -200, 'region' => 'zoo', 'translate' => FALSE]
    );

    $addCases(
      [
        'add(script)' => ['add', ['script' => 'window.alert("Boo!");']],
        'addScript()' => ['addScript', 'window.alert("Boo!");'],
      ],
      [
        'name' => 1 + $defaultCount,
        'disabled' => FALSE,
        'weight' => 1,
        'type' => 'script',
        'script' => 'window.alert("Boo!");',
      ]
    );

    if ($allowsMarkup) {
      $addCases(
        [
          'add(markup)' => ['add', ['markup' => '<p>HELLO</p>']],
          'addMarkup()' => ['addMarkup', '<p>HELLO</p>'],
        ],
        [
          'name' => 1 + $defaultCount,
          'disabled' => FALSE,
          'weight' => 1,
          'type' => 'markup',
          'markup' => '<p>HELLO</p>',
        ]
      );
    }

    return $es;
  }

  /**
   * Add a snippet with some method and ensure that it's actually added.
   *
   * @param array $callbackArgs
   *   Ex: ['addScriptUrl', 'http://example.com/foo.js'].
   * @param array $expectSnippet
   * @dataProvider getSnippetExamples
   */
  public function testAddDefaults($callbackArgs, $expectSnippet) {
    if ($callbackArgs === NULL) {
      return;
    }
    $method = array_shift($callbackArgs);

    $b = $this->createEmptyCollection();
    $result = call_user_func_array([$b, $method], $callbackArgs);

    // Check direct result.
    if ($method === 'add') {
      $this->assertSameSnippet($expectSnippet, $result);
    }
    else {
      $this->assertTrue($b === $result);
    }

    // Check side-effect of registering snippet.
    $count = 0;
    foreach ($b->getAll() as $getSnippet) {
      $this->assertSameSnippet($expectSnippet, $getSnippet, 'getAll() method should return snippet with properly computed defaults');
      $count++;
    }
    $this->assertEquals(1, $count, 'Expect one registered snippet');
  }

  /**
   * Create a few resources with aliases. Use a mix of reads+writes on both the
   * canonical names and aliased names.
   */
  public function testAliases() {
    $b = $this->createEmptyCollection();
    $b->add([
      'styleUrl' => 'https://example.com/foo.css',
      'name' => 'foo',
      'aliases' => ['bar', 'borg'],
    ]);
    $b->add([
      'scriptUrl' => 'https://example.com/whiz.js',
      'name' => 'whiz',
      'aliases' => 'bang',
    ]);

    $this->assertEquals('foo', $b->get('foo')['name']);
    $this->assertEquals('foo', $b->get('bar')['name']);
    $this->assertEquals('foo', $b->get('borg')['name']);
    $this->assertEquals('whiz', $b->get('whiz')['name']);
    $this->assertEquals('whiz', $b->get('bang')['name']);
    $this->assertEquals(NULL, $b->get('snafu'));

    // Go back+forth, updating with one name then reading with the other.

    $b->get('borg')['borgify'] = TRUE;
    $this->assertEquals(TRUE, $b->get('foo')['borgify']);

    $b->get('foo')['d'] = 'ie';
    $this->assertEquals('ie', $b->get('borg')['d']);

    $b->update('bang', ['b52' => 'love shack']);
    $this->assertEquals('love shack', $b->get('whiz')['b52']);

    $b->update('whiz', ['golly' => 'gee']);
    $this->assertEquals('gee', $b->get('bang')['golly']);
  }

  /**
   * Add some items to a bundle - then clear() all of them.
   */
  public function testClear() {
    $b = $this->createEmptyCollection();
    $b->addScriptUrl('http://example.com/child.js');
    $this->assertEquals(1, count($b->getAll()));
    $b->addStyleUrl('http://example.com/child.css');
    $this->assertEquals(2, count($b->getAll()));

    $b->clear();
    $this->assertEquals(0, count($b->getAll()));

    $b->addScriptUrl('http://example.com/encore.js');
    $this->assertEquals(1, count($b->getAll()));
  }

  /**
   * Create two bundles (parent, child) - and merge the child into the parent.
   */
  public function testMerge() {
    $child = $this->createEmptyCollection();
    $parent = $this->createEmptyCollection();

    $child->addScriptUrl('http://example.com/child.js');
    $child->addStyleUrl('http://example.com/child.css');
    $child->addSetting(['child' => ['schoolbooks']]);
    $this->assertCount(3, $child->getAll());

    $parent->addScriptUrl('http://example.com/parent.js');
    $parent->addStyleUrl('http://example.com/parent.css');
    $parent->addSetting(['parent' => ['groceries']]);
    $this->assertCount(3, $parent->getAll());

    $parent->merge($child->getAll());
    $this->assertCount(5, $parent->getAll());

    $expectSettings = [
      'child' => ['schoolbooks'],
      'parent' => ['groceries'],
    ];
    $this->assertEquals($expectSettings, $parent->getSettings());
    $this->assertEquals('http://example.com/child.js', $parent->get('http://example.com/child.js')['scriptUrl']);
    $this->assertEquals('http://example.com/child.css', $parent->get('http://example.com/child.css')['styleUrl']);
    $this->assertEquals('http://example.com/parent.js', $parent->get('http://example.com/parent.js')['scriptUrl']);
    $this->assertEquals('http://example.com/parent.css', $parent->get('http://example.com/parent.css')['styleUrl']);
  }

  public function testAddBundle() {
    $part1 = $this->createEmptyCollection();
    $part2 = $this->createEmptyCollection();
    $part1->add(['script' => 'doPart1()']);
    $part1->add(['script' => 'doPart2()']);
    $expectScripts = ['doPart1()', 'doPart2()'];

    $sumA = $this->createEmptyCollection()->addBundle($part1)->addBundle($part2);
    $this->assertEquals($expectScripts, array_column($sumA->getAll(), 'script'));

    $sumB = $this->createEmptyCollection()->addBundle([$part1, $part2]);
    $this->assertEquals($expectScripts, array_column($sumB->getAll(), 'script'));
  }

  /**
   * Functions like `addScriptFile()` accept positional arguments
   * in the order ($weight, $region, $translate).
   */
  public function testStandardSplatParser() {
    $parse = function(...$options) {
      return CRM_Core_Resources_CollectionAdderTrait::mergeStandardOptions($options, []);
    };
    $this->assertEquals([], $parse());
    $this->assertEquals(['weight' => '100'], $parse('100'));
    $this->assertEquals(['weight' => '100', 'region' => 'footer'], $parse('100', 'footer'));
    $this->assertEquals(['weight' => '100', 'region' => 'footer', 'translate' => FALSE], $parse('100', 'footer', FALSE));
    $this->assertEquals(['weight' => 200], $parse(['weight' => 200]));
    $this->assertEquals(['region' => 'shakaneigh'], $parse(['region' => 'shakaneigh']));
    $this->assertEquals(['frobnicate' => TRUE], $parse(['frobnicate' => TRUE]));
  }

  /**
   * Functions like `addVars()` accept positional arguments
   * in the order ($region).
   */
  public function testSettingsSplatParser() {
    $parse = function(...$options) {
      return CRM_Core_Resources_CollectionAdderTrait::mergeSettingOptions($options, []);
    };
    $this->assertEquals([], $parse());
    $this->assertEquals(['region' => 'oakaneigh'], $parse('oakaneigh'));
    $this->assertEquals(['region' => 'oakaneigh'], $parse(['region' => 'oakaneigh']));
    $this->assertEquals(['frobnicate' => TRUE], $parse(['frobnicate' => TRUE]));
  }

  /**
   * Assert that two snippets are equivalent.
   *
   * @param array $expect
   * @param array $actual
   * @param string $message
   */
  public function assertSameSnippet($expect, $actual, $message = '') {
    $normalizeUrl = function($url) {
      // If there is a cache code (?r=XXXX), then replace random value with constant XXXX.
      return preg_replace(';([\?\&]r=)([a-zA-Z0-9_\-]+);', '\1XXXX', $url);
    };

    $normalizeSnippet = function ($snippet) use ($normalizeUrl) {
      // Any URLs in 'styleFileUrls' or '
      foreach (['styleUrl', 'scriptUrl'] as $field) {
        if (isset($snippet[$field])) {
          $snippet[$field] = $normalizeUrl($snippet[$field]);
        }
      }
      foreach (['styleFileUrls', 'scriptFileUrls'] as $field) {
        if (isset($snippet[$field])) {
          $snippet[$field] = array_map($normalizeUrl, $snippet[$field]);
        }
      }
      ksort($snippet);
      return $snippet;
    };

    $expect = $normalizeSnippet($expect);
    $actual = $normalizeSnippet($actual);

    foreach ($expect as $expectKey => $expectValue) {
      if ($expectValue === '*') {
        $this->assertTrue(!empty($actual[$expectKey]));
      }
      else {
        $this->assertEquals($expectValue, $actual[$expectKey]);
      }
    }
  }

}
