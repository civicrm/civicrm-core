<?php
require_once 'CiviTest/CiviUnitTestCase.php';
class CRM_Utils_ArrayTest extends CiviUnitTestCase {
  function testBreakReference() {
    // Get a reference and make a change
    $fooRef1 = self::returnByReference();
    $this->assertEquals('original', $fooRef1['foo']);
    $fooRef1['foo'] = 'modified';

    // Make sure that the referenced item was actually changed
    $fooRef2 = self::returnByReference();
    $this->assertEquals('modified', $fooRef1['foo']);
    $this->assertEquals('original', $fooRef2['foo']);

    // Get a non-reference, make a change, and make sure the references were unaffected.
    $fooNonReference = CRM_Utils_Array::breakReference(self::returnByReference());
    $fooNonReference['foo'] = 'privately-modified';
    $this->assertEquals('modified', $fooRef1['foo']);
    $this->assertEquals('original', $fooRef2['foo']);
    $this->assertEquals('privately-modified', $fooNonReference['foo']);
  }

  private function &returnByReference() {
    static $foo;
    if ($foo === NULL) {
      $foo['foo'] = 'original';
    }
    return $foo;
  }

  function testIndexArray() {
    $inputs = array();
    $inputs[] = array(
      'lang' => 'en',
      'msgid' => 'greeting',
      'familiar' => false,
      'value' => 'Hello'
    );
    $inputs[] = array(
      'lang' => 'en',
      'msgid' => 'parting',
      'value' => 'Goodbye'
    );
    $inputs[] = array(
      'lang' => 'fr',
      'msgid' => 'greeting',
      'value' => 'Bon jour'
    );
    $inputs[] = array(
      'lang' => 'fr',
      'msgid' => 'parting',
      'value' => 'Au revoir'
    );
    $inputs[] = array(
      'lang' => 'en',
      'msgid' => 'greeting',
      'familiar' => true,
      'value' => 'Hey'
    );

    $byLangMsgid = CRM_Utils_Array::index(array('lang', 'msgid'), $inputs);
    $this->assertEquals($inputs[4], $byLangMsgid['en']['greeting']);
    $this->assertEquals($inputs[1], $byLangMsgid['en']['parting']);
    $this->assertEquals($inputs[2], $byLangMsgid['fr']['greeting']);
    $this->assertEquals($inputs[3], $byLangMsgid['fr']['parting']);
  }

  function testCollect() {
    $arr = array(
      array('catWord' => 'cat', 'dogWord' => 'dog'),
      array('catWord' => 'chat', 'dogWord' => 'chien'),
      array('catWord' => 'gato'),
    );
    $expected = array('cat', 'chat', 'gato');
    $this->assertEquals($expected, CRM_Utils_Array::collect('catWord', $arr));

    $arr = array();
    $arr['en']= (object) array('catWord' => 'cat', 'dogWord' => 'dog');
    $arr['fr']= (object) array('catWord' => 'chat', 'dogWord' => 'chien');
    $arr['es']= (object) array('catWord' => 'gato');
    $expected = array('en' => 'cat', 'fr' => 'chat', 'es' => 'gato');
    $this->assertEquals($expected, CRM_Utils_Array::collect('catWord', $arr));
  }

}
