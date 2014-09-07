<?php

namespace Civi\Core\Annotations;

/**
 * @Annotation
 */
class Field
{
  public $localizable = FALSE;

  public function __construct($properties)
  {
    foreach ($properties as $property_name => $property_value) {
      if (!property_exists($this, $property_name)) {
        throw new \Exception("Only the 'localizable' property is valid for a Field annotation.");
      }
      $this->$property_name = $property_value; 
    }
  }
}
