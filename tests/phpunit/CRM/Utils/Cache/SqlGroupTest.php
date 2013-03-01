<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Utils_Cache_SqlGroupTest extends CiviUnitTestCase {
  function setUp() {
    parent::setUp();
  }

  function tearDown() {
    parent::tearDown();
  }

  /**
   * Add and remove two items from the same cache instance
   */
  function testSameInstance() {
    $a = new CRM_Utils_Cache_SqlGroup(array(
      'group' => 'testSameInstance',
    ));
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_cache WHERE group_name = "testSameInstance"');
    $fooValue = array('whiz' => 'bang', 'bar' => 2);
    $a->set('foo', $fooValue);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_cache WHERE group_name = "testSameInstance"');
    $this->assertEquals($a->get('foo'), array('whiz' => 'bang', 'bar' => 2));

    $barValue = 45.78;
    $a->set('bar', $barValue);
    $this->assertDBQuery(2, 'SELECT count(*) FROM civicrm_cache WHERE group_name = "testSameInstance"');
    $this->assertEquals($a->get('bar'), 45.78);
    
    $a->delete('foo');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_cache WHERE group_name = "testSameInstance"');

    $a->flush();
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_cache WHERE group_name = "testSameInstance"');
  }

  /**
   * Add item to one cache instance then read with another
   */
  function testTwoInstance() {
    $a = new CRM_Utils_Cache_SqlGroup(array(
      'group' => 'testTwoInstance',
    ));
    $fooValue = array('whiz' => 'bang', 'bar' => 3);
    $a->set('foo', $fooValue);
    $this->assertEquals($a->get('foo'), array('whiz' => 'bang', 'bar' => 3));

    $b = new CRM_Utils_Cache_SqlGroup(array(
      'group' => 'testTwoInstance',
      'prefetch' => FALSE,
    ));
    $this->assertEquals($b->get('foo'), array('whiz' => 'bang', 'bar' => 3));
  }

  /**
   * Add item to one cache instance then read (and prefetch) with another
   */
  function testPrefetch() {
    $a = new CRM_Utils_Cache_SqlGroup(array(
      'group' => 'testPrefetch',
    ));
    $fooValue = array('whiz' => 'bang', 'bar' => 4);
    $a->set('foo', $fooValue);
    $this->assertEquals($a->get('foo'), array('whiz' => 'bang', 'bar' => 4));

    $b = new CRM_Utils_Cache_SqlGroup(array(
      'group' => 'testPrefetch',
      'prefetch' => TRUE,
    ));
    // assuming the values have been prefetched in $b, we can do a stale
    // read -- i.e. change the underlying data table and then read the
    // prefetched value from $b
    $fooValue2 = 'muahahaha';
    $a->set('foo', $fooValue2);
    $this->assertEquals($b->get('foo'), array('whiz' => 'bang', 'bar' => 4));
    
    // ok, enough with the stale reading
    $b->prefetch();
    $this->assertEquals($b->get('foo'), 'muahahaha');
  }
}
