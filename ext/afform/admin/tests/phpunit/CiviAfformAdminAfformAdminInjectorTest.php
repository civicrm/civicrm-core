<?php

use Civi\Api4\Afform;
use Civi\Api4\SavedSearch;
use Civi\Api4\SearchDisplay;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;

/**
 * @group headless
 */
class CiviAfformAdminAfformAdminInjectorTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface {

  public function setUpHeadless(): CiviEnvBuilder {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function tearDown(): void {
    Afform::revert(FALSE)->addWhere('name', '=', 'testGearMenuForm')->execute();
    SavedSearch::delete(FALSE)->addWhere('name', '=', 'testGearMenuSearch')->execute();
    parent::tearDown();
  }

  /**
   * Form title and search label are stored data: the menu must not let
   * Angular interpolate them.
   */
  public function testGearMenuDoesNotInterpolateLabels(): void {
    SavedSearch::create(FALSE)
      ->setValues([
        'name' => 'testGearMenuSearch',
        'label' => 'Search {{ 1 + 1 }}',
        'api_entity' => 'Contact',
        'api_params' => ['version' => 4, 'select' => ['id']],
      ])
      ->addChain('display', SearchDisplay::create(FALSE)->setValues([
        'saved_search_id' => '$id',
        'name' => 'testGearMenuDisplay',
        'label' => 'testGearMenuDisplay',
        'type' => 'table',
        'settings' => ['columns' => [['key' => 'id', 'label' => 'ID', 'type' => 'field']]],
      ]))
      ->execute();
    Afform::create(FALSE)
      ->setLayoutFormat('html')
      ->setValues([
        'name' => 'testGearMenuForm',
        'title' => 'Form {{ 1 + 1 }}',
        'type' => 'search',
        'layout' => '<div af-fieldset=""><crm-search-display-table search-name="testGearMenuSearch" display-name="testGearMenuDisplay"></crm-search-display-table></div>',
      ])
      ->execute();
    \Civi::service('angular')->clear();

    $html = implode('', \Civi::service('angular')->getPartials('testGearMenuForm'));
    foreach (['Form {{ 1 + 1 }}', 'Search {{ 1 + 1 }}'] as $label) {
      $this->assertMatchesRegularExpression('#<span ng-non-bindable(="")?>[^<]*<em>' . preg_quote($label, '#') . '</em>#', $html);
    }
  }

}
