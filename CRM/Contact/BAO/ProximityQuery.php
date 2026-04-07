<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Contact_BAO_ProximityQuery {

  /**
   * Trigonometry for calculating geographical distances.
   *
   * Modification made in: CRM-13904
   * http://en.wikipedia.org/wiki/Great-circle_distance
   * http://www.movable-type.co.uk/scripts/latlong.html
   *
   * All function arguments and return values measure distances in metres
   * and angles in degrees.  The ellipsoid model is from the WGS-84 datum.
   * Ka-Ping Yee, 2003-08-11
   * earth_radius_semimajor = 6378137.0;
   * earth_flattening = 1/298.257223563;
   * earth_radius_semiminor = $earth_radius_semimajor * (1 - $earth_flattening);
   * earth_eccentricity_sq = 2*$earth_flattening - pow($earth_flattening, 2);
   * This library is an implementation of UCB CS graduate student, Ka-Ping Yee (http://www.zesty.ca).
   * This version has been taken from Drupal's location module: http://drupal.org/project/location
   */

  /**
   * @var float
   */
  static protected $_earthFlattening;

  /**
   * @var float
   */
  static protected $_earthRadiusSemiMinor;

  /**
   * @var float
   */
  static protected $_earthRadiusSemiMajor;

  /**
   * @var float
   */
  static protected $_earthEccentricitySQ;

  public static function initialize() {
    static $_initialized = FALSE;

    if (!$_initialized) {
      $_initialized = TRUE;

      self::$_earthFlattening = 1.0 / 298.257223563;
      self::$_earthRadiusSemiMajor = 6378137.0;
      self::$_earthRadiusSemiMinor = self::$_earthRadiusSemiMajor * (1.0 - self::$_earthFlattening);
      self::$_earthEccentricitySQ = 2 * self::$_earthFlattening - pow(self::$_earthFlattening, 2);
    }
  }

  /*
   * Latitudes in all of U. S.: from -7.2 (American Samoa) to 70.5 (Alaska).
   * Latitudes in continental U. S.: from 24.6 (Florida) to 49.0 (Washington).
   * Average latitude of all U. S. zipcodes: 37.9.
   */

  /**
   * Estimate the Earth's radius at a given latitude.
   * Default to an approximate average radius for the United States.
   *
   * @param float $latitude
   * @return float
   */
  public static function earthRadius($latitude) {
    $lat = deg2rad($latitude);

    $x = cos($lat) / self::$_earthRadiusSemiMajor;
    $y = sin($lat) / self::$_earthRadiusSemiMinor;
    return 1.0 / sqrt($x * $x + $y * $y);
  }

  /**
   * Estimate the min and max longitudes within $distance of a given location.
   *
   * @param float $longitude
   * @param float $latitude
   * @param float $distance
   * @return array
   */
  public static function earthLongitudeRange($longitude, $latitude, $distance) {
    $long = deg2rad($longitude);
    $lat = deg2rad($latitude);
    $radius = self::earthRadius($latitude);

    $angle = $distance / $radius;
    $diff = asin(sin($angle) / cos($lat));
    $minLong = $long - $diff;
    $maxLong = $long + $diff;

    if ($minLong < -pi()) {
      $minLong = $minLong + pi() * 2;
    }

    if ($maxLong > pi()) {
      $maxLong = $maxLong - pi() * 2;
    }

    return [
      rad2deg($minLong),
      rad2deg($maxLong),
    ];
  }

  /**
   * Estimate the min and max latitudes within $distance of a given location.
   *
   * @param float $longitude
   * @param float $latitude
   * @param float $distance
   * @return array
   */
  public static function earthLatitudeRange($longitude, $latitude, $distance) {
    $long = deg2rad($longitude);
    $lat = deg2rad($latitude);
    $radius = self::earthRadius($latitude);

    $angle = $distance / $radius;
    $minLat = $lat - $angle;
    $maxLat = $lat + $angle;
    $rightangle = pi() / 2.0;

    // wrapped around the south pole
    if ($minLat < -$rightangle) {
      $overshoot = -$minLat - $rightangle;
      $minLat = -$rightangle + $overshoot;
      if ($minLat > $maxLat) {
        $maxLat = $minLat;
      }
      $minLat = -$rightangle;
    }

    // wrapped around the north pole
    if ($maxLat > $rightangle) {
      $overshoot = $maxLat - $rightangle;
      $maxLat = $rightangle - $overshoot;
      if ($maxLat < $minLat) {
        $minLat = $maxLat;
      }
      $maxLat = $rightangle;
    }

    return [
      rad2deg($minLat),
      rad2deg($maxLat),
    ];
  }

  /**
   * @param float $latitude
   * @param float $longitude
   * @param float $distance
   * @param string $tablePrefix
   *
   * @return string
   */
  public static function where($latitude, $longitude, $distance, $tablePrefix = 'civicrm_address') {
    self::initialize();

    [$minLongitude, $maxLongitude] = self::earthLongitudeRange($longitude, $latitude, $distance);
    [$minLatitude, $maxLatitude] = self::earthLatitudeRange($longitude, $latitude, $distance);

    // DONT consider NAN values (which is returned by rad2deg php function)
    // for checking BETWEEN geo_code's criteria as it throws obvious 'NAN' field not found DB: Error
    $geoCodeWhere = [];
    if (!is_nan($minLatitude)) {
      $geoCodeWhere[] = "{$tablePrefix}.geo_code_1  >= $minLatitude ";
    }
    if (!is_nan($maxLatitude)) {
      $geoCodeWhere[] = "{$tablePrefix}.geo_code_1  <= $maxLatitude ";
    }
    if (!is_nan($minLongitude)) {
      $geoCodeWhere[] = "{$tablePrefix}.geo_code_2 >= $minLongitude ";
    }
    if (!is_nan($maxLongitude)) {
      $geoCodeWhere[] = "{$tablePrefix}.geo_code_2 <= $maxLongitude ";
    }
    $geoCodeWhereClause = implode(' AND ', $geoCodeWhere);

    $where = "
{$geoCodeWhereClause} AND
ACOS(
    COS(RADIANS({$tablePrefix}.geo_code_1)) *
    COS(RADIANS($latitude)) *
    COS(RADIANS({$tablePrefix}.geo_code_2) - RADIANS($longitude)) +
    SIN(RADIANS({$tablePrefix}.geo_code_1)) *
    SIN(RADIANS($latitude))
  ) * 6378137  <= $distance
";
    return $where;
  }

  /**
   * Process form.
   *
   * @param CRM_Contact_BAO_Query $query
   * @param array $values
   *
   * @return null
   * @throws Exception
   */
  public static function process(&$query, &$values) {
    [$name, $op, $distance, $grouping, $wildcard] = $values;

    // also get values array for all address related info
    $proximityVars = [
      'street_address' => 1,
      'city' => 1,
      'postal_code' => 1,
      'state_province_id' => 0,
      'country_id' => 0,
      'state_province' => 0,
      'country' => 0,
      'distance_unit' => 0,
      'geo_code_1' => 0,
      'geo_code_2' => 0,
    ];

    $proximityAddress = [];
    $qill = [];
    foreach ($proximityVars as $var => $recordQill) {
      $proximityValues = $query->getWhereValues("prox_{$var}", $grouping);
      if (!empty($proximityValues[2])) {
        $proximityAddress[$var] = $proximityValues[2];
        if ($recordQill) {
          $qill[] = $proximityValues[2];
        }
      }
    }

    if (empty($proximityAddress)) {
      return NULL;
    }

    if (isset($proximityAddress['state_province_id'])) {
      $proximityAddress['state_province'] = CRM_Core_PseudoConstant::stateProvince($proximityAddress['state_province_id']);
      $qill[] = $proximityAddress['state_province'];
    }

    $config = CRM_Core_Config::singleton();
    if (!isset($proximityAddress['country_id'])) {
      // get it from state if state is present
      if (isset($proximityAddress['state_province_id'])) {
        $proximityAddress['country_id'] = CRM_Core_PseudoConstant::countryIDForStateID($proximityAddress['state_province_id']);
      }
      elseif (isset($config->defaultContactCountry)) {
        $proximityAddress['country_id'] = $config->defaultContactCountry;
      }
    }

    if (!empty($proximityAddress['country_id'])) {
      $proximityAddress['country'] = CRM_Core_PseudoConstant::country($proximityAddress['country_id']);
      $qill[] = $proximityAddress['country'];
    }

    if (
      isset($proximityAddress['distance_unit']) &&
      $proximityAddress['distance_unit'] == 'miles'
    ) {
      $qillUnits = " {$distance} " . ts('miles');
      $distance = $distance * 1609.344;
    }
    else {
      $qillUnits = " {$distance} " . ts('km');
      $distance = $distance * 1000.00;
    }

    $qill = ts('Proximity search to a distance of %1 from %2',
      [
        1 => $qillUnits,
        2 => implode(', ', $qill),
      ]
    );

    $query->_tables['civicrm_address'] = $query->_whereTables['civicrm_address'] = 1;

    if (empty($proximityAddress['geo_code_1']) || empty($proximityAddress['geo_code_2'])) {
      if (!CRM_Core_BAO_Address::addGeocoderData($proximityAddress)) {
        throw new CRM_Core_Exception(ts('Proximity searching requires you to set a valid geocoding provider'));
      }
    }

    if (
      !is_numeric($proximityAddress['geo_code_1'] ?? '') ||
      !is_numeric($proximityAddress['geo_code_2'] ?? '')
    ) {
      // we are setting the where clause to 0 here, so we wont return anything
      $qill .= ': ' . ts('We could not geocode the destination address.');
      $query->_qill[$grouping][] = $qill;
      $query->_where[$grouping][] = ' (0) ';
      return NULL;
    }

    $query->_qill[$grouping][] = $qill;
    $query->_where[$grouping][] = self::where(
      $proximityAddress['geo_code_1'],
      $proximityAddress['geo_code_2'],
      $distance
    );

    return NULL;
  }

  /**
   * @param array $input
   *
   * @return void
   */
  public static function fixInputParams(&$input) {
    foreach ($input as $param) {
      if (($param['0'] ?? NULL) == 'prox_distance') {
        // add prox_ prefix to these
        $param_alter = ['street_address', 'city', 'postal_code', 'state_province', 'country'];

        foreach ($input as $key => $_param) {
          if (in_array($_param[0], $param_alter)) {
            $input[$key][0] = 'prox_' . $_param[0];

            // _id suffix where needed
            if ($_param[0] == 'country' || $_param[0] == 'state_province') {
              $input[$key][0] .= '_id';

              // flatten state_province array
              if (is_array($input[$key][2])) {
                $input[$key][2] = $input[$key][2][0];
              }
            }
          }
        }
      }
    }
  }

}
