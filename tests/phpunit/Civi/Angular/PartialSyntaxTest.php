<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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
    $cases = array();

    $cases[0] = array(
      '<div foo="bar"></div>',
      '<div foo="bar"></div>',
    );
    $cases[1] = array(
      '<div foo="bar"/>',
      '<div foo="bar"></div>',
    );
    $cases[2] = array(
      '<div foo=\'bar\'></div>',
      '<div foo="bar"></div>',
    );
    $cases[3] = array(
      '<div foo=\'ts("Hello world")\'></div>',
      '<div foo=\'ts("Hello world")\'></div>',
    );
    $cases[4] = array(
      '<div foo="ts(\'Hello world\')\"></div>',
      '<div foo="ts(\'Hello world\')\"></div>',
    );
    $cases[5] = array(
      '<a href="{{foo}}" title="{{bar}}"></a>',
      '<a href="{{foo}}" title="{{bar}}"></a>',
    );
    $cases[6] = array(
      '<div ng-if="a && b"></div>',
      '<div ng-if="a && b"></div>',
    );

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
    $errors = array();
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
