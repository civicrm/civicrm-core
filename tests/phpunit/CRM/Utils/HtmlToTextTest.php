<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Utils_HtmlToTextTest extends CiviUnitTestCase {
  protected $_testInput = array(
    '<br><p>' => '', // empty test
    '
<p>
This is a paragraph with <b>Bold</b> and <i>italics</i>
Also some <a href="http://www.example.com">hrefs</a> and a
few <mailto:"info@example.org">mailto</mailto> tags.
This is also a really long long line' => '
This is a paragraph with BOLD and _italics_ Also some hrefs [1] and a few
mailto tags. This is also a really long long line

Links:
------
[1] http://www.example.com
'
  );

  function get_info() {
    return array(
      'name' => 'HtmlToText Test',
      'description' => 'Test htmlToText Function',
      'group' => 'CiviCRM BAO Tests',
    );
  }

  function setUp() {
    parent::setUp();
  }

  function testHtmlToText() {
    foreach ($this->_testInput as $html => $text) {
      $output = CRM_Utils_String::htmlToText($html);
      $this->assertEquals(
        trim($output),
        trim($text),
        "Text Output did not match for $html"
      );
    }
  }
}

