<?php

/**
 * Class CRM_Core_MenuTest
 */
class CRM_Core_MenuTest extends CiviUnitTestCase {

  /**
   * @return array
   */
  public function pathArguments() {
    $cases = array(); // array(0 => string $input, 1 => array $expectedOutput)
    //$cases[] = array(NULL, array());
    //$cases[] = array('', array());
    //$cases[] = array('freestanding', array('freestanding' => NULL));
    $cases[] = array('addSequence=1', array('addSequence' => '1'));
    $cases[] = array('attachUpload=1', array('attachUpload' => '1'));
    $cases[] = array('mode=256', array('mode' => '256'));
    $cases[] = array(
      'mode=256,addSequence=1,attachUpload=1',
      array('mode' => '256', 'addSequence' => '1', 'attachUpload' => 1),
    );
    $cases[] = array(
      'mode=256,urlToSession=a:b:c:d',
      array(
        'mode' => '256',
        'urlToSession' => array(
          array('urlVar' => 'a', 'sessionVar' => 'b', 'type' => 'c', 'default' => 'd'),
        ),
      ),
    );
    $cases[] = array(
      'mode=256,urlToSession=a:b:c:d;z:y:x:w',
      array(
        'mode' => '256',
        'urlToSession' => array(
          array('urlVar' => 'a', 'sessionVar' => 'b', 'type' => 'c', 'default' => 'd'),
          array('urlVar' => 'z', 'sessionVar' => 'y', 'type' => 'x', 'default' => 'w'),
        ),
      ),
    );
    $cases[] = array('url=whiz!;.:#=%/|+bang?', array('url' => 'whiz!;.:#=%/|+bang?'));
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
