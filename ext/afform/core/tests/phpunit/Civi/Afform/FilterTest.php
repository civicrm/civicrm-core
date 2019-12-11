<?php
namespace Civi\Afform;

use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * Class FilterTest
 *
 * Ensure that the HTML post-processing/filtering works as expected.
 *
 * @package Civi\Afform
 * @group headless
 */
class FilterTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  const PERSON_TPL = '<af-form ctrl="modelListCtrl" ><af-entity type="Contact" name="person" />%s</af-form>';

  public function setUpHeadless() {
    return \Civi\Test::headless()->installMe(__DIR__)->apply();
  }

  /**
   * Apply any filters to an HTML partial.
   *
   * @param string $fileName
   * @param string $html
   *   Original HTML.
   * @return string
   *   Modified HTML.
   */
  private function htmlFilter($fileName, $html) {
    $htmls = \Civi\Angular\ChangeSet::applyResourceFilters(\Civi::service('angular')->getChangeSets(), 'partials', [$fileName => $html]);
    return $htmls[$fileName];
  }

  public function testDefnInjection() {
    $inputHtml = sprintf(self::PERSON_TPL,
      '<div af-fieldset="person"><af-field name="first_name" /></div>');
    $filteredHtml = $this->htmlFilter('~/afform/MyForm.aff.html', $inputHtml);
    $converter = new \CRM_Afform_ArrayHtml(TRUE);
    $parsed = $converter->convertHtmlToArray($filteredHtml);

    $myField = $parsed[0]['#children'][1]['#children'][0];
    $this->assertEquals('af-field', $myField['#tag']);
    $this->assertEquals('First Name', $myField['defn']['title']);
  }

  public function testDefnInjectionNested() {
    $inputHtml = sprintf(self::PERSON_TPL,
      '<span><div af-fieldset="person"><foo><af-field name="first_name" /></foo></div></span>');
    $filteredHtml = $this->htmlFilter('~/afform/MyForm.aff.html', $inputHtml);
    $converter = new \CRM_Afform_ArrayHtml(TRUE);
    $parsed = $converter->convertHtmlToArray($filteredHtml);

    $myField = $parsed[0]['#children'][1]['#children'][0]['#children'][0]['#children'][0];
    $this->assertEquals('af-field', $myField['#tag']);
    $this->assertEquals('First Name', $myField['defn']['title']);
  }

  public function testDefnOverrideTitle() {
    $inputHtml = sprintf(self::PERSON_TPL,
      '<div af-fieldset="person"><af-field name="first_name" defn="{title: \'Given name\'}" /></div>');
    $filteredHtml = $this->htmlFilter('~/afform/MyForm.aff.html', $inputHtml);
    $converter = new \CRM_Afform_ArrayHtml(TRUE);
    $parsed = $converter->convertHtmlToArray($filteredHtml);

    $myField = $parsed[0]['#children'][1]['#children'][0];
    $this->assertEquals('af-field', $myField['#tag']);
    $this->assertEquals('Given name', $myField['defn']['title']);
    $this->assertEquals('Text', $myField['defn']['input_type']);
  }

}
