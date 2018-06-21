<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * Class CRM_Core_BAO_CacheTest
 * @group headless
 */
class CRM_Core_BAO_CacheTest extends CiviUnitTestCase {

  public function testMultiVersionDecode() {
    $encoders = ['serialize', ['CRM_Core_BAO_Cache', 'encode']];
    $values = [NULL, 0, 1, TRUE, FALSE, [], ['abcd'], 'ab;cd', new stdClass()];
    foreach ($encoders as $encoder) {
      foreach ($values as $value) {
        $encoded = $encoder($value);
        $decoded = CRM_Core_BAO_Cache::decode($encoded);
        $this->assertEquals($value, $decoded, "Failure encoding/decoding value " . var_export($value, 1) . ' with ' . var_export($encoder, 1));
      }
    }
  }

  public function exampleValues() {
    $binary = '';
    for ($i = 0; $i < 256; $i++) {
      $binary .= chr($i);
    }

    $ex = [];

    $ex[] = [array('abc' => 'def')];
    $ex[] = [0];
    $ex[] = ['hello world'];
    $ex[] = ['Scarabée'];
    $ex[] = ['Iñtërnâtiônàlizætiøn'];
    $ex[] = ['これは日本語のテキストです。読めますか'];
    $ex[] = ['देखें हिन्दी कैसी नजर आती है। अरे वाह ये तो नजर आती है।'];
    $ex[] = [$binary];

    return $ex;
  }

  /**
   * @param $originalValue
   * @dataProvider exampleValues
   */
  public function testSetGetItem($originalValue) {
    CRM_Core_BAO_Cache::setItem($originalValue, __CLASS__, 'testSetGetItem');

    $return_1 = CRM_Core_BAO_Cache::getItem(__CLASS__, 'testSetGetItem');
    $this->assertEquals($originalValue, $return_1);

    // Wipe out any in-memory copies of the cache. Check to see if the SQL
    // read is correct.

    CRM_Core_BAO_Cache::$_cache = NULL;
    CRM_Utils_Cache::$_singleton = NULL;
    $return_2 = CRM_Core_BAO_Cache::getItem(__CLASS__, 'testSetGetItem');
    $this->assertEquals($originalValue, $return_2);
  }

  public function getCleanKeyExamples() {
    $es = [];
    $es[] = ['hello_world and other.planets', 'hello_world and other.planets']; // allowed chars
    $es[] = ['hello/world+-#@{}', 'hello-2fworld-2b-2d-23-40-7b-7d']; // escaped chars
    $es[] = ['123456789 123456789 123456789 123456789 123456789 123456789 123', '123456789 123456789 123456789 123456789 123456789 123456789 123']; // long but allowed
    $es[] = ['123456789 123456789 123456789 123456789 123456789 123456789 1234', '-2a008e182a4dcd1a78f405f30119e5f2']; // too long, md5 fallback
    $es[] = ['123456789 /23456789 +23456789 -23456789 123456789 123456789', '-1b6baab5961431ed443ab321f5dfa0fb']; // too long, md5 fallback
    return $es;
  }

  /**
   * @param $inputKey
   * @param $expectKey
   * @dataProvider getCleanKeyExamples
   */
  public function testCleanKeys($inputKey, $expectKey) {
    $actualKey = CRM_Core_BAO_Cache::cleanKey($inputKey);
    $this->assertEquals($expectKey, $actualKey);
  }

}
