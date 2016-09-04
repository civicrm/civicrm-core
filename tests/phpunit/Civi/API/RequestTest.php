<?php
namespace Civi\API;

/**
 */
class RequestTest extends \CiviUnitTestCase {

  /**
   * @return array
   */
  public function v4options() {
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
      array(
        'version' => 4,
        'option.limit' => 15,
        'option.foo' => array('bar'),
        'options' => array('whiz' => 'bang'),
        'optionnotreally' => 'data',
      ), // requestParams
      array('limit' => 15, 'foo' => array('bar'), 'whiz' => 'bang'), // expectedOptions
      array('optionnotreally' => 'data'), // expectedData
      array(), // expectedChains
    );
    $cases[] = array(
      array(
        'version' => 4,
        'return' => array('field1', 'field2'),
        'return.field3' => 1,
        'return.field4' => 0,
        'returnontreally' => 'data',
      ), // requestParams
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
   * @param array $inputParams
   * @param $expectedOptions
   * @param $expectedData
   * @param $expectedChains
   * @dataProvider v4options
   */
  public function testCreateRequest_v4Options($inputParams, $expectedOptions, $expectedData, $expectedChains) {
    $apiRequest = Request::create('MyEntity', 'MyAction', $inputParams, NULL);
    $this->assertEquals($expectedOptions, $apiRequest['options']->getArray());
    $this->assertEquals($expectedData, $apiRequest['data']->getArray());
    $this->assertEquals($expectedChains, $apiRequest['chains']);
  }

  /**
   * @expectedException \API_Exception
   */
  public function testCreateRequest_v4BadEntity() {
    Request::create('Not!Valid', 'create', array('version' => 4), NULL);
  }

  /**
   * @expectedException \API_Exception
   */
  public function testCreateRequest_v4BadAction() {
    Request::create('MyEntity', 'bad!action', array('version' => 4), NULL);
  }

  /**
   * @return array
   */
  public function validEntityActionPairs() {
    $cases = array();
    $cases[] = array(
      array('MyEntity', 'MyAction', 3),
      array('MyEntity', 'myaction', 3),
    );
    $cases[] = array(
      array('my+entity', 'MyAction', 3),
      array('MyEntity', 'myaction', 3),
    );
    $cases[] = array(
      array('my entity with under_scores', 'My_Action', 3),
      array('MyEntityWithUnderScores', 'my_action', 3),
    );
    $cases[] = array(
      array('u_f_match', 'get Something', 3),
      array('UFMatch', 'get_something', 3),
    );
    $cases[] = array(
      array('MyEntity', 'MyAction', 4),
      array('MyEntity', 'myAction', 4),
    );
    return $cases;
  }

  /**
   * @dataProvider validEntityActionPairs
   * @param $input
   * @param $expected
   * @throws \API_Exception
   */
  public function testCreateRequest_EntityActionMunging($input, $expected) {
    list ($inEntity, $inAction, $inVersion) = $input;
    $apiRequest = Request::create($inEntity, $inAction, array('version' => $inVersion), NULL);
    $this->assertEquals($expected, array($apiRequest['entity'], $apiRequest['action'], $apiRequest['version']));
  }

  /**
   * @return array
   */
  public function invalidEntityActionPairs() {
    $cases = array();
    $cases[] = array('My+Entity', 'MyAction', 4);
    $cases[] = array('My Entity', 'MyAction', 4);
    $cases[] = array('2MyEntity', 'MyAction', 4);
    $cases[] = array('MyEntity', 'My+Action', 4);
    $cases[] = array('MyEntity', 'My Action', 4);
    $cases[] = array('MyEntity', '2Action', 4);
    return $cases;
  }

  /**
   * @dataProvider invalidEntityActionPairs
   * @expectedException \API_Exception
   * @param $inEntity
   * @param $inAction
   * @param $inVersion
   * @throws \API_Exception
   */
  public function testCreateRequest_InvalidEntityAction($inEntity, $inAction, $inVersion) {
    Request::create($inEntity, $inAction, array('version' => $inVersion), NULL);
  }

}
