<?php

namespace api\v4\Utils;

use api\v4\Api4TestBase;
use Civi\Api4\Utils\FormattingUtil;

/**
 * @group headless
 */
class FormattingUtilTest extends Api4TestBase {

  /**
   * @dataProvider getFilterByPathExamples
   * @param string $fieldPath
   * @param string $fieldName
   * @param array $originalValues
   * @param array $expectedValues
   */
  public function testFilterByPath(string $fieldPath, string $fieldName, array $originalValues, array $expectedValues): void {
    $this->assertEquals($expectedValues, FormattingUtil::filterByPath($originalValues, $fieldPath, $fieldName));
  }

  public function getFilterByPathExamples(): array {
    $originalValueSets = [
      [
        'id' => 'a',
        'name' => 'a_name',
        'id_foo.id' => 'b',
        'id_foo.name' => 'b_name',
        'id.id_foo.id' => 'c',
        'id.id_foo.name' => 'c_name',
      ],
    ];
    return [
      ['id', 'id', $originalValueSets[0], $originalValueSets[0]],
      ['id_foo.id', 'id', $originalValueSets[0], ['id' => 'b', 'name' => 'b_name']],
      ['id.id_foo.id', 'id', $originalValueSets[0], ['id' => 'c', 'name' => 'c_name']],
    ];
  }

}
