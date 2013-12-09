<?php

namespace Civi\Tests\Factories\Price;

use \Civi\Tests\Factories;

class Field extends Factories\Base
{
  static function build()
  {
    $price_field = new \Civi\Price\Field();
    $price_field->setName('Test');
    $price_field->setLabel('Test');
    $price_field->setHtmlType('Text');
    $price_field->setIsActive(TRUE);
    $price_field->addPriceFieldValue(FieldValue::build());
    return $price_field;
  }
}
