<?php

namespace Civi\Core\Annotations;

/**
 * @Annotation
 */
class Field
{
  public $localizable = FALSE;
  public $id_column;
  public $type_column;

  public function __construct($properties)
  {
    foreach ($properties as $property_name => $property_value) {
      if (!property_exists($this, $property_name)) {
        throw new \Exception("There is no property called '$property_name' in the Field annotation. Valid properties: localizable, id_column, type_column.");
      }
      $this->$property_name = $property_value; 
    }
  }
}
