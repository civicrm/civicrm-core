<?php

/**
 * Class CRM_Utils_HtmlToTextTest
 * @group headless
 */
class CRM_Utils_HtmlToTextTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  /**
   * @return array
   */
  public function htmlToTextExamples() {
    // array(0 => string $html, 1 => string $text)
    $cases = [];

    $cases[] = [
      '<br/><p>',
      '',
    ];

    $cases[] = [
      "\n<p>\n" .
      "This is a paragraph with <b>Bold</b> and <i>italics</i>.\n" .
      "Also some <a href=\"http://www.example.com\">hrefs</a> and a\n" .
      "few <mailto:\"info@example.org\">mailto</mailto> tags.\n" .
      "This is also a really long long line\n" .
      "\n",
      "This is a paragraph with Bold and italics. Also some [hrefs](http://www.example.com)" .
      " and a few mailto tags. This is also a really long long line",
    ];

    $cases[] = [
      "<p>\nA <a href=\"{action.do_something}\">token</a>\nis not treated as a relative URL",
      "A [token]({action.do_something}) is not treated as a relative URL",
    ];

    return $cases;
  }

  /**
   * @param string $html
   *   Example HTML input.
   * @param string $text
   *    Expected text output.
   * @dataProvider htmlToTextExamples
   */
  public function testHtmlToText($html, $text) {
    $output = CRM_Utils_String::htmlToText($html);
    $this->assertEquals(
      trim($text),
      trim($output),
      "Text Output did not match for $html"
    );
  }

}
