<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Contact_BAO_ProximityQuery {

  /**
   * Trigonometry for calculating geographical distances.
   * All function arguments and return values measure distances in metres
   * and angles in degrees.  The ellipsoid model is from the WGS-84 datum.
   * Ka-Ping Yee, 2003-08-11

   * earth_radius_semimajor = 6378137.0;
   * earth_flattening = 1/298.257223563;
   * earth_radius_semiminor = $earth_radius_semimajor * (1 - $earth_flattening);
   * earth_eccentricity_sq = 2*$earth_flattening - pow($earth_flattening, 2);

   * This library is an implementation of UCB CS graduate student, Ka-Ping Yee (http://www.zesty.ca).
   * This version has been taken from Drupal's location module: http://drupal.org/project/location
   **/

  static protected $_earthFlattening;
  static protected $_earthRadiusSemiMinor;
  static protected $_earthRadiusSemiMajor;
  static protected $_earthEccentricitySQ;

  static function initialize() {
    static $_initialized = FALSE;

    if (!$_initialized) {
      $_initialized = TRUE;

      self::$_earthFlattening = 1.0 / 298.257223563;
      self::$_earthRadiusSemiMajor = 6378137.0;
      self::$_earthRadiusSemiMinor = self::$_earthRadiusSemiMajor * (1.0 - self::$_earthFlattening);
      self::$_earthEccentricitySQ = 2 * self::$_earthFlattening - pow(self::$_earthFlattening, 2);
    }
  }

  /**
   * Latitudes in all of U. S.: from -7.2 (American Samoa) to 70.5 (Alaska).
   * Latitudes in continental U. S.: from 24.6 (Florida) to 49.0 (Washington).
   * Average latitude of all U. S. zipcodes: 37.9.
   */

  /*
    /**
     * Estimate the Earth's radius at a given latitude.
     * Default to an approximate average radius for the United States.
     */

  static function earthRadius($latitude) {
    $lat = deg2rad($latitude);

    $x = cos($lat) / self::$_earthRadiusSemiMajor;
    $y = sin($lat) / self::$_earthRadiusSemiMinor;
    return 1.0 / sqrt($x * $x + $y * $y);
  }

  /**
   * Convert longitude and latitude to earth-centered earth-fixed coordinates.
   * X axis is 0 long, 0 lat; Y axis is 90 deg E; Z axis is north pole.
   */
  static function earthXYZ($longitude, $latitude, $height = 0) {
    $long = deg2rad($longitude);
    $lat = deg2rad($latitude);

    $cosLong = cos($long);
    $cosLat  = cos($lat);
    $sinLong = sin($long);
    $sinLat  = sin($lat);

    $radius = self::$_earthRadiusSemiMajor / sqrt(1 - self::$_earthEccentricitySQ * $sinLat * $sinLat);

    $x = ($radius + $height) * $cosLat * $cosLong;
    $y = ($radius + $height) * $cosLat * $sinLong;
    $z = ($radius * (1 - self::$_earthEccentricitySQ) + $height) * $sinLat;

    return array($x, $y, $z);
  }

  /**
   * Convert a given angle to earth-surface distance.
   */
  static function earthArcLength($angle, $latitude) {
    return deg2rad($angle) * self::earthRadius($latitude);
  }

  /**
   * Estimate the earth-surface distance between two locations.
   */
  static function earthDistance($longitudeSrc, $latitudeSrc,
    $longitudeDst, $latitudeDst
  ) {

    $longSrc = deg2rad($longitudeSrc);
    $latSrc  = deg2rad($latitudeSrc);
    $longDst = deg2rad($longitudeDst);
    $latDst  = deg2rad($latitudeDst);

    $radius = self::earthRadius(($latitudeSrc + $latitudeDst) / 2);

    $cosAngle = cos($latSrc) * cos($latDst) * (cos($longSrc) * cos($longDst) + sin($longSrc) * sin($longDst)) + sin($latSrc) * sin($latDst);
    return acos($cosAngle) * $radius;
  }

  /**
   * Estimate the min and max longitudes within $distance of a given location.
   */
  static function earthLongitudeRange($longitude, $latitude, $distance) {
    $long   = deg2rad($longitude);
    $lat    = deg2rad($latitude);
    $radius = self::earthRadius($latitude);

    $angle   = $distance / $radius;
    $diff    = asin(sin($angle) / cos($lat));
    $minLong = $long - $diff;
    $maxLong = $long + $diff;

    if ($minLong < - pi()) {
      $minLong = $minLong + pi() * 2;
    }

    if ($maxLong > pi()) {
      $maxLong = $maxLong - pi() * 2;
    }

    return array(rad2deg($minLong),
      rad2deg($maxLong),
    );
  }

  /**
   * Estimate the min and max latitudes within $distance of a given location.
   */
  static function earthLatitudeRange($longitude, $latitude, $distance) {
    $long   = deg2rad($longitude);
    $lat    = deg2rad($latitude);
    $radius = self::earthRadius($latitude);

    $angle      = $distance / $radius;
    $minLat     = $lat - $angle;
    $maxLat     = $lat + $angle;
    $rightangle = pi() / 2.0;

    // wrapped around the south pole
    if ($minLat < - $rightangle) {
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

    return array(rad2deg($minLat),
      rad2deg($maxLat),
    );
  }

  /*
     * Returns the SQL fragment needed to add a column called 'distance'
     * to a query that includes the location table
     *
     * @param $longitude
     * @param $latitude
     */

  static function earthDistanceSQL($longitude, $latitude) {
    $long   = deg2rad($longitude);
    $lat    = deg2rad($latitude);
    $radius = self::earthRadius($latitude);

    $cosLong = cos($long);
    $cosLat  = cos($lat);
    $sinLong = sin($long);
    $sinLat  = sin($lat);

    return "
IFNULL( ACOS( $cosLat * COS( RADIANS( $latitude ) ) *
              ( $cosLong * COS( RADIANS( $longitude ) ) +
                $sinLong * SIN( RADIANS( $longitude ) ) ) +
              $sinLat  * SIN( RADIANS( $latitude  ) ) ), 0.00000 ) * $radius
";
  }

  static function where($latitude, $longitude, $distance, $tablePrefix = 'civicrm_address') {
    self::initialize();

    $params = array();
    $clause = array();

    list($minLongitude, $maxLongitude) = self::earthLongitudeRange($longitude,
      $latitude,
      $distance
    );
    list($minLatitude, $maxLatitude) = self::earthLatitudeRange($longitude,
      $latitude,
      $distance
    );

    $earthDistanceSQL = self::earthDistanceSQL($longitude, $latitude);

    $where = "
{$tablePrefix}.geo_code_1  >= $minLatitude  AND
{$tablePrefix}.geo_code_1  <= $maxLatitude  AND
{$tablePrefix}.geo_code_2 >= $minLongitude AND
{$tablePrefix}.geo_code_2 <= $maxLongitude AND
$earthDistanceSQL  <= $distance
";

    return $where;
  }

  static function process(&$query, &$values) {
    list($name, $op, $distance, $grouping, $wildcard) = $values;

    // also get values array for all address related info
    $proximityVars = array(
      'street_address' => 1,
      'city' => 1,
      'postal_code' => 1,
      'state_province_id' => 0,
      'country_id' => 0,
      'state_province' => 0,
      'country' => 0,
      'distance_unit' => 0,
    );

    $proximityAddress = array();
    $qill = array();
    foreach ($proximityVars as $var => $recordQill) {
      $proximityValues = $query->getWhereValues("prox_{$var}", $grouping);
      if (!empty($proximityValues) &&
        !empty($proximityValues[2])
      ) {
        $proximityAddress[$var] = $proximityValues[2];
        if ($recordQill) {
          $qill[] = $proximityValues[2];
        }
      }
    }

    if (empty($proximityAddress)) {
      return;
    }

    if (isset($proximityAddress['state_province_id'])) {
      $proximityAddress['state_province'] = CRM_Core_PseudoConstant::stateProvince($proximityAddress['state_province_id']);
      $qill[] = $proximityAddress['state_province'];
    }

    $config = CRM_Core_Config::singleton();
    if (!isset($proximityAddress['country_id'])) {
      // get it from state if state is present
      if (isset($proximityAddress['state_province_id'])) {
        $proximityAddress['country_id'] = CRM_Core_PseudoConstant::countryForState($proximityAddress['state_province_id']);
      }
      elseif (isset($config->defaultContactCountry)) {
        $proximityAddress['country_id'] = $config->defaultContactCountry;
      }
    }

    if (isset($proximityAddress['country_id'])) {
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
      array(
        1 => $qillUnits,
        2 => implode(', ', $qill)
      )
    );

    $fnName = isset($config->geocodeMethod) ? $config->geocodeMethod : NULL;
    if (empty($fnName)) {
      CRM_Core_Error::fatal(ts('Proximity searching requires you to set a valid geocoding provider'));
    }

    $query->_tables['civicrm_address'] = $query->_whereTables['civicrm_address'] = 1;

    require_once (str_replace('_', DIRECTORY_SEPARATOR, $fnName) . '.php');
    $fnName::format($proximityAddress);
    if (
      !is_numeric(CRM_Utils_Array::value('geo_code_1', $proximityAddress)) ||
      !is_numeric(CRM_Utils_Array::value('geo_code_2', $proximityAddress))
    ) {
      // we are setting the where clause to 0 here, so we wont return anything
      $qill .= ': ' . ts('We could not geocode the destination address.');
      $query->_qill[$grouping][] = $qill;
      $query->_where[$grouping][] = ' (0) ';
      return;
    }

    $query->_qill[$grouping][] = $qill;
    $query->_where[$grouping][] = self::where(
      $proximityAddress['geo_code_1'],
      $proximityAddress['geo_code_2'],
      $distance
    );

    return;
  }

  static function fixInputParams(&$input) {
    foreach ($input as $param) {
      if (CRM_Utils_Array::value('0', $param) == 'prox_distance') {
        // add prox_ prefix to these
        $param_alter = array('street_address', 'city', 'postal_code', 'state_province', 'country');

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
        return;
      }
    }
  }
}

