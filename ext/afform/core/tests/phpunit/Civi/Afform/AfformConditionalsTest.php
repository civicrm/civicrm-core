<?php
namespace Civi\Afform;

use Civi\Api4\Action\Afform\AbstractProcessor;
use Civi\Test\HeadlessInterface;
use Override;

/**
 * @group headless
 */
class AfformConditionalsTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()->installMe(__DIR__)->apply();
  }

  /**
   * Get a concrete child of AbstractProcessor to test conditionals
   */
  protected function getConditionalProcessor() {
    return new class extends AbstractProcessor {
      public function __construct() {
        // no entity name needed
      }

      protected function processForm() {
        // no processing to do
      }

      /**
       * Take a conditional expression as text, parse it and then evaluate it and
       * return the result
       * @param string $expression
       * @param array $entityValues
       */
      public function processConditional(string $expression, array $entityValues) {
        $this->_entityValues = $entityValues;
        return $this->checkAfformConditional(FormDataModel::parseExpression(\html_entity_decode($expression)));
      }
    };
  }

  public function testBooleanComposition() {
    $processor = $this->getConditionalProcessor();

    $entityValues = [
      'Individual1' => [
        0 => [
          'fields' => [
            'first_name' => 'a',
            'middle_name' => 'b',
            'last_name' => 'c',

          ],
        ],
      ],
    ];

    // first_name = A OR first_name contains BC should be TRUE
    $expression = '([[&amp;quot;OR&amp;quot;,[[&amp;quot;Individual1[0][fields][first_name]&amp;quot;,&amp;quot;=&amp;quot;,&amp;quot;\&amp;quot;A\&amp;quot;&amp;quot;],[&amp;quot;Individual1[0][fields][first_name]&amp;quot;,&amp;quot;CONTAINS&amp;quot;,&amp;quot;\&amp;quot;BC\&amp;quot;&amp;quot;]]]])';
    $result = $processor->processConditional($expression, $entityValues);
    $this->assertTrue($result, 'TRUE OR FALSE = TRUE');

    // first_name = B OR first_name contains A should be TRUE
    $expression = '([[&amp;quot;OR&amp;quot;,[[&amp;quot;Individual1[0][fields][first_name]&amp;quot;,&amp;quot;=&amp;quot;,&amp;quot;\&amp;quot;B\&amp;quot;&amp;quot;],[&amp;quot;Individual1[0][fields][first_name]&amp;quot;,&amp;quot;CONTAINS&amp;quot;,&amp;quot;\&amp;quot;A\&amp;quot;&amp;quot;]]]])';
    $result = $processor->processConditional($expression, $entityValues);
    $this->assertTrue($result, 'FALSE OR TRUE = TRUE');

    // first_name = B OR first_name contains C should be FALSE
    $expression = '([[&amp;quot;OR&amp;quot;,[[&amp;quot;Individual1[0][fields][first_name]&amp;quot;,&amp;quot;=&amp;quot;,&amp;quot;\&amp;quot;B\&amp;quot;&amp;quot;],[&amp;quot;Individual1[0][fields][first_name]&amp;quot;,&amp;quot;CONTAINS&amp;quot;,&amp;quot;\&amp;quot;C\&amp;quot;&amp;quot;]]]])';
    $result = $processor->processConditional($expression, $entityValues);
    $this->assertFalse($result, 'FALSE OR FALSE = FALSE');

    // first_name = a AND last_name contains C should be TRUE
    $expression = '([[&amp;quot;AND&amp;quot;,[[&amp;quot;Individual1[0][fields][first_name]&amp;quot;,&amp;quot;=&amp;quot;,&amp;quot;\&amp;quot;a\&amp;quot;&amp;quot;],[&amp;quot;Individual1[0][fields][last_name]&amp;quot;,&amp;quot;CONTAINS&amp;quot;,&amp;quot;\&amp;quot;C\&amp;quot;&amp;quot;]]]])';
    $result = $processor->processConditional($expression, $entityValues);
    $this->assertTrue($result, 'TRUE AND TRUE = TRUE');

    // first_name = a AND last_name contains D should be FALSE
    $expression = '([[&amp;quot;AND&amp;quot;,[[&amp;quot;Individual1[0][fields][first_name]&amp;quot;,&amp;quot;=&amp;quot;,&amp;quot;\&amp;quot;a\&amp;quot;&amp;quot;],[&amp;quot;Individual1[0][fields][last_name]&amp;quot;,&amp;quot;CONTAINS&amp;quot;,&amp;quot;\&amp;quot;D\&amp;quot;&amp;quot;]]]])';
    $result = $processor->processConditional($expression, $entityValues);
    $this->assertFalse($result, 'TRUE AND FALSE = FALSE');

    // NOT (first_name = C, last_name contains D) should be TRUE
    $expression = '([[&amp;quot;NOT&amp;quot;,[[&amp;quot;Individual1[0][fields][first_name]&amp;quot;,&amp;quot;=&amp;quot;,&amp;quot;\&amp;quot;C\&amp;quot;&amp;quot;],[&amp;quot;Individual1[0][fields][last_name]&amp;quot;,&amp;quot;CONTAINS&amp;quot;,&amp;quot;\&amp;quot;D\&amp;quot;&amp;quot;]]]])';
    $result = $processor->processConditional($expression, $entityValues);
    $this->assertTrue($result, 'NOT(FALSE,FALSE) = TRUE');

    // NOT (first_name = A, last_name contains D) should be FALSE
    $expression = '([[&amp;quot;NOT&amp;quot;,[[&amp;quot;Individual1[0][fields][first_name]&amp;quot;,&amp;quot;=&amp;quot;,&amp;quot;\&amp;quot;A\&amp;quot;&amp;quot;],[&amp;quot;Individual1[0][fields][last_name]&amp;quot;,&amp;quot;CONTAINS&amp;quot;,&amp;quot;\&amp;quot;D\&amp;quot;&amp;quot;]]]])';
    $result = $processor->processConditional($expression, $entityValues);
    $this->assertFalse($result, 'NOT(TRUE,FALSE) = FALSE');
  }

  public function testJsonScalars() {
    $processor = $this->getConditionalProcessor();

    // JSON scalars should be interchangeable in compare and submit values
    $expressions = [
      '["[Individual1.0.first_name]","=","123"]',
      '["[Individual1.0.first_name]","=",123]',
    ];

    $submitValues = ['123', 123];

    foreach ($expressions as $expression) {

      foreach ($submitValues as $submitValue) {
        $entityValues = [
          'Individual1' => [
            0 => [
              'fields' => [
                'first_name' => $submitValue,
              ],
            ],
          ],
        ];
        $shouldBeTrue = $processor->processConditional($expression, $entityValues);
        $this->assertTrue($shouldBeTrue);
      }
    }

    $submitValues = ['124', 124];
    foreach ($expressions as $expression) {
      foreach ($submitValues as $submitValue) {
        $entityValues = [
          'Individual1' => [
            0 => [
              'fields' => [
                'first_name' => $submitValue,
              ],
            ],
          ],
        ];
        $shouldBeFalse = $processor->processConditional($expression, $entityValues);
        $this->assertFalse($shouldBeFalse);
      }
    }
  }

}