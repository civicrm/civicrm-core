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
   * @throws \CRM_Core_Exception
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
   * @param $inEntity
   * @param $inAction
   * @param $inVersion
   * @throws \Civi\API\Exception\NotImplementedException
   */
  public function testCreateRequest_InvalidEntityAction($inEntity, $inAction, $inVersion) {
    $this->expectException(\Civi\API\Exception\NotImplementedException::class);
    Request::create($inEntity, $inAction, ['version' => $inVersion], NULL);
  }

  public function getCastingExamples(): array {
    $exs = [];
    // We run the same tests on `$checkPermissions` (which has real PHP setter method)
    // and `$useTrash` (which has a generic magic method) to show that casting is similar.
    $exs[] = ['Contact.delete checkPermissions', 0, FALSE];
    $exs[] = ['Contact.delete checkPermissions', '0', FALSE];
    $exs[] = ['Contact.delete checkPermissions', 1, TRUE];
    $exs[] = ['Contact.delete checkPermissions', '1', TRUE];
    $exs[] = ['Contact.delete useTrash', 0, FALSE];
    $exs[] = ['Contact.delete useTrash', '0', FALSE];
    $exs[] = ['Contact.delete useTrash', 1, TRUE];
    $exs[] = ['Contact.delete useTrash', '1', TRUE];
    return $exs;
  }

  /**
   * @param $entityActionField
   * @param $inputValue
   * @param $expectValue
   * @dataProvider getCastingExamples
   */
  public function testCasting(string $entityActionField, $inputValue, $expectValue): void {
    [$entity, $action, $field] = preg_split('/[ \.]/', $entityActionField);
    $request = Request::create($entity, $action, ['version' => 4, $field => $inputValue]);
    $getter = 'get' . ucfirst($field);
    $actualValue = call_user_func([$request, $getter]);
    $this->assertEquals(gettype($actualValue), gettype($expectValue));
    $this->assertTrue($actualValue === $expectValue);
  }

}
