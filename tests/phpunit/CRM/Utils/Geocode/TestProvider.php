<?php

class CRM_Utils_Geocode_TestProvider {

  public static function format(&$values, $stateName = FALSE) {
    $address = ($values['street_address'] ?? '') . ($values['city'] ?? '');

    $coord = self::getCoordinates($address);

    $values['geo_code_1'] = $coord['geo_code_1'] ?? 'null';
    $values['geo_code_2'] = $coord['geo_code_2'] ?? 'null';

    if (isset($coord['geo_code_error'])) {
      $values['geo_code_error'] = $coord['geo_code_error'];
    }

    return isset($coord['geo_code_1'], $coord['geo_code_2']);
  }

  public static function getCoordinates($address): array {
    if (strpos($address, '600 Pennsylvania Avenue NW, Washington') === 0) {
      return ['geo_code_1' => '38.897957', 'geo_code_2' => '-77.036560'];
    }
    return [];
  }

}
