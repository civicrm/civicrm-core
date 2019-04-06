<?php

/**
 * Class CRM_Utils_HtmlToTextTest
 * @group headless
 */
class CRM_Utils_HtmlToTextTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * @return array
   */
  public function htmlToTextExamples() {
    // array(0 => string $html, 1 => string $text)
    $cases = array();

    $cases[] = array(
      '<br/><p>',
      '',
    );

    $cases[] = array(
      "\n<p>\n" .
      "This is a paragraph with <b>Bold</b> and <i>italics</i>\n" .
      "Also some <a href=\"http://www.example.com\">hrefs</a> and a\n" .
      "few <mailto:\"info@example.org\">mailto</mailto> tags.\n" .
      "This is also a really long long line\n" .
      "\n",
      "This is a paragraph with BOLD and _italics_ Also some hrefs [1] and a few\n" .
      "mailto tags. This is also a really long long line\n" .
      "\n" .
      "Links:\n" .
      "------\n" .
      "[1] http://www.example.com\n" .
      "",
    );

    $cases[] = array(
      "<p>\nA <a href=\"{action.do_something}\">token</a>\nis not treated as a relative URL",
      "A token [1] is not treated as a relative URL\n" .
      "\n" .
      "Links:\n" .
      "------\n" .
      "[1] {action.do_something}\n",
    );

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
      trim($output),
      trim($text),
      "Text Output did not match for $html"
    );
  }

}
