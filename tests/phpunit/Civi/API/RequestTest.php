<?php
namespace Civi\API;

/**
 */
class RequestTest extends \CiviUnitTestCase {

  /**
   * @return array
   */
  public function validEntityActionPairs() {
    $cases = [];
    $cases[] = [
      ['MyEntity', 'MyAction', 3],
      ['MyEntity', 'myaction', 3],
    ];
    $cases[] = [
      ['my+entity', 'MyAction', 3],
      ['MyEntity', 'myaction', 3],
    ];
    $cases[] = [
      ['my entity with under_scores', 'My_Action', 3],
      ['MyEntityWithUnderScores', 'my_action', 3],
    ];
    $cases[] = [
      ['u_f_match', 'get Something', 3],
      ['UFMatch', 'get_something', 3],
    ];
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
    $apiRequest = Request::create($inEntity, $inAction, ['version' => $inVersion]);
    $this->assertEquals($expected, [$apiRequest['entity'], $apiRequest['action'], $apiRequest['version']]);
  }

  /**
   * @return array
   */
  public function invalidEntityActionPairs() {
    $cases = [];
    $cases[] = ['Not!Valid', 'create', 4];
    $cases[] = ['My+Entity', 'MyAction', 4];
    $cases[] = ['My Entity', 'MyAction', 4];
    $cases[] = ['2MyEntity', 'MyAction', 4];
    $cases[] = ['MyEntity', 'My+Action', 4];
    $cases[] = ['MyEntity', 'My Action', 4];
    $cases[] = ['MyEntity', '2Action', 4];
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
    Request::create($inEntity, $inAction, ['version' => $inVersion], NULL);
  }

}
