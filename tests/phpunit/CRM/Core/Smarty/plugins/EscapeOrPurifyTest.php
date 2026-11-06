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

/**
 * Test class for the escapeOrPurify Smarty modifier.
 *
 * @package CRM
 * @group headless
 */
class CRM_Core_Smarty_plugins_EscapeOrPurifyTest extends CiviUnitTestCase {

  /**
   * Data provider for testing escapeOrPurify.
   *
   * @return array
   */
  public function escapeOrPurifyDataProvider(): array {
    return [
      'null value' => [NULL, ''],
      'empty string' => ['', ''],
      'integer number' => [123, '123'],
      'float number' => [45.67, '45.67'],
      'plain text without special chars' => ['Hello World', 'Hello World'],
      'plain text with bare ampersand' => ['Ben & Jerry', 'Ben &amp; Jerry'],
      'plain text with quotes' => ['Single \'quote\' and "double"', 'Single &#039;quote&#039; and &quot;double&quot;'],
      'plain text with single less-than' => ['x < 5', 'x &lt; 5'],
      'plain text with single greater-than' => ['y > 10', 'y &gt; 10'],
      'plain text with both' => ['y > 10 && x < 11', 'y &gt; 10 &amp;&amp; x &lt; 11'],
      'markup without angle brackets (HTML entity)' => ['Hello &amp; Welcome', 'Hello &amp; Welcome'],
      'markup with entity and bare ampersand' => ['Caf&eacute; & bar', 'Caf&eacute; &amp; bar'],
      'markup with multiple entities' => ['&quot;Hello&quot; &amp; &copy; 2026', '&quot;Hello&quot; &amp; &copy; 2026'],
      'safe markup tags' => ['<a href="https://civicrm.org">CiviCRM</a>', '<a href="https://civicrm.org">CiviCRM</a>'],
      'safe formatting tags' => ['<b>Bold</b> & <i>Italic</i>', '<b>Bold</b> &amp; <i>Italic</i>'],
      'line breaks' => ['Line 1<br />Line 2', 'Line 1<br />Line 2'],
      'safe markup with existing entity' => ['<p>Hello &amp; Welcome</p>', '<p>Hello &amp; Welcome</p>'],
      'dangerous script tag is stripped' => ['<script>alert(1)</script>', ''],
      'dangerous attribute is stripped' => ['<a href="javascript:alert(1)">click</a>', '<a>click</a>'],
    ];
  }

  /**
   * Test escapeOrPurify modifier function directly and through Smarty parsing.
   *
   * @dataProvider escapeOrPurifyDataProvider
   */
  public function testEscapeOrPurify($input, string $expected): void {
    require_once 'CRM/Core/Smarty/plugins/modifier.escapeOrPurify.php';
    $this->assertSame($expected, smarty_modifier_escapeOrPurify($input));

    $actualSmarty = CRM_Utils_String::parseOneOffStringThroughSmarty('{$val|escapeOrPurify}', ['val' => $input]);
    $this->assertSame($expected, $actualSmarty);
  }

}
