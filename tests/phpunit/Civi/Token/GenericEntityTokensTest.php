<?php
namespace Civi\Token;

/**
 * @group headless
 */
class GenericEntityTokensTest extends \CiviUnitTestCase {

  /**
   * Only Custom fields, or fields whose metadata declares `usage: token`,
   * should be exposed as tokens.
   */
  public function testGetExposedFieldsFiltersByUsage(): void {
    $tokens = new class('Contact') extends GenericEntityTokens {

      protected function getFieldMetadata(): array {
        return [
          'exposed_field' => ['name' => 'exposed_field', 'type' => 'String', 'usage' => ['token']],
          'hidden_field' => ['name' => 'hidden_field', 'type' => 'String', 'usage' => ['export']],
          'no_usage_field' => ['name' => 'no_usage_field', 'type' => 'String'],
          'custom_field' => ['name' => 'custom_field', 'type' => 'Custom', 'usage' => ['export']],
        ];
      }

      public function getExposedFieldsForTest(): array {
        return $this->getExposedFields();
      }

    };

    $this->assertEquals(['exposed_field', 'custom_field'], $tokens->getExposedFieldsForTest());
  }

}
