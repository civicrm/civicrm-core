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
 * Test the reading-writing of HTML snippets.
 */
class ChangeSetTest extends \CiviUnitTestCase {

  /**
   * Insert content using after().
   */
  public function testInsertAfter() {
    $changeSet = ChangeSet::create(__FUNCTION__);
    $counts = ['~/foo.html' => 0];

    $changeSet->alterHtml('~/foo.html', function (\phpQueryObject $doc, $file) use (&$counts) {
      $counts[$file]++;
      $doc->find('.foo')->after('<p ng-if="alpha.beta() && true">world</p>');
    });
    $changeSet->alterHtml('~/f*.html', function (\phpQueryObject $doc, $file) use (&$counts) {
      $counts[$file]++;
      $doc->find('.bar')->after('<p>cruel world</p>');
    });
    $changeSet->alterHtml('/path/does/not/exist.html', function(\phpQueryObject $doc) {
      throw new \Exception("This should not be called. The file does not exist!");
    });

    $results = ChangeSet::applyResourceFilters([$changeSet], 'partials', [
      '~/foo.html' => '<span><p class="foo">Hello</p><p class="bar">Goodbye</p></span>',
    ]);

    $this->assertHtmlEquals(
      '<span><p class="foo">Hello</p><p ng-if="alpha.beta() && true">world</p><p class="bar">Goodbye</p><p>cruel world</p></span>',
      $results['~/foo.html']
    );
    $this->assertEquals(2, $counts['~/foo.html']);
  }

  /**
   * Insert content using append() and prepend().
   */
  public function testAppendPrepend() {
    $changeSet = ChangeSet::create(__FUNCTION__);
    $counts = ['~/foo.html' => 0];

    $changeSet->alterHtml('~/foo.html', function (\phpQueryObject $doc, $file) use (&$counts) {
      $counts[$file]++;
      $doc->find('.foo')->append('<p ng-if="!!gamma()">world</p>');
    });
    $changeSet->alterHtml('~/*.html', function (\phpQueryObject $doc, $file) use (&$counts) {
      $counts[$file]++;
      $doc->find('.bar')->prepend('<span>Cruel world,</span>');
    });
    $changeSet->alterHtml('/path/does/not/exist.html', function(\phpQueryObject $doc) {
      throw new \Exception("This should not be called. The file does not exist!");
    });

    $originals = [
      '~/foo.html' => '<span><p class="foo">Hello</p><p class="bar">Goodbye</p></span>',
    ];
    $results = ChangeSet::applyResourceFilters([$changeSet], 'partials', $originals);

    $this->assertHtmlEquals(
      '<span><p class="foo">Hello<p ng-if="!!gamma()">world</p></p><p class="bar"><span>Cruel world,</span>Goodbye</p></span>',
      $results['~/foo.html']
    );
    $this->assertEquals(2, $counts['~/foo.html']);
  }

  protected function assertHtmlEquals($expected, $actual, $message = '') {
    $expected = preg_replace(';>[ \r\n\t]+;', '>', $expected);
    $actual = preg_replace(';>[ \r\n\t]+;', '>', $actual);
    $this->assertEquals($expected, $actual, $message);
  }

}
