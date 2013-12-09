<?php

namespace Civi\Tests\Factories\Price;

use \Civi\Tests\Factories;

class FieldValue extends Factories\Base
{
  static function build()
  {
    $price_field_value = new \Civi\Price\FieldValue();
    $price_field_value->setName('Test');
    $price_field_value->setLabel('Test');
    $price_field_value->setAmount(22);
    $price_field_value->setDeductibleAmount(0);
    $price_field_value->setIsActive(TRUE);
    return $price_field_value;
  }
}
