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
 * This class contains string functions.
 */
class CRM_Utils_String {
  const COMMA = ",", SEMICOLON = ";", SPACE = " ", TAB = "\t", LINEFEED = "\n", CARRIAGELINE = "\r\n", LINECARRIAGE = "\n\r", CARRIAGERETURN = "\r";

  /**
   * List of all letters and numbers
   */
  const ALPHANUMERIC = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';

  /**
   * Convert a display name into a potential variable name.
   *
   * @param $title title of the string
   * @param int $maxLength
   *
   * @return string
   *   An equivalent variable name.
   */
  public static function titleToVar($title, $maxLength = 31) {
    $variable = self::munge($title, '_', $maxLength);

    if (CRM_Utils_Rule::title($variable, $maxLength)) {
      return $variable;
    }

    // if longer than the maxLength lets just return a substr of the
    // md5 to prevent errors downstream
    return substr(md5($title), 0, $maxLength);
  }

  /**
   * Replace all non alpha numeric characters and spaces with the replacement character.
   *
   * @param string $name
   *   The name to be worked on.
   * @param string $char
   *   The character to use for non-valid chars.
   * @param int $len
   *   Length of valid variables.
   *
   * @return string
   *   returns the manipulated string
   */
  public static function munge($name, $char = '_', $len = 63) {
    // Replace all white space and non-alpha numeric with $char
    // we only use the ascii character set since mysql does not create table names / field names otherwise
    // CRM-11744
    $name = preg_replace('/[^a-zA-Z0-9]+/', $char, trim($name));

    if ($len) {
      // lets keep variable names short
      return substr($name, 0, $len);
    }
    else {
      return $name;
    }
  }

  /**
   * Convert possibly underscore separated words to camel case with special handling for 'UF'
   * e.g membership_payment returns MembershipPayment
   *
   * @param string $string
   *
   * @return string
   */
  public static function convertStringToCamel($string) {
    $map = array(
      'acl' => 'Acl',
      'ACL' => 'Acl',
      'im' => 'Im',
      'IM' => 'Im',
    );
    if (isset($map[$string])) {
      return $map[$string];
    }

    $fragments = explode('_', $string);
    foreach ($fragments as & $fragment) {
      $fragment = ucfirst($fragment);
    }
    // Special case: UFGroup, UFJoin, UFMatch, UFField
    if ($fragments[0] === 'Uf') {
      $fragments[0] = 'UF';
    }
    return implode('', $fragments);
  }

  /**
   * Takes a variable name and munges it randomly into another variable name.
   *
   * @param string $name
   *   Initial Variable Name.
   * @param int $len
   *   Length of valid variables.
   *
   * @return string
   *   Randomized Variable Name
   */
  public static function rename($name, $len = 4) {
    $rand = substr(uniqid(), 0, $len);
    return substr_replace($name, $rand, -$len, $len);
  }

  /**
   * Takes a string and returns the last tuple of the string.
   *
   * Useful while converting file names to class names etc
   *
   * @param string $string
   *   The input string.
   * @param string $char
   *   Character used to demarcate the components
   *
   * @return string
   *   The last component
   */
  public static function getClassName($string, $char = '_') {
    $names = array();
    if (!is_array($string)) {
      $names = explode($char, $string);
    }
    if (!empty($names)) {
      return array_pop($names);
    }
  }

  /**
   * Appends a name to a string and separated by delimiter.
   *
   * Does the right thing for an empty string
   *
   * @param string $str
   *   The string to be appended to.
   * @param string $delim
   *   The delimiter to use.
   * @param mixed $name
   *   The string (or array of strings) to append.
   */
  public static function append(&$str, $delim, $name) {
    if (empty($name)) {
      return;
    }

    if (is_array($name)) {
      foreach ($name as $n) {
        if (empty($n)) {
          continue;
        }
        if (empty($str)) {
          $str = $n;
        }
        else {
          $str .= $delim . $n;
        }
      }
    }
    else {
      if (empty($str)) {
        $str = $name;
      }
      else {
        $str .= $delim . $name;
      }
    }
  }

  /**
   * Determine if the string is composed only of ascii characters.
   *
   * @param string $str
   *   Input string.
   * @param bool $utf8
   *   Attempt utf8 match on failure (default yes).
   *
   * @return bool
   *   true if string is ascii
   */
  public static function isAscii($str, $utf8 = TRUE) {
    if (!function_exists('mb_detect_encoding')) {
      // eliminate all white space from the string
      $str = preg_replace('/\s+/', '', $str);
      // FIXME:  This is a pretty brutal hack to make utf8 and 8859-1 work.

      // match low- or high-ascii characters
      if (preg_match('/[\x00-\x20]|[\x7F-\xFF]/', $str)) {
        // || // low ascii characters
        // high ascii characters
        //  preg_match( '/[\x7F-\xFF]/', $str ) ) {
        if ($utf8) {
          // if we did match, try for utf-8, or iso8859-1

          return self::isUtf8($str);
        }
        else {
          return FALSE;
        }
      }
      return TRUE;
    }
    else {
      $order = array('ASCII');
      if ($utf8) {
        $order[] = 'UTF-8';
      }
      $enc = mb_detect_encoding($str, $order, TRUE);
      return ($enc == 'ASCII' || $enc == 'UTF-8');
    }
  }

  /**
   * Determine the string replacements for redaction.
   * on the basis of the regular expressions
   *
   * @param string $str
   *   Input string.
   * @param array $regexRules
   *   Regular expression to be matched w/ replacements.
   *
   * @return array
   *   array of strings w/ corresponding redacted outputs
   */
  public static function regex($str, $regexRules) {
    // redact the regular expressions
    if (!empty($regexRules) && isset($str)) {
      static $matches, $totalMatches, $match = array();
      foreach ($regexRules as $pattern => $replacement) {
        preg_match_all($pattern, $str, $matches);
        if (!empty($matches[0])) {
          if (empty($totalMatches)) {
            $totalMatches = $matches[0];
          }
          else {
            $totalMatches = array_merge($totalMatches, $matches[0]);
          }
          $match = array_flip($totalMatches);
        }
      }
    }

    if (!empty($match)) {
      foreach ($match as $matchKey => & $dontCare) {
        foreach ($regexRules as $pattern => $replacement) {
          if (preg_match($pattern, $matchKey)) {
            $dontCare = $replacement . substr(md5($matchKey), 0, 5);
            break;
          }
        }
      }
      return $match;
    }
    return CRM_Core_DAO::$_nullArray;
  }

  /**
   * @param $str
   * @param $stringRules
   *
   * @return mixed
   */
  public static function redaction($str, $stringRules) {
    // redact the strings
    if (!empty($stringRules)) {
      foreach ($stringRules as $match => $replace) {
        $str = str_ireplace($match, $replace, $str);
      }
    }

    // return the redacted output
    return $str;
  }

  /**
   * Determine if a string is composed only of utf8 characters
   *
   * @param string $str
   *   Input string.
   *
   * @return bool
   */
  public static function isUtf8($str) {
    if (!function_exists(mb_detect_encoding)) {
      // eliminate all white space from the string
      $str = preg_replace('/\s+/', '', $str);

      // pattern stolen from the php.net function documentation for
      // utf8decode();
      // comment by JF Sebastian, 30-Mar-2005
      return preg_match('/^([\x00-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xec][\x80-\xbf]{2}|\xed[\x80-\x9f][\x80-\xbf]|[\xee-\xef][\x80-\xbf]{2}|f0[\x90-\xbf][\x80-\xbf]{2}|[\xf1-\xf3][\x80-\xbf]{3}|\xf4[\x80-\x8f][\x80-\xbf]{2})*$/', $str);
      // ||
      // iconv('ISO-8859-1', 'UTF-8', $str);
    }
    else {
      $enc = mb_detect_encoding($str, array('UTF-8'), TRUE);
      return ($enc !== FALSE);
    }
  }

  /**
   * Determine if two hrefs are equivalent (fuzzy match)
   *
   * @param string $url1
   *   The first url to be matched.
   * @param string $url2
   *   The second url to be matched against.
   *
   * @return bool
   *   true if the urls match, else false
   */
  public static function match($url1, $url2) {
    $url1 = strtolower($url1);
    $url2 = strtolower($url2);

    $url1Str = parse_url($url1);
    $url2Str = parse_url($url2);

    if ($url1Str['path'] == $url2Str['path'] &&
      self::extractURLVarValue(CRM_Utils_Array::value('query', $url1Str)) == self::extractURLVarValue(CRM_Utils_Array::value('query', $url2Str))
    ) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Extract the civicrm path from the url.
   *
   * @param string $query
   *   A url string.
   *
   * @return string|null
   *   civicrm url (eg: civicrm/contact/search)
   */
  public static function extractURLVarValue($query) {
    $config = CRM_Core_Config::singleton();
    $urlVar = $config->userFrameworkURLVar;

    $params = explode('&', $query);
    foreach ($params as $p) {
      if (strpos($p, '=')) {
        list($k, $v) = explode('=', $p);
        if ($k == $urlVar) {
          return $v;
        }
      }
    }
    return NULL;
  }

  /**
   * Translate a true/false/yes/no string to a 0 or 1 value
   *
   * @param string $str
   *   The string to be translated.
   *
   * @return bool
   */
  public static function strtobool($str) {
    if (!is_scalar($str)) {
      return FALSE;
    }

    if (preg_match('/^(y(es)?|t(rue)?|1)$/i', $str)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Returns string '1' for a true/yes/1 string, and '0' for no/false/0 else returns false
   *
   * @param string $str
   *   The string to be translated.
   *
   * @return bool
   */
  public static function strtoboolstr($str) {
    if (!is_scalar($str)) {
      return FALSE;
    }

    if (preg_match('/^(y(es)?|t(rue)?|1)$/i', $str)) {
      return '1';
    }
    elseif (preg_match('/^(n(o)?|f(alse)?|0)$/i', $str)) {
      return '0';
    }
    else {
      return FALSE;
    }
  }

  /**
   * Convert a HTML string into a text one using html2text
   *
   * @param string $html
   *   The string to be converted.
   *
   * @return string
   *   the converted string
   */
  public static function htmlToText($html) {
    require_once 'packages/html2text/rcube_html2text.php';
    $token_html = preg_replace('!\{([a-z_.]+)\}!i', 'token:{$1}', $html);
    $converter = new rcube_html2text($token_html);
    $token_text = $converter->get_text();
    $text = preg_replace('!token\:\{([a-z_.]+)\}!i', '{$1}', $token_text);
    return $text;
  }

  /**
   * @param $string
   * @param array $params
   */
  public static function extractName($string, &$params) {
    $name = trim($string);
    if (empty($name)) {
      return;
    }

    // strip out quotes
    $name = str_replace('"', '', $name);
    $name = str_replace('\'', '', $name);

    // check for comma in name
    if (strpos($name, ',') !== FALSE) {

      // name has a comma - assume lname, fname [mname]
      $names = explode(',', $name);
      if (count($names) > 1) {
        $params['last_name'] = trim($names[0]);

        // check for space delim
        $fnames = explode(' ', trim($names[1]));
        if (count($fnames) > 1) {
          $params['first_name'] = trim($fnames[0]);
          $params['middle_name'] = trim($fnames[1]);
        }
        else {
          $params['first_name'] = trim($fnames[0]);
        }
      }
      else {
        $params['first_name'] = trim($names[0]);
      }
    }
    else {
      // name has no comma - assume fname [mname] fname
      $names = explode(' ', $name);
      if (count($names) == 1) {
        $params['first_name'] = $names[0];
      }
      elseif (count($names) == 2) {
        $params['first_name'] = $names[0];
        $params['last_name'] = $names[1];
      }
      else {
        $params['first_name'] = $names[0];
        $params['middle_name'] = $names[1];
        $params['last_name'] = $names[2];
      }
    }
  }

  /**
   * @param $string
   *
   * @return array
   */
  public static function &makeArray($string) {
    $string = trim($string);

    $values = explode("\n", $string);
    $result = array();
    foreach ($values as $value) {
      list($n, $v) = CRM_Utils_System::explode('=', $value, 2);
      if (!empty($v)) {
        $result[trim($n)] = trim($v);
      }
    }
    return $result;
  }

  /**
   * Given an ezComponents-parsed representation of
   * a text with alternatives return only the first one
   *
   * @param string $full
   *   All alternatives as a long string (or some other text).
   *
   * @return string
   *   only the first alternative found (or the text without alternatives)
   */
  public static function stripAlternatives($full) {
    $matches = array();
    preg_match('/-ALTERNATIVE ITEM 0-(.*?)-ALTERNATIVE ITEM 1-.*-ALTERNATIVE END-/s', $full, $matches);

    if (isset($matches[1]) &&
      trim(strip_tags($matches[1])) != ''
    ) {
      return $matches[1];
    }
    else {
      return $full;
    }
  }

  /**
   * Strip leading, trailing, double spaces from string
   * used for postal/greeting/addressee
   *
   * @param string $string
   *   Input string to be cleaned.
   *
   * @return string
   *   the cleaned string
   */
  public static function stripSpaces($string) {
    return (empty($string)) ? $string : preg_replace("/\s{2,}/", " ", trim($string));
  }

  /**
   * clean the URL 'path' variable that we use
   * to construct CiviCRM urls by removing characters from the path variable
   *
   * @param string $string
   *   The input string to be sanitized.
   * @param array $search
   *   The characters to be sanitized.
   * @param string $replace
   *   The character to replace it with.
   *
   * @return string
   *   the sanitized string
   */
  public static function stripPathChars(
    $string,
    $search = NULL,
    $replace = NULL
  ) {
    static $_searchChars = NULL;
    static $_replaceChar = NULL;

    if (empty($string)) {
      return $string;
    }

    if ($_searchChars == NULL) {
      $_searchChars = array(
        '&',
        ';',
        ',',
        '=',
        '$',
        '"',
        "'",
        '\\',
        '<',
        '>',
        '(',
        ')',
        ' ',
        "\r",
        "\r\n",
        "\n",
        "\t",
      );
      $_replaceChar = '_';
    }

    if ($search == NULL) {
      $search = $_searchChars;
    }

    if ($replace == NULL) {
      $replace = $_replaceChar;
    }

    return str_replace($search, $replace, $string);
  }


  /**
   * Use HTMLPurifier to clean up a text string and remove any potential
   * xss attacks. This is primarily used in public facing pages which
   * accept html as the input string
   *
   * @param string $string
   *   The input string.
   *
   * @return string
   *   the cleaned up string
   */
  public static function purifyHTML($string) {
    static $_filter = NULL;
    if (!$_filter) {
      $config = HTMLPurifier_Config::createDefault();
      $config->set('Core.Encoding', 'UTF-8');

      // Disable the cache entirely
      $config->set('Cache.DefinitionImpl', NULL);

      $_filter = new HTMLPurifier($config);
    }

    return $_filter->purify($string);
  }

  /**
   * Truncate $string; if $string exceeds $maxLen, place "..." at the end
   *
   * @param string $string
   * @param int $maxLen
   *
   * @return string
   */
  public static function ellipsify($string, $maxLen) {
    $len = strlen($string);
    if ($len <= $maxLen) {
      return $string;
    }
    else {
      return substr($string, 0, $maxLen - 3) . '...';
    }
  }

  /**
   * Generate a random string.
   *
   * @param $len
   * @param $alphabet
   * @return string
   */
  public static function createRandom($len, $alphabet) {
    $alphabetSize = strlen($alphabet);
    $result = '';
    for ($i = 0; $i < $len; $i++) {
      $result .= $alphabet{rand(1, $alphabetSize) - 1};
    }
    return $result;
  }

  /**
   * Examples:
   * "admin foo" => array(NULL,"admin foo")
   * "cms:admin foo" => array("cms", "admin foo")
   *
   * @param $delim
   * @param string $string
   *   E.g. "view all contacts". Syntax: "[prefix:]name".
   * @param null $defaultPrefix
   *
   * @return array
   *   (0 => string|NULL $prefix, 1 => string $value)
   */
  public static function parsePrefix($delim, $string, $defaultPrefix = NULL) {
    $pos = strpos($string, $delim);
    if ($pos === FALSE) {
      return array($defaultPrefix, $string);
    }
    else {
      return array(substr($string, 0, $pos), substr($string, 1 + $pos));
    }
  }

  /**
   * This function will mask part of the the user portion of an Email address (everything before the @)
   *
   * @param string $email
   *   The email address to be masked.
   * @param string $maskChar
   *   The character used for masking.
   * @param int $percent
   *   The percentage of the user portion to be masked.
   *
   * @return string
   *   returns the masked Email address
   */
  public static function maskEmail($email, $maskChar = '*', $percent = 50) {
    list($user, $domain) = preg_split("/@/", $email);
    $len = strlen($user);
    $maskCount = floor($len * $percent / 100);
    $offset = floor(($len - $maskCount) / 2);

    $masked = substr($user, 0, $offset)
      . str_repeat($maskChar, $maskCount)
      . substr($user, $maskCount + $offset);

    return ($masked . '@' . $domain);
  }

  /**
   * This function compares two strings.
   *
   * @param string $strOne
   *   String one.
   * @param string $strTwo
   *   String two.
   * @param bool $case
   *   Boolean indicating whether you want the comparison to be case sensitive or not.
   *
   * @return bool
   *   TRUE (string are identical); FALSE (strings are not identical)
   */
  public static function compareStr($strOne, $strTwo, $case) {
    if ($case == TRUE) {
      // Convert to lowercase and trim white spaces
      if (strtolower(trim($strOne)) == strtolower(trim($strTwo))) {
        // yes - they are identical
        return TRUE;
      }
      else {
        // not identical
        return FALSE;
      }
    }
    if ($case == FALSE) {
      // Trim white spaces
      if (trim($strOne) == trim($strTwo)) {
        // yes - they are identical
        return TRUE;
      }
      else {
        // not identical
        return FALSE;
      }
    }
  }

  /**
   * Many parts of the codebase have a convention of internally passing around
   * HTML-encoded URLs. This effectively means that "&" is replaced by "&amp;"
   * (because most other odd characters are %-escaped in URLs; and %-escaped
   * strings don't need any extra escaping in HTML).
   *
   * @param string $htmlUrl
   *   URL with HTML entities.
   * @return string
   *   URL without HTML entities
   */
  public static function unstupifyUrl($htmlUrl) {
    return str_replace('&amp;', '&', $htmlUrl);
  }

  /**
   * Formats a string of attributes for insertion in an html tag.
   *
   * @param array $attributes
   *
   * @return string
   */
  public static function htmlAttributes($attributes) {
    $output = '';
    foreach ($attributes as $name => $vals) {
      $output .= " $name=\"" . htmlspecialchars(implode(' ', (array) $vals)) . '"';
    }
    return ltrim($output);
  }

}
