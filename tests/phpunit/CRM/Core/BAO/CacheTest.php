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
 * Class CRM_Core_BAO_CacheTest
 * @group headless
 */
class CRM_Core_BAO_CacheTest extends CiviUnitTestCase {

  /**
   * @var CRM_Utils_Cache_Interface
   */
  protected $a;

  public function createSimpleCache() {
    return new CRM_Utils_Cache_FastArrayDecorator(
      $this->a = CRM_Utils_Cache::create([
        'name' => 'CRM_Core_BAO_CacheTest',
        'type' => ['*memory*', 'SqlGroup', 'ArrayCache'],
      ])
    );
  }

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

    $ex[] = [['abc' => 'def']];
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
    $this->createSimpleCache();
    $this->a->set('testSetGetItem', $originalValue);

    $return_1 = $this->a->get('testSetGetItem');
    $this->assertEquals($originalValue, $return_1);

    // Wipe out any in-memory copies of the cache. Check to see if the SQL
    // read is correct.

    CRM_Core_BAO_Cache::$_cache = NULL;
    CRM_Utils_Cache::$_singleton = NULL;
    $this->a->values = [];
    $return_2 = $this->a->get('testSetGetItem');
    $this->assertEquals($originalValue, $return_2);
  }

  public function getCleanKeyExamples() {
    $es = [];
    // allowed chars
    $es[] = ['hello_world and/other.planets', 'hello_world-20and-2fother.planets'];
    // escaped chars
    $es[] = ['hello/world+-#@{}', 'hello-2fworld-2b-2d-23-40-7b-7d'];
    // short with emoji
    $es[] = ["LF-\nTAB-\tCR-\remojiskull💀", 'LF-2d-aTAB-2d-9CR-2d-demojiskull-f0-9f-92-80'];
    // long with emoji
    $es[] = ["LF-\nTAB-\tCR-\remojibomb💣emojiskull💀", '-5d9324e052f6e10240dce5029c5e8525'];
    // spaces are escaped
    $es[] = ['123456789 123456789 123456789 123456789 123456789 123', '123456789-20123456789-20123456789-20123456789-20123456789-20123'];
    // long but allowed
    $es[] = ['123456789_123456789_123456789_123456789_123456789_123456789_123', '123456789_123456789_123456789_123456789_123456789_123456789_123'];
    // too long, md5 fallback
    $es[] = ['123456789_123456789_123456789_123456789_123456789_123456789_1234', '-e02b981aff954fdcc9a81c25f5ec9681'];
    // too long, md5 fallback
    $es[] = ['123456789-/23456789-+23456789--23456789_123456789_123456789', '-43b6dec1026187ae6f6a8fe4d56ab22e'];
    return $es;
  }

  /**
   * @param $inputKey
   * @param $expectKey
   * @dataProvider getCleanKeyExamples
   */
  public function testCleanKeys($inputKey, $expectKey) {
    $actualKey = CRM_Utils_Cache::cleanKey($inputKey);
    $this->assertEquals($expectKey, $actualKey);
  }

}
