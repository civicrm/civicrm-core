<?php

class CRM_Utils_Geocode_TestProvider {

  public static function format(&$values, $stateName = FALSE) {
    if ($values['street_address'] == 'Does not exist') {
      $values['geo_code_1'] = $values['geo_code_2'] = 'null';
    }
  }

}
