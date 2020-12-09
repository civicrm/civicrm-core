<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

use Civi\FlexMailer\ClickTracker\TextClickTracker;
use Civi\FlexMailer\ClickTracker\HtmlClickTracker;
use Civi\FlexMailer\ClickTracker\BaseClickTracker;

/**
 * Tests that URLs are converted to tracked ones if at all possible.
 *
 * @group headless
 */
class Civi_FlexMailer_ClickTrackerTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  protected $mailing_id;

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp() {
    // Mock the getTrackerURL call; we don't need to test creating a row in a table.
    BaseClickTracker::$getTrackerURL = function($a, $b, $c) {
      return 'http://example.com/extern?u=1&qid=1';
    };

    parent::setUp();
  }

  public function tearDown() {
    // Reset the class.
    BaseClickTracker::$getTrackerURL = ['CRM_Mailing_BAO_TrackableURL', 'getTrackerURL'];
    parent::tearDown();
  }

  /**
   * Example: Test that a link without any tokens works.
   */
  public function testLinkWithoutTokens() {
    $filter = new TextClickTracker();
    $msg = 'See this: https://example.com/foo/bar?a=b&c=d#frag';
    $result = $filter->filterContent($msg, 1, 1);
    $this->assertEquals('See this: http://example.com/extern?u=1&qid=1', $result);
  }

  /**
   * Example: Test that a link with tokens in the query works.
   */
  public function testLinkWithTokensInQueryWithStaticParams() {
    $filter = new TextClickTracker();
    $msg = 'See this: https://example.com/foo/bar?a=b&cid={contact.id}';
    $result = $filter->filterContent($msg, 1, 1);
    $this->assertEquals('See this: http://example.com/extern?u=1&qid=1&cid={contact.id}', $result);
  }

  /**
   * Example: Test that a link with tokens in the query works.
   */
  public function testLinkWithTokensInQueryWithMultipleStaticParams() {
    $filter = new TextClickTracker();
    $msg = 'See this: https://example.com/foo/bar?cs={contact.checksum}&a=b&cid={contact.id}';
    $result = $filter->filterContent($msg, 1, 1);
    $this->assertEquals('See this: http://example.com/extern?u=1&qid=1&cs={contact.checksum}&cid={contact.id}', $result);
  }

  /**
   * Example: Test that a link with tokens in the query works.
   */
  public function testLinkWithTokensInQueryWithMultipleStaticParamsHtml() {
    $filter = new HtmlClickTracker();
    $msg = '<a href="https://example.com/foo/bar?cs={contact.checksum}&amp;a=b&amp;cid={contact.id}">See this</a>';
    $result = $filter->filterContent($msg, 1, 1);
    $this->assertEquals('<a href="http://example.com/extern?u=1&amp;qid=1&amp;cs={contact.checksum}&amp;cid={contact.id}" rel=\'nofollow\'>See this</a>', $result);
  }

  /**
   * Example: Test that a link with tokens in the query works.
   */
  public function testLinkWithTokensInQueryWithoutStaticParams() {
    $filter = new TextClickTracker();
    $msg = 'See this: https://example.com/foo/bar?cid={contact.id}';
    $result = $filter->filterContent($msg, 1, 1);
    $this->assertEquals('See this: http://example.com/extern?u=1&qid=1&cid={contact.id}', $result);
  }

  /**
   * Example: Test that a link with tokens in the fragment works.
   *
   * Seems browsers maintain the fragment when they receive a redirect, so a
   * token here might still work.
   */
  public function testLinkWithTokensInFragment() {
    $filter = new TextClickTracker();
    $msg = 'See this: https://example.com/foo/bar?a=b#cid={contact.id}';
    $result = $filter->filterContent($msg, 1, 1);
    $this->assertEquals('See this: http://example.com/extern?u=1&qid=1#cid={contact.id}', $result);
  }

  /**
   * Example: Test that a link with tokens in the fragment works.
   *
   * Seems browsers maintain the fragment when they receive a redirect, so a
   * token here might still work.
   */
  public function testLinkWithTokensInQueryAndFragment() {
    $filter = new TextClickTracker();
    $msg = 'See this: https://example.com/foo/bar?a=b&cid={contact.id}#cid={contact.id}';
    $result = $filter->filterContent($msg, 1, 1);
    $this->assertEquals('See this: http://example.com/extern?u=1&qid=1&cid={contact.id}#cid={contact.id}', $result);
  }

  /**
   * We can't handle tokens in the domain so it should not be tracked.
   */
  public function testLinkWithTokensInDomainFails() {
    $filter = new TextClickTracker();
    $msg = 'See this: https://{some.domain}.com/foo/bar';
    $result = $filter->filterContent($msg, 1, 1);
    $this->assertEquals('See this: https://{some.domain}.com/foo/bar', $result);
  }

  /**
   * We can't handle tokens in the path so it should not be tracked.
   */
  public function testLinkWithTokensInPathFails() {
    $filter = new TextClickTracker();
    $msg = 'See this: https://example.com/{some.path}';
    $result = $filter->filterContent($msg, 1, 1);
    $this->assertEquals('See this: https://example.com/{some.path}', $result);
  }

}
