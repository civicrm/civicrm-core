<?php
namespace api\v4\Afform;

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\SearchParamSet;

/**
 * Test case for SearchParamSet permissions.
 *
 * @group headless
 */
class AfformSearchParamSetTest extends AfformUsageTestCase {

  public function tearDown(): void {
    SearchParamSet::delete(FALSE)->addWhere('afform_name', '=', $this->formName)->execute();
    parent::tearDown();
  }

  /**
   * @return array
   */
  public static function getFormPermissions(): array {
    return [
      'form the user may open' => ['access CiviCRM', TRUE],
      'form the user may not open' => ['administer CiviCRM', FALSE],
    ];
  }

  /**
   * Parameter sets follow the permission of the form they belong to
   *
   * @dataProvider getFormPermissions
   */
  public function testParameterSetsFollowFormPermission(string $formPermission, bool $isAllowed): void {
    $this->useValues([
      'layout' => '<div af-fieldset=""><af-field name="source" /></div>',
      'type' => 'search',
      'permission' => [$formPermission],
    ]);
    $ownerId = $this->createLoggedInUser();
    $storedSetId = SearchParamSet::create(FALSE)
      ->setValues(['label' => 'Stored', 'afform_name' => $this->formName, 'filters' => ['source' => 'a'], 'created_by' => $ownerId])
      ->execute()->single()['id'];
    $this->createLoggedInUser();
    \CRM_Core_Config::singleton()->userPermissionTemp = new \CRM_Core_Permission_Temp();
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];

    $countAffected = function (callable $apiCall): int {
      try {
        return $apiCall()->count();
      }
      catch (UnauthorizedException $e) {
        return 0;
      }
    };
    $readable = SearchParamSet::get()->addWhere('id', '=', $storedSetId)->execute()->count();
    $created = $countAffected(fn() => SearchParamSet::create()->setValues(['label' => 'New', 'afform_name' => $this->formName, 'filters' => ['source' => 'b']])->execute());
    $updated = $countAffected(fn() => SearchParamSet::update()->addWhere('id', '=', $storedSetId)->addValue('label', 'Changed')->execute());
    $deleted = $countAffected(fn() => SearchParamSet::delete()->addWhere('id', '=', $storedSetId)->execute());

    $this->assertSame((int) $isAllowed, $readable);
    $this->assertSame((int) $isAllowed, $created);
    $this->assertSame((int) $isAllowed, $updated);
    $this->assertSame((int) $isAllowed, $deleted);
    $storedLabels = SearchParamSet::get(FALSE)->addWhere('afform_name', '=', $this->formName)->execute()->column('label');
    $this->assertSame($isAllowed ? ['New'] : ['Stored'], $storedLabels);
  }

}
