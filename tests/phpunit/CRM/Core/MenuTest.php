<?php

/**
 * Class CRM_Core_MenuTest
 * @group headless
 */
class CRM_Core_MenuTest extends CiviUnitTestCase {

  public function testReadXML() {
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

  public function testReadXML_IDS() {
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
  public function testModuleData() {
    CRM_Core_Menu::store(TRUE);
    $item = CRM_Core_Menu::get('civicrm/case');
    $this->assertFalse(isset($item['ids_arguments']['exceptions']));
    $this->assertFalse(isset($item['whimsy']));

    CRM_Utils_Hook::singleton()->setHook('civicrm_alterMenu', function(&$items) {
      $items['civicrm/case']['ids_arguments']['exceptions'][] = 'foobar';
      $items['civicrm/case']['whimsy'] = 'godliness';
    });

    CRM_Core_Menu::store(TRUE);
    $item = CRM_Core_Menu::get('civicrm/case');
    $this->assertTrue(in_array('foobar', $item['ids_arguments']['exceptions']));
    $this->assertEquals('godliness', $item['whimsy']);
  }

  /**
   * @return array
   */
  public function pathArguments() {
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

}
