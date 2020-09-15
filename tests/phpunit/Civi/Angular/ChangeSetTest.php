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
