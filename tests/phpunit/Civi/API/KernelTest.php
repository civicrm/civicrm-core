<?php
namespace Civi\API;

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 */
class KernelTest extends \CiviUnitTestCase {

  function v4options() {
    $cases = array(); // array(0 => $requestParams, 1 => $expectedOptions, 2 => $expectedData, 3 => $expectedChains)
    $cases[] = array(
      array('version' => 4), // requestParams
      array(), // expectedOptions
      array(), // expectedData
      array(), // expectedChains
    );
    $cases[] = array(
      array('version' => 4, 'debug' => TRUE), // requestParams
      array('debug' => TRUE), // expectedOptions
      array(), // expectedData
      array(), // expectedChains
    );
    $cases[] = array(
      array('version' => 4, 'format.is_success' => TRUE), // requestParams
      array('format' => 'is_success'), // expectedOptions
      array(), // expectedData
      array(), // expectedChains
    );
    $cases[] = array(
      array('version' => 4, 'option.limit' => 15, 'option.foo' => array('bar'), 'options' => array('whiz' => 'bang'), 'optionnotreally' => 'data'), // requestParams
      array('limit' => 15, 'foo' => array('bar'), 'whiz' => 'bang'), // expectedOptions
      array('optionnotreally' => 'data'), // expectedData
      array(), // expectedChains
    );
    $cases[] = array(
      array('version' => 4, 'return' => array('field1', 'field2'), 'return.field3' => 1, 'return.field4' => 0, 'returnontreally' => 'data'), // requestParams
      array('return' => array('field1', 'field2', 'field3')), // expectedOptions
      array('returnontreally' => 'data'), // expectedData
      array(), // expectedChains
    );
    $cases[] = array(
      array('version' => 4, 'foo' => array('bar'), 'whiz' => 'bang'), // requestParams
      array(), // expectedOptions
      array('foo' => array('bar'), 'whiz' => 'bang'), // expectedData
      array(), // expectedChains
    );
    $cases[] = array(
      array('version' => 4, 'api.foo.bar' => array('whiz' => 'bang')), // requestParams
      array(), // expectedOptions
      array(), // expectedData
      array('api.foo.bar' => array('whiz' => 'bang')), // expectedChains
    );
    $cases[] = array(
      array(
        'version' => 4,
        'option.limit' => 15,
        'options' => array('whiz' => 'bang'),
        'somedata' => 'data',
        'moredata' => array('woosh'),
        'return.field1' => 1,
        'return' => array('field2'),
        'api.first' => array('the first call'),
        'api.second' => array('the second call'),
      ), // requestParams
      array('limit' => 15, 'whiz' => 'bang', 'return' => array('field1', 'field2')), // expectedOptions
      array('somedata' => 'data', 'moredata' => array('woosh')), // expectedData
      array('api.first' => array('the first call'), 'api.second' => array('the second call')), // expectedChains
    );
    return $cases;
  }

  /**
   * @param $inputParams
   * @param $expectedOptions
   * @param $expectedData
   * @param $expectedChains
   * @dataProvider v4options
   */
  function testCreateRequest_v4Options($inputParams, $expectedOptions, $expectedData, $expectedChains) {
    $kernel = new Kernel(NULL);
    $apiRequest = $kernel->createRequest('MyEntity', 'MyAction', $inputParams, NULL);
    $this->assertEquals($expectedOptions, $apiRequest['options']->getArray());
    $this->assertEquals($expectedData, $apiRequest['data']->getArray());
    $this->assertEquals($expectedChains, $apiRequest['chains']);
  }

  /**
   * @expectedException \API_Exception
   */
  function testCreateRequest_v4BadEntity() {
    $kernel = new Kernel(NULL);
    $kernel->createRequest('Not!Valid', 'create', array('version' => 4), NULL);
  }

  /**
   * @expectedException \API_Exception
   */
  function testCreateRequest_v4BadAction() {
    $kernel = new Kernel(NULL);
    $kernel->createRequest('MyEntity', 'bad!action', array('version' => 4), NULL);
  }
}