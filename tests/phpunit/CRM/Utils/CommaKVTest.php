<?php

/**
 * @group headless
 */
class CRM_Utils_CommaKVTest extends CiviUnitTestCase {

  /**
   * Set up for tests.
   */
  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  public function testExamples() {
    $canonicalExamples = [];
    $canonicalExamples[''] = ['' => '']; /* Weird, but that's what it has been doing. */
    $canonicalExamples['Orange'] = ['Orange' => 'Orange'];
    $canonicalExamples['2=Purple'] = [2 => 'Purple'];
    $canonicalExamples['Red,White,Blue'] = ['Red' => 'Red', 'White' => 'White', 'Blue' => 'Blue'];
    $canonicalExamples['3=Red,2=White,1=Blue'] = [3 => 'Red', 2 => 'White', 1 => 'Blue'];

    // The alternate examples are legal representations of the data, but they differ slightly from canonical encode() output.
    $alternateExamples = [];
    $alternateExamples['Red, Green, Blue'] = ['Red' => 'Red', 'Green' => 'Green', 'Blue' => 'Blue'];
    $alternateExamples["\nCyan , Yellow\n,\t\t Magenta "] = ['Cyan' => 'Cyan', 'Yellow' => 'Yellow', 'Magenta' => 'Magenta'];
    $alternateExamples['f00=Red,fff=White,Blue'] = ['f00' => 'Red', 'fff' => 'White', 'Blue' => 'Blue'];

    $this->assertNotEmpty($canonicalExamples);
    foreach ($canonicalExamples as $string => $array) {
      $decoded = CRM_Utils_CommaKV::unserialize($string);
      $this->assertEquals($array, $decoded, sprintf('String %s should be parsed as array', json_encode($string)));
      $encoded = CRM_Utils_CommaKV::serialize($decoded);
      $this->assertEquals($string, $encoded, sprintf('String %s should be re-encoded as equivalent value', json_encode($string)));
    }

    $this->assertNotEmpty($alternateExamples);
    foreach ($alternateExamples as $string => $array) {
      $parsed_1 = CRM_Utils_CommaKV::unserialize($string);
      $this->assertEquals($array, $parsed_1, sprintf('String %s should be parsed as array', json_encode($string)));
      $parsed_2 = CRM_Utils_CommaKV::unserialize(CRM_Utils_CommaKV::serialize($parsed_1));
      $this->assertEquals($array, $parsed_2, sprintf('String %s should yield stable outputs after multiple decode/encode cycles', json_encode($string)));
    }
  }

}
