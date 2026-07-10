<?php

/**
 * Class CRM_Core_MenuTest
 * @group headless
 */
class CRM_Core_MenuTest extends CiviUnitTestCase {

  public function testReadXML(): void {
    $xmlString = '<?xml version="1.0" encoding="iso-8859-1" ?>
    <menu>
      <item>
         <path>civicrm/foo/bar</path>
         <title>Foo Bar</title>
         <desc>The foo is one with the bar.</desc>
         <page_callback>CRM_Foo_Page_Bar</page_callback>
         <adminGroup>Customize Data and Screens</adminGroup>
         <weight>10</weight>
      </item>
    </menu>
    ';
    $xml = simplexml_load_string($xmlString);
    $menu = [];
    CRM_Core_Menu::readXML($xml, $menu);
    $this->assertTrue(isset($menu['civicrm/foo/bar']));
    $this->assertEquals('Foo Bar', $menu['civicrm/foo/bar']['title']);
    $this->assertEquals('The foo is one with the bar.', $menu['civicrm/foo/bar']['desc']);
    $this->assertEquals('CRM_Foo_Page_Bar', $menu['civicrm/foo/bar']['page_callback']);
    $this->assertEquals('Customize Data and Screens', $menu['civicrm/foo/bar']['adminGroup']);
    $this->assertEquals('10', $menu['civicrm/foo/bar']['weight']);
    $this->assertTrue(!isset($menu['civicrm/foo/bar']['ids_arguments']));
  }

  public function testReadXML_IDS(): void {
    $xmlString = '<?xml version="1.0" encoding="iso-8859-1" ?>
    <menu>
      <item>
         <path>civicrm/foo/bar</path>
         <title>Foo Bar</title>
         <ids_arguments>
          <json>alpha</json>
          <json>beta</json>
          <exception>gamma</exception>
        </ids_arguments>
      </item>
    </menu>
    ';
    $xml = simplexml_load_string($xmlString);
    $menu = [];
    CRM_Core_Menu::readXML($xml, $menu);
    $this->assertTrue(isset($menu['civicrm/foo/bar']));
    $this->assertEquals('Foo Bar', $menu['civicrm/foo/bar']['title']);
    $this->assertEquals(['alpha', 'beta'], $menu['civicrm/foo/bar']['ids_arguments']['json']);
    $this->assertEquals(['gamma'], $menu['civicrm/foo/bar']['ids_arguments']['exceptions']);
    $this->assertEquals([], $menu['civicrm/foo/bar']['ids_arguments']['html']);

    $idsConfig = CRM_Core_IDS::createRouteConfig($menu['civicrm/foo/bar']);
    // XML
    $this->assertTrue(in_array('alpha', $idsConfig['General']['json']));
    // XML
    $this->assertTrue(in_array('beta', $idsConfig['General']['json']));
    // XML
    $this->assertTrue(in_array('gamma', $idsConfig['General']['exceptions']));
    // Inherited
    $this->assertTrue(in_array('thankyou_text', $idsConfig['General']['exceptions']));
  }

  /**
   * Check that novel data elements in the menu are correctly
   * stored and loaded.
   */
  public function testModuleData(): void {
    CRM_Core_Menu::clear();
    $item = CRM_Core_Menu::get('civicrm/case');
    $this->assertFalse(isset($item['ids_arguments']['exceptions']));
    $this->assertFalse(isset($item['whimsy']));

    CRM_Utils_Hook::singleton()->setHook('civicrm_alterMenu', function(&$items) {
      $items['civicrm/case']['ids_arguments']['exceptions'][] = 'foobar';
      $items['civicrm/case']['whimsy'] = 'godliness';
    });

    CRM_Core_Menu::clear();
    $item = CRM_Core_Menu::get('civicrm/case');
    $this->assertTrue(in_array('foobar', $item['ids_arguments']['exceptions']));
    $this->assertEquals('godliness', $item['whimsy']);
  }

  /**
   * @return array
   */
  public static function pathArguments() {
    // array(0 => string $input, 1 => array $expectedOutput)
    $cases = [];
    //$cases[] = array(NULL, array());
    //$cases[] = array('', array());
    //$cases[] = array('freestanding', array('freestanding' => NULL));
    $cases[] = ['addSequence=1', ['addSequence' => '1']];
    $cases[] = ['attachUpload=1', ['attachUpload' => '1']];
    $cases[] = ['mode=256', ['mode' => '256']];
    $cases[] = [
      'mode=256,addSequence=1,attachUpload=1',
      ['mode' => '256', 'addSequence' => '1', 'attachUpload' => 1],
    ];
    $cases[] = [
      'mode=256,urlToSession=a:b:c:d',
      [
        'mode' => '256',
        'urlToSession' => [
          ['urlVar' => 'a', 'sessionVar' => 'b', 'type' => 'c', 'default' => 'd'],
        ],
      ],
    ];
    $cases[] = [
      'mode=256,urlToSession=a:b:c:d;z:y:x:w',
      [
        'mode' => '256',
        'urlToSession' => [
          ['urlVar' => 'a', 'sessionVar' => 'b', 'type' => 'c', 'default' => 'd'],
          ['urlVar' => 'z', 'sessionVar' => 'y', 'type' => 'x', 'default' => 'w'],
        ],
      ],
    ];
    $cases[] = ['url=whiz!;.:#=%/|+bang?', ['url' => 'whiz!;.:#=%/|+bang?']];
    return $cases;
  }

  /**
   * @param $inputString
   * @param $expectedArray
   * @dataProvider pathArguments
   */
  public function testGetArrayForPathArgs($inputString, $expectedArray) {
    $actual = CRM_Core_Menu::getArrayForPathArgs($inputString);
    $this->assertEquals($expectedArray, $actual);
  }

  public function testIsPublicRoute(): void {
    $this->assertEquals(FALSE, \CRM_Core_Menu::isPublicRoute('civicrm/contribute'));
    $this->assertEquals(TRUE, \CRM_Core_Menu::isPublicRoute('civicrm/contribute/transact'));
  }

  /**
   * Regression guard for the fix: both civicrm_menu writers, store() (TRUNCATE +
   * repopulate) and clear() (TRUNCATE), run under the data.core.menu lock and
   * release it. That is what stops two concurrent rebuilds from colliding on
   * the (path, domain_id) unique key or leaving a partial table. dev/core#6621.
   *
   * This asserts the locking is wired up, not the race itself: reproducing the
   * collision needs two rebuilds interleaving on separate DB connections, and
   * GET_LOCK() is per-connection, so this single-connection test cannot both
   * hold and contend for the lock. The rebuild's lock hold is observed from the
   * civicrm_alterMenu hook, which fires after the TRUNCATE and before the table
   * is repopulated - the exact window the race corrupts - so the probe checks
   * both that the lock is held and that civicrm_menu is empty at that point
   * (isFree() reports a lock held by the current connection as in-use).
   */
  public function testRebuildRunsUnderLock(): void {
    $isFree = fn() => (int) Civi::lockManager()->create('data.core.menu')->isFree();

    $this->assertSame(1, $isFree(), 'the rebuild lock should be free before store()');

    $freeDuringRebuild = NULL;
    $menuRowsDuringRebuild = NULL;
    CRM_Utils_Hook::singleton()->setHook('civicrm_alterMenu', function (&$items) use ($isFree, &$freeDuringRebuild, &$menuRowsDuringRebuild) {
      $freeDuringRebuild = $isFree();
      $menuRowsDuringRebuild = (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_menu');
    });

    CRM_Core_Menu::store();

    $this->assertSame(0, $freeDuringRebuild, 'store() should hold the rebuild lock while repopulating civicrm_menu');
    $this->assertSame(0, $menuRowsDuringRebuild, 'the lock should be held while civicrm_menu is empty mid-rebuild (the window the race corrupts)');
    $this->assertSame(1, $isFree(), 'store() should release the rebuild lock when finished');

    // clear() is the other writer; it takes the same lock and must release it too.
    CRM_Core_Menu::clear();
    $this->assertSame(1, $isFree(), 'clear() should release the rebuild lock');

    // Leave the route table populated for subsequent tests.
    CRM_Core_Menu::store();
  }

}
