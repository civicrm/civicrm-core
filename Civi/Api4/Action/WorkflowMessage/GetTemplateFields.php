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
   * @dynamicFieldControl
   */
  protected $workflow;

  /**
   * Controls the return format.
   *  - 'metadata': Return the fields as an array of metadata
   *  - 'example': Return the fields as an example record (a basis for passing into Render::$values).
   *
   * @var string
   * @options metadata,example
   * @dynamicFieldControl
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

  /**
   * Describe the records this action returns, which are not fields of a
   * workflow but of one workflow's message model.
   *
   * Without this, `select: ['*']` expands to the fields of a workflow and
   * every column of the returned records is dropped.
   *
   * @param \Civi\Api4\Generic\BasicGetFieldsAction $getFields
   *
   * @return array
   */
  public function fields($getFields = NULL) {
    $format = $getFields ? $getFields->getFormat() : NULL;
    $workflow = $getFields ? $getFields->getWorkflow() : NULL;

    if ($format === 'example') {
      // One record, keyed by the model's own field names.
      if (!$workflow) {
        return [];
      }
      $fields = [];
      foreach (\Civi\WorkflowMessage\WorkflowMessage::create($workflow)->getFields() as $name => $spec) {
        $fields[] = [
          'name' => $name,
          'title' => $spec->getTitle() ?: $name,
          'description' => $spec->getDescription(),
          'data_type' => $spec->getDataType(),
        ];
      }
      return $fields;
    }

    // Columns of a Civi\WorkflowMessage\FieldSpec, whatever they happen to be:
    // toArray() reflects over the spec's public properties, so deriving the
    // list here keeps the two from drifting apart.
    $described = [
      'scope' => ts('Subsystems this field is shared with, keyed by subsystem: tokenContext, tplParams or envelope.'),
      'type' => ts('PHP types accepted by this field, as declared by its @var annotation.'),
      'comment' => ts('Remainder of the docblock, after the description.'),
    ];
    $fields = [];
    foreach (array_keys((new \Civi\WorkflowMessage\FieldSpec())->toArray()) as $name) {
      $field = ['name' => $name];
      if (isset($described[$name])) {
        $field['description'] = $described[$name];
      }
      $fields[] = $field;
    }
    return $fields;
  }

}
