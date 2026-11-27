<?php

namespace E2E\AfformMock;

use Civi;

/**
 * Display filters written by FormBuilder must read back as written.
 *
 * @group e2e
 */
class AfformGuiDisplayFiltersBrowserTest extends Civi\Test\MinkBase {

  public static function setUpBeforeClass(): void {
    \Civi\Test::e2e()
      ->install(['org.civicrm.afform', 'org.civicrm.afform_admin', 'org.civicrm.search_kit'])
      ->apply();
  }

  public static function getFilters(): array {
    return [
      'list before another filter' => [[['name' => 'tag_id', 'mode' => 'val', 'value' => [1, 2]], ['name' => 'a', 'mode' => 'val', 'value' => 1]]],
      'time' => [[['name' => 'receive_date', 'mode' => 'val', 'value' => '2024-01-01 10:00:00']]],
      'apostrophe after another filter' => [[['name' => 'a', 'mode' => 'val', 'value' => 1], ['name' => 'last_name', 'mode' => 'val', 'value' => "O'Brien"]]],
      'quoted comma and colon' => [[['name' => 'note', 'mode' => 'val', 'value' => 'say "a, b: c"']]],
      'backslash' => [[['name' => 'path', 'mode' => 'val', 'value' => 'c:\\temp'], ['name' => 'x', 'mode' => 'routeParams', 'value' => 'y\\z']]],
      'name with a leading digit' => [[['name' => '1abc', 'mode' => 'routeParams', 'value' => '2x']]],
      'plain' => [[['name' => 'contact_type', 'mode' => 'val', 'value' => 'Individual'], ['name' => 'id', 'mode' => 'routeParams', 'value' => 'cid']]],
    ];
  }

  /**
   * Written filters read back unchanged, and form the object AngularJS evaluates.
   *
   * @dataProvider getFilters
   */
  public function testFiltersReadBack(array $filters): void {
    $session = $this->mink->getSession();
    $this->login($GLOBALS['_CV']['ADMIN_USER']);
    $this->visit(Civi::url('backend://civicrm/admin/afform'));
    $this->assertTrue($session->wait(10000, '!!(window.angular && angular.element(document.querySelector(".ng-scope")).injector()?.has("afGui"))'));
    $result = $session->evaluateScript('(() => {
      const injector = angular.element(document.querySelector(".ng-scope")).injector();
      const afGui = injector.get("afGui");
      const filters = ' . json_encode($filters) . ';
      const text = afGui.stringifyDisplayFilters(filters);
      const locals = {routeParams: {}, options: {}};
      filters.forEach((f) => { if (f.mode !== "val") locals[f.mode][f.value] = "from-" + f.mode; });
      let evaluated;
      try { evaluated = injector.get("$parse")(text)(locals); } catch (e) { evaluated = String(e.message).split("\n")[0]; }
      return {readBack: afGui.parseDisplayFilters(text), evaluated};
    })()');
    $this->assertSame(json_encode($filters), json_encode($result['readBack']), 'Read back');
    $expected = [];
    foreach ($filters as $filter) {
      $expected[$filter['name']] = $filter['mode'] === 'val' ? $filter['value'] : 'from-' . $filter['mode'];
    }
    $this->assertSame(json_encode($expected), json_encode($result['evaluated']), 'Evaluated by AngularJS');
  }

}
