<?php
namespace Civi\Afform;

use Civi\Api4\Afform;
use Civi\Api4\Navigation;
use Civi\Test\HeadlessInterface;

/**
 * @group headless
 */
class AfformNavigationTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface {

  private $formName = 'afformNavigationTest';

  public function setUpHeadless() {
    return \Civi\Test::headless()->install(['org.civicrm.search_kit', 'org.civicrm.afform', 'org.civicrm.afform_admin'])->apply();
  }

  public function setUp(): void {
    parent::setUp();
    Afform::revert(FALSE)
      ->addWhere('name', '=', $this->formName)
      ->execute();
  }

  public function testCreateUpdateDeleteNavigation():void {
    Afform::create(FALSE)
      ->addValue('title', 'Flight of the Navigator')
      ->addValue('name', $this->formName)
      ->addValue('server_route', 'civicrm/test/afform/navigation')
      ->addValue('navigation', [
        'label' => 'Menu of the Navigator',
        'parent' => 'Search',
        'weight' => 0,
      ])
      ->execute();

    // Nav should have been created automatically
    $nav = Navigation::get(FALSE)
      ->addWhere('url', '=', 'civicrm/test/afform/navigation')
      ->execute()->single();
    $this->assertEquals('Menu of the Navigator', $nav['label']);
    $this->assertEquals('civicrm/test/afform/navigation', $nav['url']);
    $this->assertEquals(['access CiviCRM'], $nav['permission']);

    // Delete navigation
    Navigation::delete(FALSE)
      ->addWhere('url', '=', 'civicrm/test/afform/navigation')
      ->execute();
    // Navigation should have been deleted from Afform as well
    $afform = Afform::get(FALSE)
      ->addWhere('name', '=', $this->formName)
      ->execute()->single();
    $this->assertNull($afform['navigation']);
  }

}
