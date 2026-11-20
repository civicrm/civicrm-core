<?php

/**
 * Test CRM_Utils_API_HTMLInputCoder::transcode().
 *
 * 'title' is stored as 'html-ish' (partially encoded); 'description' is in the skip list,
 * so it is stored as 'plain'.
 *
 * @group headless
 */
class CRM_Utils_API_HTMLInputCoderTest extends CiviUnitTestCase {

  /**
   * @return array
   *   [field, stored value, output format, expected value]
   */
  public static function transcodeScalarProvider(): array {
    return [
      'html-ish => html-ish' => ['title', 'A & B &lt;b&gt;', 'html-ish', 'A & B &lt;b&gt;'],
      'html-ish => plain' => ['title', 'A & B &lt;b&gt;', 'plain', 'A & B <b>'],
      'html-ish => html' => ['title', 'A & B &lt;b&gt;', 'html', 'A &amp; B &lt;b&gt;'],
      'plain => plain' => ['description', 'A & B <b>', 'plain', 'A & B <b>'],
      'plain => html-ish' => ['description', 'A & B <b>', 'html-ish', 'A & B &lt;b&gt;'],
      'plain => html' => ['description', 'A & B <b>', 'html', 'A &amp; B &lt;b&gt;'],
    ];
  }

  /**
   * @dataProvider transcodeScalarProvider
   */
  public function testTranscodeScalar(string $field, string $storedValue, string $outputFormat, string $expected): void {
    $this->assertSame($expected, CRM_Utils_API_HTMLInputCoder::singleton()->transcode($field, $storedValue, $outputFormat));
  }

  /**
   * @return array
   *   [field, stored value, output format, expected value]
   */
  public static function transcodeArrayProvider(): array {
    $stored = [
      7 => 'A &lt;b&gt;',
      'key' => NULL,
      12 => ['nested' => 'C &lt;i&gt;', 'empty' => ''],
    ];
    return [
      'html-ish => html-ish returns the array unchanged' => ['title', $stored, 'html-ish', $stored],
      'html-ish => plain converts every element and keeps keys' => [
        'title',
        $stored,
        'plain',
        [7 => 'A <b>', 'key' => NULL, 12 => ['nested' => 'C <i>', 'empty' => '']],
      ],
      'html-ish => html converts every element and keeps keys' => [
        'title',
        $stored,
        'html',
        [7 => 'A &lt;b&gt;', 'key' => NULL, 12 => ['nested' => 'C &lt;i&gt;', 'empty' => '']],
      ],
      'plain => plain returns the array unchanged' => ['description', [3 => 'A <b>', 'key' => NULL], 'plain', [3 => 'A <b>', 'key' => NULL]],
      'plain => html-ish converts every element and keeps keys' => [
        'description',
        [3 => 'A <b>', 'key' => NULL],
        'html-ish',
        [3 => 'A &lt;b&gt;', 'key' => NULL],
      ],
      'empty array' => ['title', [], 'plain', []],
    ];
  }

  /**
   * @dataProvider transcodeArrayProvider
   */
  public function testTranscodeArray(string $field, array $storedValue, string $outputFormat, array $expected): void {
    $this->assertSame($expected, CRM_Utils_API_HTMLInputCoder::singleton()->transcode($field, $storedValue, $outputFormat));
  }

  /**
   * @return array
   *   [field, output format]
   */
  public static function transcodeNullProvider(): array {
    return [
      'html-ish => html-ish' => ['title', 'html-ish'],
      'html-ish => plain' => ['title', 'plain'],
      'html-ish => html' => ['title', 'html'],
      'plain => plain' => ['description', 'plain'],
      'plain => html-ish' => ['description', 'html-ish'],
      'plain => html' => ['description', 'html'],
    ];
  }

  /**
   * @dataProvider transcodeNullProvider
   */
  public function testTranscodeNull(string $field, string $outputFormat): void {
    $this->assertNull(CRM_Utils_API_HTMLInputCoder::singleton()->transcode($field, NULL, $outputFormat));
  }

  public function testTranscodeInvalidFormat(): void {
    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('Invalid transcode operation (html-ish => bogus)');
    CRM_Utils_API_HTMLInputCoder::singleton()->transcode('title', 'A', 'bogus');
  }

  public function testTranscodeInvalidFormatInArray(): void {
    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('Invalid transcode operation (plain => bogus)');
    CRM_Utils_API_HTMLInputCoder::singleton()->transcode('description', ['A'], 'bogus');
  }

}
