<?php
namespace Civi\FlexMailer;

use Civi\Core\Event\GenericHookEvent;
use Civi\Test\HeadlessInterface;
use Civi\Core\HookInterface;
use Civi\Test\TransactionalInterface;

use Civi\FlexMailer\ClickTracker\TextClickTracker;
use Civi\FlexMailer\ClickTracker\HtmlClickTracker;

/**
 * Tests that URLs are converted to tracked ones if at all possible.
 *
 * @group headless
 */
class ClickTrackerTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  protected static $mockTrackedUrl = 'http://example.com/extern?u=1';

  protected $mailing_id;

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public static function on_civi_mailing_track(GenericHookEvent $event) {
    if (static::$mockTrackedUrl) {
      $event->url = static::$mockTrackedUrl;
      $event->stopPropagation();
    }
  }

  /**
   * Example: Test that a link without any tokens works.
   */
  public function testLinkWithoutTokens(): void {
    $filter = new TextClickTracker();
    $msg = 'See this: https://example.com/foo/bar?a=b&c=d#frag';
    $result = $filter->filterContent($msg, 1, 1);
    $this->assertEquals('See this: http://example.com/extern?u=1&qid=1', $result);
  }

  /**
   * Example: Test that a link with tokens in the query works.
   */
  public function testLinkWithTokensInQueryWithStaticParams(): void {
    $filter = new TextClickTracker();
    $msg = 'See this: https://example.com/foo/bar?a=b&cid={contact.id}';
    $result = $filter->filterContent($msg, 1, 1);
    $this->assertEquals('See this: http://example.com/extern?u=1&qid=1&cid={contact.id}', $result);
  }

  /**
   * Example: Test that a link with tokens in the query works.
   */
  public function testLinkWithTokensInQueryWithMultipleStaticParams(): void {
    $filter = new TextClickTracker();
    $msg = 'See this: https://example.com/foo/bar?cs={contact.checksum}&a=b&cid={contact.id}';
    $result = $filter->filterContent($msg, 1, 1);
    $this->assertEquals('See this: http://example.com/extern?u=1&qid=1&cs={contact.checksum}&cid={contact.id}', $result);
  }

  /**
   * Example: Test that a link with tokens in the query works.
   */
  public function testLinkWithTokensInQueryWithMultipleStaticParamsHtml(): void {
    $filter = new HtmlClickTracker();
    $msg = '<a href="https://example.com/foo/bar?cs={contact.checksum}&amp;a=b&amp;cid={contact.id}">See this</a>';
    $result = $filter->filterContent($msg, 1, 1);
    $this->assertEquals('<a href="http://example.com/extern?u=1&amp;qid=1&amp;cs={contact.checksum}&amp;cid={contact.id}" rel=\'nofollow\'>See this</a>', $result);
  }

  /**
   * Example: Test that a link with tokens in the query works.
   */
  public function testLinkWithTokensInQueryWithoutStaticParams(): void {
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
  public function testLinkWithTokensInFragment(): void {
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
  public function testLinkWithTokensInQueryAndFragment(): void {
    $filter = new TextClickTracker();
    $msg = 'See this: https://example.com/foo/bar?a=b&cid={contact.id}#cid={contact.id}';
    $result = $filter->filterContent($msg, 1, 1);
    $this->assertEquals('See this: http://example.com/extern?u=1&qid=1&cid={contact.id}#cid={contact.id}', $result);
  }

  /**
   * We can't handle tokens in the domain so it should not be tracked.
   */
  public function testLinkWithTokensInDomainFails(): void {
    $filter = new TextClickTracker();
    $msg = 'See this: https://{some.domain}.com/foo/bar';
    $result = $filter->filterContent($msg, 1, 1);
    $this->assertEquals('See this: https://{some.domain}.com/foo/bar', $result);
  }

  /**
   * We can't handle tokens in the path so it should not be tracked.
   */
  public function testLinkWithTokensInPathFails(): void {
    $filter = new TextClickTracker();
    $msg = 'See this: https://example.com/{some.path}';
    $result = $filter->filterContent($msg, 1, 1);
    $this->assertEquals('See this: https://example.com/{some.path}', $result);
  }

  public function testLinkWithUnicode(): void {
    $filter = new TextClickTracker();
    $msg = 'See this: https://civińcrm.org';
    $result = $filter->filterContent($msg, 1, 1);
    $this->assertEquals('See this: http://example.com/extern?u=1&qid=1', $result);
  }

  public function testHtmlLinkWithUnicode(): void {
    $filter = new HtmlClickTracker();
    $msg = '<p><a href="https://civińcrm.org">See This</a></p>';
    $result = $filter->filterContent($msg, 1, 1);
    $this->assertEquals('<p><a href="http://example.com/extern?u=1&amp;qid=1" rel=\'nofollow\'>See This</a></p>', $result);
  }

  public function testTraditionalViewMailingTokenFormat(): void {
    static::$mockTrackedUrl = NULL;
    $filter = new HtmlClickTracker();
    $msg = '<p><a href="http://civicrm.org/civicrm/mailing/view?id={mailing.key}&{contact.checksum}&cid={contact.contact_id}">View online</a></p>';
    $result = $filter->filterContent($msg, 1, 1);
    $this->assertEquals('<p><a href="http://civicrm.org/civicrm/mailing/view?id={mailing.key}&amp;{contact.checksum}&amp;cid={contact.contact_id}" rel=\'nofollow\'>View online</a></p>', $result);
  }

}
