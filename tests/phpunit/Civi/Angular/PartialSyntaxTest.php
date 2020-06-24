<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Angular;

/**
 * Ensure that all Angular *.html partials are well-formed.
 */
class PartialSyntaxTest extends \CiviUnitTestCase {

  /**
   * @var Manager
   */
  protected $angular;

  /**
   * @var \CRM_Core_Resources
   */
  protected $res;

  /**
   * @inheritDoc
   */
  protected function setUp() {
    $this->useTransaction(TRUE);
    parent::setUp();
    $this->createLoggedInUser();
    $this->res = \CRM_Core_Resources::singleton();
    $this->angular = new Manager($this->res);
  }

  public function basicConsistencyExamples() {
    $cases = [];

    $cases[0] = [
      '<div foo="bar"></div>',
      '<div foo="bar"></div>',
    ];
    $cases[1] = [
      '<div foo="bar"/>',
      '<div foo="bar"></div>',
    ];
    $cases[2] = [
      '<div foo=\'bar\'></div>',
      '<div foo="bar"></div>',
    ];
    $cases[3] = [
      '<div foo=\'ts("Hello world")\'></div>',
      '<div foo=\'ts("Hello world")\'></div>',
    ];
    $cases[4] = [
      '<div foo="ts(\'Hello world\')\"></div>',
      '<div foo="ts(\'Hello world\')\"></div>',
    ];
    $cases[5] = [
      '<a href="{{foo}}" title="{{bar}}"></a>',
      '<a href="{{foo}}" title="{{bar}}"></a>',
    ];
    $cases[6] = [
      '<div ng-if="a && b"></div>',
      '<div ng-if="a && b"></div>',
    ];

    return $cases;
  }

  /**
   * @param string $inputHtml
   * @param string $expectHtml
   * @dataProvider basicConsistencyExamples
   */
  public function testConsistencyExamples($inputHtml, $expectHtml) {
    $coder = new Coder();
    $this->assertEquals($expectHtml, $coder->recode($inputHtml));
  }

  /**
   */
  public function testAllPartials() {
    $coder = new \Civi\Angular\Coder();
    $errors = [];
    $count = 0;
    foreach ($this->angular->getModules() as $module => $moduleDefn) {
      $partials = $this->angular->getPartials($module);
      foreach ($partials as $path => $html) {
        $count++;
        if (!$coder->checkConsistentHtml($html)) {
          $recodedHtml = $coder->recode($html);
          $this->assertEquals($html, $recodedHtml, "File $path has inconsistent HTML. Use tools/scripts/check-angular.php to debug. ");
        }
      }
    }

    $this->assertTrue($count > 0);
  }

}
