<?php
namespace Civi\API;

/**
 */
class RequestTest extends \CiviUnitTestCase {

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
    $cases[] = array('Not!Valid', 'create', 4);
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
   * @expectedException \Civi\API\Exception\NotImplementedException
   * @param $inEntity
   * @param $inAction
   * @param $inVersion
   * @throws \Civi\API\Exception\NotImplementedException
   */
  public function testCreateRequest_InvalidEntityAction($inEntity, $inAction, $inVersion) {
    Request::create($inEntity, $inAction, array('version' => $inVersion), NULL);
  }

}
