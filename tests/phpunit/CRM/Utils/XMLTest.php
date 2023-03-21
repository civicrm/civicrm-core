<?php

/**
 * Class CRM_Utils_XMLTest
 * @group headless
 */
class CRM_Utils_XMLTest extends CiviUnitTestCase {

  /**
   * Set up for tests.
   */
  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  public function testFilterMarkupTest(): void {
    $examples = [
      ['<b>', 'mb_strtoupper', '<b>'],
      ['<b>Ok</b>', 'mb_strtoupper', '<b>OK</b>'],
      ['<b>Ok</b>', 'mb_strtolower', '<b>ok</b>'],
      ['<b>This &amp; That</b>', 'mb_strtoupper', '<b>THIS &amp; THAT</b>'],
      ['<b>This &amp; That</b>', 'mb_strtolower', '<b>this &amp; that</b>'],
      ['One<b>Two</b>Three', 'mb_strtoupper', 'ONE<b>TWO</b>THREE'],
      ['One<b>Two</b>Three', 'mb_strtolower', 'one<b>two</b>three'],
      ['<a href="https://example.com/FooBar">The Foo Bar</a>', 'mb_strtoupper', '<a href="https://example.com/FooBar">THE FOO BAR</a>'],
      ['<a href="https://example.com/FooBar">The Foo Bar</a>', 'mb_strtolower', '<a href="https://example.com/FooBar">the foo bar</a>'],
      ['<a onclick="window.location=\'https://google.COM\'" target=\'_blank\'>The Foo Bar</a>', 'mb_strtoupper', '<a onclick="window.location=\'https://google.COM\'" target=\'_blank\'>THE FOO BAR</a>'],
    ];
    foreach ($examples as $example) {
      [$input, $filter, $expect] = $example;
      $actual = CRM_Utils_XML::filterMarkupText($input, $filter);
      $this->assertEquals($expect, $actual, sprintf('Filter "%s" via "%s"', $input, $filter));
    }
  }

}
