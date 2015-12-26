<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */

require_once 'HTML/QuickForm/Rule/Email.php';

/**
 * Class CRM_Utils_Rule
 */
class CRM_Utils_Rule {

  /**
   * @param $str
   * @param int $maxLength
   *
   * @return bool
   */
  public static function title($str, $maxLength = 127) {

    // check length etc
    if (empty($str) || strlen($str) > $maxLength) {
      return FALSE;
    }

    // Make sure it include valid characters, alpha numeric and underscores
    if (!preg_match('/^\w[\w\s\'\&\,\$\#\-\.\"\?\!]+$/i', $str)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * @param $str
   *
   * @return bool
   */
  public static function longTitle($str) {
    return self::title($str, 255);
  }

  /**
   * @param $str
   *
   * @return bool
   */
  public static function variable($str) {
    // check length etc
    if (empty($str) || strlen($str) > 31) {
      return FALSE;
    }

    // make sure it includes valid characters, alpha numeric and underscores
    if (!preg_match('/^[\w]+$/i', $str)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * @param $str
   *
   * @return bool
   */
  public static function qfVariable($str) {
    // check length etc
    //if ( empty( $str ) || strlen( $str ) > 31 ) {
    if (strlen(trim($str)) == 0 || strlen($str) > 31) {
      return FALSE;
    }

    // make sure it includes valid characters, alpha numeric and underscores
    // added (. and ,) option (CRM-1336)
    if (!preg_match('/^[\w\s\.\,]+$/i', $str)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * @param $phone
   *
   * @return bool
   */
  public static function phone($phone) {
    // check length etc
    if (empty($phone) || strlen($phone) > 16) {
      return FALSE;
    }

    // make sure it includes valid characters, (, \s and numeric
    if (preg_match('/^[\d\(\)\-\.\s]+$/', $phone)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * @param $query
   *
   * @return bool
   */
  public static function query($query) {
    // check length etc
    if (empty($query) || strlen($query) < 3 || strlen($query) > 127) {
      return FALSE;
    }

    // make sure it includes valid characters, alpha numeric and underscores
    if (!preg_match('/^[\w\s\%\'\&\,\$\#]+$/i', $query)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * @param $url
   *
   * @return bool
   */
  public static function url($url) {
    if (preg_match('/^\//', $url)) {
      // allow relative URL's (CRM-15598)
      $url = 'http://' . $_SERVER['HTTP_HOST'] . $url;
    }
    return (bool) filter_var($url, FILTER_VALIDATE_URL);
  }

  /**
   * @param $url
   *
   * @return bool
   */
  public static function urlish($url) {
    if (empty($url)) {
      return TRUE;
    }
    $url = Civi::paths()->getUrl($url, 'absolute');
    return (bool) filter_var($url, FILTER_VALIDATE_URL);
  }

  /**
   * @param $string
   *
   * @return bool
   */
  public static function wikiURL($string) {
    $items = explode(' ', trim($string), 2);
    return self::url($items[0]);
  }

  /**
   * @param $domain
   *
   * @return bool
   */
  public static function domain($domain) {
    // not perfect, but better than the previous one; see CRM-1502
    if (!preg_match('/^[A-Za-z0-9]([A-Za-z0-9\.\-]*[A-Za-z0-9])?$/', $domain)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @param $value
   * @param null $default
   *
   * @return null
   */
  public static function date($value, $default = NULL) {
    if (is_string($value) &&
      preg_match('/^\d\d\d\d-?\d\d-?\d\d$/', $value)
    ) {
      return $value;
    }
    return $default;
  }

  /**
   * @param $value
   * @param null $default
   *
   * @return null|string
   */
  public static function dateTime($value, $default = NULL) {
    $result = $default;
    if (is_string($value) &&
      preg_match('/^\d\d\d\d-?\d\d-?\d\d(\s\d\d:\d\d(:\d\d)?|\d\d\d\d(\d\d)?)?$/', $value)
    ) {
      $result = $value;
    }

    return $result;
  }

  /**
   * Check the validity of the date (in qf format)
   * note that only a year is valid, or a mon-year is
   * also valid in addition to day-mon-year. The date
   * specified has to be beyond today. (i.e today or later)
   *
   * @param array $date
   * @param bool $monthRequired
   *   Check whether month is mandatory.
   *
   * @return bool
   *   true if valid date
   */
  public static function currentDate($date, $monthRequired = TRUE) {
    $config = CRM_Core_Config::singleton();

    $d = CRM_Utils_Array::value('d', $date);
    $m = CRM_Utils_Array::value('M', $date);
    $y = CRM_Utils_Array::value('Y', $date);

    if (!$d && !$m && !$y) {
      return TRUE;
    }

    // CRM-9017 CiviContribute/CiviMember form with expiration date format 'm Y'
    if (!$m && !empty($date['m'])) {
      $m = CRM_Utils_Array::value('m', $date);
    }

    $day = $mon = 1;
    $year = 0;
    if ($d) {
      $day = $d;
    }
    if ($m) {
      $mon = $m;
    }
    if ($y) {
      $year = $y;
    }

    // if we have day we need mon, and if we have mon we need year
    if (($d && !$m) ||
      ($d && !$y) ||
      ($m && !$y)
    ) {
      return FALSE;
    }

    $result = FALSE;
    if (!empty($day) || !empty($mon) || !empty($year)) {
      $result = checkdate($mon, $day, $year);
    }

    if (!$result) {
      return FALSE;
    }

    // ensure we have month if required
    if ($monthRequired && !$m) {
      return FALSE;
    }

    // now make sure this date is greater that today
    $currentDate = getdate();
    if ($year > $currentDate['year']) {
      return TRUE;
    }
    elseif ($year < $currentDate['year']) {
      return FALSE;
    }

    if ($m) {
      if ($mon > $currentDate['mon']) {
        return TRUE;
      }
      elseif ($mon < $currentDate['mon']) {
        return FALSE;
      }
    }

    if ($d) {
      if ($day > $currentDate['mday']) {
        return TRUE;
      }
      elseif ($day < $currentDate['mday']) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Check the validity of a date or datetime (timestamp)
   * value which is in YYYYMMDD or YYYYMMDDHHMMSS format
   *
   * Uses PHP checkdate() - params are ( int $month, int $day, int $year )
   *
   * @param string $date
   *
   * @return bool
   *   true if valid date
   */
  public static function mysqlDate($date) {
    // allow date to be null
    if ($date == NULL) {
      return TRUE;
    }

    if (checkdate(substr($date, 4, 2), substr($date, 6, 2), substr($date, 0, 4))) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * @param $value
   *
   * @return bool
   */
  public static function integer($value) {
    if (is_int($value)) {
      return TRUE;
    }

    // CRM-13460
    // ensure number passed is always a string numeral
    if (!is_numeric($value)) {
      return FALSE;
    }

    // note that is_int matches only integer type
    // and not strings which are only integers
    // hence we do this here
    if (preg_match('/^\d+$/', $value)) {
      return TRUE;
    }

    if ($value < 0) {
      $negValue = -1 * $value;
      if (is_int($negValue)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * @param $value
   *
   * @return bool
   */
  public static function positiveInteger($value) {
    if (is_int($value)) {
      return ($value < 0) ? FALSE : TRUE;
    }

    // CRM-13460
    // ensure number passed is always a string numeral
    if (!is_numeric($value)) {
      return FALSE;
    }

    if (preg_match('/^\d+$/', $value)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * @param $value
   *
   * @return bool
   */
  public static function numeric($value) {
    // lets use a php gatekeeper to ensure this is numeric
    if (!is_numeric($value)) {
      return FALSE;
    }

    return preg_match('/(^-?\d\d*\.\d*$)|(^-?\d\d*$)|(^-?\.\d\d*$)/', $value) ? TRUE : FALSE;
  }

  /**
   * @param $value
   * @param $noOfDigit
   *
   * @return bool
   */
  public static function numberOfDigit($value, $noOfDigit) {
    return preg_match('/^\d{' . $noOfDigit . '}$/', $value) ? TRUE : FALSE;
  }

  /**
   * @param $value
   *
   * @return mixed
   */
  public static function cleanMoney($value) {
    // first remove all white space
    $value = str_replace(array(' ', "\t", "\n"), '', $value);

    $config = CRM_Core_Config::singleton();

    //CRM-14868
    $currencySymbols = CRM_Core_PseudoConstant::get(
      'CRM_Contribute_DAO_Contribution',
      'currency', array(
        'keyColumn' => 'name',
        'labelColumn' => 'symbol',
      )
    );
    $value = str_replace($currencySymbols, '', $value);

    if ($config->monetaryThousandSeparator) {
      $mon_thousands_sep = $config->monetaryThousandSeparator;
    }
    else {
      $mon_thousands_sep = ',';
    }

    // ugly fix for CRM-6391: do not drop the thousand separator if
    // it looks like it’s separating decimal part (because a given
    // value undergoes a second cleanMoney() call, for example)
    // CRM-15835 - in case the amount/value contains 0 after decimal
    // eg 150.5 the following if condition will pass
    if ($mon_thousands_sep != '.' or (substr($value, -3, 1) != '.' && substr($value, -2, 1) != '.')) {
      $value = str_replace($mon_thousands_sep, '', $value);
    }

    if ($config->monetaryDecimalPoint) {
      $mon_decimal_point = $config->monetaryDecimalPoint;
    }
    else {
      $mon_decimal_point = '.';
    }
    $value = str_replace($mon_decimal_point, '.', $value);

    return $value;
  }

  /**
   * @param $value
   *
   * @return bool
   */
  public static function money($value) {
    $config = CRM_Core_Config::singleton();

    // only edge case when we have a decimal point in the input money
    // field and not defined in the decimal Point in config settings
    if ($config->monetaryDecimalPoint &&
      $config->monetaryDecimalPoint != '.' &&
      // CRM-7122 also check for Thousands Separator in config settings
      $config->monetaryThousandSeparator != '.' &&
      substr_count($value, '.')
    ) {
      return FALSE;
    }

    $value = self::cleanMoney($value);

    if (self::integer($value)) {
      return TRUE;
    }

    return preg_match('/(^-?\d+\.\d?\d?$)|(^-?\.\d\d?$)/', $value) ? TRUE : FALSE;
  }

  /**
   * @param $value
   * @param int $maxLength
   *
   * @return bool
   */
  public static function string($value, $maxLength = 0) {
    if (is_string($value) &&
      ($maxLength === 0 || strlen($value) <= $maxLength)
    ) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * @param $value
   *
   * @return bool
   */
  public static function boolean($value) {
    return preg_match(
      '/(^(1|0)$)|(^(Y(es)?|N(o)?)$)|(^(T(rue)?|F(alse)?)$)/i', $value
    ) ? TRUE : FALSE;
  }

  /**
   * @param $value
   *
   * @return bool
   */
  public static function email($value) {
    return (bool) filter_var($value, FILTER_VALIDATE_EMAIL);
  }

  /**
   * @param $list
   *
   * @return bool
   */
  public static function emailList($list) {
    $emails = explode(',', $list);
    foreach ($emails as $email) {
      $email = trim($email);
      if (!self::email($email)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * allow between 4-6 digits as postal code since india needs 6 and US needs 5 (or
   * if u disregard the first 0, 4 (thanx excel!)
   * FIXME: we need to figure out how to localize such rules
   * @param $value
   *
   * @return bool
   */
  public static function postalCode($value) {
    if (preg_match('/^\d{4,6}(-\d{4})?$/', $value)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * See how file rules are written in HTML/QuickForm/file.php
   * Checks to make sure the uploaded file is ascii
   *
   * @param string $elementValue
   *
   * @return bool
   *   True if file has been uploaded, false otherwise
   */
  public static function asciiFile($elementValue) {
    if ((isset($elementValue['error']) && $elementValue['error'] == 0) ||
      (!empty($elementValue['tmp_name']) && $elementValue['tmp_name'] != 'none')
    ) {
      return CRM_Utils_File::isAscii($elementValue['tmp_name']);
    }
    return FALSE;
  }

  /**
   * Checks to make sure the uploaded file is in UTF-8, recodes if it's not
   *
   * @param array $elementValue
   *
   * @return bool
   *   Whether file has been uploaded properly and is now in UTF-8.
   */
  public static function utf8File($elementValue) {
    $success = FALSE;

    if ((isset($elementValue['error']) && $elementValue['error'] == 0) ||
      (!empty($elementValue['tmp_name']) && $elementValue['tmp_name'] != 'none')
    ) {

      $success = CRM_Utils_File::isAscii($elementValue['tmp_name']);

      // if it's a file, but not UTF-8, let's try and recode it
      // and then make sure it's an UTF-8 file in the end
      if (!$success) {
        $success = CRM_Utils_File::toUtf8($elementValue['tmp_name']);
        if ($success) {
          $success = CRM_Utils_File::isAscii($elementValue['tmp_name']);
        }
      }
    }
    return $success;
  }

  /**
   * See how file rules are written in HTML/QuickForm/file.php
   * Checks to make sure the uploaded file is html
   *
   * @param array $elementValue
   *
   * @return bool
   *   True if file has been uploaded, false otherwise
   */
  public static function htmlFile($elementValue) {
    if ((isset($elementValue['error']) && $elementValue['error'] == 0) ||
      (!empty($elementValue['tmp_name']) && $elementValue['tmp_name'] != 'none')
    ) {
      return CRM_Utils_File::isHtmlFile($elementValue['tmp_name']);
    }
    return FALSE;
  }

  /**
   * Check if there is a record with the same name in the db.
   *
   * @param string $value
   *   The value of the field we are checking.
   * @param array $options
   *   The daoName and fieldName (optional ).
   *
   * @return bool
   *   true if object exists
   */
  public static function objectExists($value, $options) {
    $name = 'name';
    if (isset($options[2])) {
      $name = $options[2];
    }

    return CRM_Core_DAO::objectExists($value, CRM_Utils_Array::value(0, $options), CRM_Utils_Array::value(1, $options), CRM_Utils_Array::value(2, $options, $name));
  }

  /**
   * @param $value
   * @param $options
   *
   * @return bool
   */
  public static function optionExists($value, $options) {
    return CRM_Core_OptionValue::optionExists($value, $options[0], $options[1], $options[2], CRM_Utils_Array::value(3, $options, 'name'));
  }

  /**
   * @param $value
   * @param $type
   *
   * @return bool
   */
  public static function creditCardNumber($value, $type) {
    require_once 'Validate/Finance/CreditCard.php';
    return Validate_Finance_CreditCard::number($value, $type);
  }

  /**
   * @param $value
   * @param $type
   *
   * @return bool
   */
  public static function cvv($value, $type) {
    require_once 'Validate/Finance/CreditCard.php';

    return Validate_Finance_CreditCard::cvv($value, $type);
  }

  /**
   * @param $value
   *
   * @return bool
   */
  public static function currencyCode($value) {
    static $currencyCodes = NULL;
    if (!$currencyCodes) {
      $currencyCodes = CRM_Core_PseudoConstant::currencyCode();
    }
    if (in_array($value, $currencyCodes)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * @param $value
   *
   * @return bool
   */
  public static function xssString($value) {
    if (is_string($value)) {
      return preg_match('!<(vb)?script[^>]*>.*</(vb)?script.*>!ims',
        $value
      ) ? FALSE : TRUE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * @param $path
   *
   * @return bool
   */
  public static function fileExists($path) {
    return file_exists($path);
  }

  /**
   * Determine whether the value contains a valid reference to a directory.
   *
   * Paths stored in the setting system may be absolute -- or may be
   * relative to the default data directory.
   *
   * @param string $path
   * @return bool
   */
  public static function settingPath($path) {
    return is_dir(Civi::paths()->getPath($path));
  }

  /**
   * @param $value
   * @param null $actualElementValue
   *
   * @return bool
   */
  public static function validContact($value, $actualElementValue = NULL) {
    if ($actualElementValue) {
      $value = $actualElementValue;
    }

    return CRM_Utils_Rule::positiveInteger($value);
  }

  /**
   * Check the validity of the date (in qf format)
   * note that only a year is valid, or a mon-year is
   * also valid in addition to day-mon-year
   *
   * @param array $date
   *
   * @return bool
   *   true if valid date
   */
  public static function qfDate($date) {
    $config = CRM_Core_Config::singleton();

    $d = CRM_Utils_Array::value('d', $date);
    $m = CRM_Utils_Array::value('M', $date);
    $y = CRM_Utils_Array::value('Y', $date);
    if (isset($date['h']) ||
      isset($date['g'])
    ) {
      $m = CRM_Utils_Array::value('M', $date);
    }

    if (!$d && !$m && !$y) {
      return TRUE;
    }

    $day = $mon = 1;
    $year = 0;
    if ($d) {
      $day = $d;
    }
    if ($m) {
      $mon = $m;
    }
    if ($y) {
      $year = $y;
    }

    // if we have day we need mon, and if we have mon we need year
    if (($d && !$m) ||
      ($d && !$y) ||
      ($m && !$y)
    ) {
      return FALSE;
    }

    if (!empty($day) || !empty($mon) || !empty($year)) {
      return checkdate($mon, $day, $year);
    }
    return FALSE;
  }

  /**
   * @param $key
   *
   * @return bool
   */
  public static function qfKey($key) {
    return ($key) ? CRM_Core_Key::valid($key) : FALSE;
  }

}
