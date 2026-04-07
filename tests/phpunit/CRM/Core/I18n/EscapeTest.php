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

use Civi\Test\Invasive;

/**
 * @group headless
 * @group locale
 */
class CRM_Core_I18n_EscapeTest extends CiviUnitTestCase {

  /**
   * Data provider for testEscape
   *
   * @return array
   */
  public function escapeDataProvider(): array {
    return [
      'no escape mode' => [
        'Hello & World',
        '',
        'Hello & World',
      ],
      'html escape special chars' => [
        'Hello & <World>',
        'html',
        'Hello &amp; &lt;World&gt;',
      ],
      'htmlattribute escape special chars' => [
        'Hello & "World"',
        'htmlattribute',
        'Hello &amp; &quot;World&quot;',
      ],
      'js escape special chars' => [
        "Hello 'World' & <script>",
        'js',
        "Hello \u0027World\u0027 \u0026 \u003Cscript\u003E",
      ],
      'invalid mode throws exception' => [
        'test',
        'invalid',
        'Invalid escape mode: invalid',
        TRUE,
      ],
    ];
  }

  /**
   * @dataProvider escapeDataProvider
   * @param string $input Text to escape
   * @param string $mode Escape mode
   * @param string $expected Expected result
   * @param bool $expectException Whether to expect an exception
   */
  public function testEscape(
    string $input,
    string $mode,
    string $expected,
    bool $expectException = FALSE,
  ): void {
    if ($expectException) {
      $this->expectException(Exception::class);
      $this->expectExceptionMessage($expected);
    }

    $result = Invasive::call(['CRM_Core_I18n', 'escape'], [$input, $mode]);

    if (!$expectException) {
      $this->assertEquals($expected, $result);
    }
  }

}
