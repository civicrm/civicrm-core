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
namespace Civi\FlexMailer\Listener;

use Civi\FlexMailer\ClickTracker\TextClickTracker;

/**
 * Class HtmlClickTrackerTest
 *
 * @group headless
 */
class TextClickTrackerTest extends \CiviUnitTestCase {

  public function setUp(): void {
    // Activate before transactions are setup.
    $manager = \CRM_Extension_System::singleton()->getManager();
    if ($manager->getStatus('org.civicrm.flexmailer') !== \CRM_Extension_Manager::STATUS_INSTALLED) {
      $manager->install(['org.civicrm.flexmailer']);
    }

    parent::setUp();
  }

  public function getHrefExamples() {
    $exs = [];

    // For each example, the test-harness will useHtmlClickTracker to wrap the URL in "tracking(...)".

    $exs[] = [
      // Basic case
      '<p><a href="http://example.com/">Foo</a></p>',
      '<p><a href="tracking(http://example.com/)" rel=\'nofollow\'>Foo</a></p>',
    ];
    $exs[] = [
      // Messy looking URL
      '<p><a href=\'https://sub.example.com/foo.php?whiz=%2Fbang%2F&pie[fruit]=apple\'>Foo</a></p>',
      '<p><a href=\'tracking(https://sub.example.com/foo.php?whiz=%2Fbang%2F&pie[fruit]=apple)\' rel=\'nofollow\'>Foo</a></p>',
    ];
    $exs[] = [
      // Messy looking URL, designed to trip-up quote handling, no tracking as no http
      '<p><a href="javascript:alert(\'Cheese\')">Foo</a></p>',
      '<p><a href="javascript:alert(\'Cheese\')" rel=\'nofollow\'>Foo</a></p>',
    ];
    $exs[] = [
      // Messy looking URL, designed to trip-up quote handling, no tracking as no http
      '<p><a href=\'javascript:alert("Cheese")\'>Foo</a></p>',
      '<p><a href=\'javascript:alert("Cheese")\' rel=\'nofollow\'>Foo</a></p>',
    ];
    $exs[] = [
      // Messy looking URL, funny whitespace
      '<p><a href="http://example.com?utm_medium=email&amp;utm_detail=hello">Foo</a></p>',
      '<p><a href="tracking(http://example.com?utm_medium=email&amp;utm_detail=hello)" rel=\'nofollow\'>Foo</a></p>',
    ];
    $exs[] = [
      // Many different URLs
      '<p><a href="http://example.com/1">First</a><a href="http://example.com/2">Second</a><a href=\'http://example.com/3\'>Third</a><a href="http://example.com/4">Fourth</a></p>',
      '<p><a href="tracking(http://example.com/1)" rel=\'nofollow\'>First</a><a href="tracking(http://example.com/2)" rel=\'nofollow\'>Second</a><a href=\'tracking(http://example.com/3)\' rel=\'nofollow\'>Third</a><a href="tracking(http://example.com/4)" rel=\'nofollow\'>Fourth</a></p>',
    ];
    $exs[] = [
      // Messy looking URL, including hyphens
      '<p><a href=\'https://sub.example-url.com/foo-bar.php?whiz=%2Fbang%2F&pie[fruit]=apple-pie\'>Foo</a></p>',
      '<p><a href=\'tracking(https://sub.example-url.com/foo-bar.php?whiz=%2Fbang%2F&pie[fruit]=apple-pie)\' rel=\'nofollow\'>Foo</a></p>',
    ];
    return $exs;
  }

  /**
   * @param $inputHtml
   * @param $expectHtml
   * @dataProvider getHrefExamples
   */
  public function testReplaceTextUrls($inputHtml, $expectHtml): void {
    $inputText = \CRM_Utils_String::htmlToText($inputHtml);
    $expectText = \CRM_Utils_String::htmlToText($expectHtml);
    $expectText = str_replace('/tracking', 'tracking', $expectText);
    $actual = TextClickTracker::replaceTextUrls($inputText, function($url) {
      return "tracking($url)";
    });

    $this->assertEquals($expectText, $actual, "Check substitutions on text ($inputText)");
  }

}
