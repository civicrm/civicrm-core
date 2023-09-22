<?php

namespace Civi\Api4\Action\WorkflowMessage;

/**
 * Class GetTemplateFields
 * @package Civi\Api4\Action\WorkflowMessage
 *
 * @method $this setWorkflow(string $workflow)
 * @method string getWorkflow()
 * @method $this setFormat(string $workflow)
 * @method string getFormat()
 */
class GetTemplateFields extends \Civi\Api4\Generic\BasicGetAction {

  /**
   * @var string
   * @required
   */
  public $workflow;

  /**
   * Controls the return format.
   *  - 'metadata': Return the fields as an array of metadata
   *  - 'example': Return the fields as an example record (a basis for passing into Render::$values).
   *
   * @var string
   * @options metadata,example
   */
  protected $format = 'metadata';

  protected function getRecords() {
    $item = \Civi\WorkflowMessage\WorkflowMessage::create($this->workflow);
    /** @var \Civi\WorkflowMessage\FieldSpec[] $fields */
    $fields = $item->getFields();
    $array = [];
    $genericExamples = [
      'string[]' => ['example-string1', 'example-string2...'],
      'string' => 'example-string',
      'int[]' => [1, 2, 3],
      'int' => 123,
      'double[]' => [1.23, 4.56],
      'double' => 1.23,
      'array' => [],
    ];

    switch ($this->format) {
      case 'metadata':
        foreach ($fields as $name => $field) {
          $array[$name] = $field->toArray();
        }
        return $array;

      case 'example':
        foreach ($fields as $name => $field) {
          $array[$name] = NULL;
          foreach (array_intersect(array_keys($genericExamples), $field->getType()) as $ex) {
            $array[$name] = $genericExamples[$ex];
          }
        }
        ksort($array);
        return [$array];

      default:
        throw new \RuntimeException("Unrecognized format");
    }
  }

}
