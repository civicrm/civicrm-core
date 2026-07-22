<?php
namespace Civi\Token;

use CiviUnitTestCase;

/**
 * @group headless
 */
class TokenCompatSubscriberTest extends CiviUnitTestCase {

  /**
   * Test conditional punctuation rendering.
   *
   * @dataProvider conditionalPunctuationDataProvider
   */
  public function testRenderConditionalPunctuation(string $input, string $expected): void {
    $this->assertEquals($expected, TokenCompatSubscriber::renderConditionalPunctuation($input));
  }

  public function conditionalPunctuationDataProvider(): array {
    return [
      'simple space separation' => ['John{ }Smith', 'John Smith'],
      'leading space removal' => ['{ }Smith', 'Smith'],
      'trailing space removal' => ['John{ }', 'John'],
      'comma space separation' => ['San Francisco{, }CA', 'San Francisco, CA'],
      'trailing comma space removal' => ['San Francisco{, }', 'San Francisco'],
      'leading comma space removal' => ['{, }CA', 'CA'],
      'parentheses paired' => ['John{ (}Johnny{) }Smith', 'John (Johnny) Smith'],
      'parentheses paired removed when missing inner' => ['John{ (}{) }Smith', 'JohnSmith'],
      'address block style' => ['123 Main St{, }{ }Suite 100', '123 Main St, Suite 100'],
      'multiple adjacent curlies' => ['John{ }{, }Smith', 'John, Smith'],
      'empty input' => ['', ''],
    ];
  }

}
