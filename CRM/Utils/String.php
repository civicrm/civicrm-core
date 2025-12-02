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

use function xKerman\Restricted\unserialize;
use xKerman\Restricted\UnserializeFailedException;

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
   * @param string $title title of the string
   * @param int $maxLength
   *
   * @return string
   *   An equivalent variable name.
   */
  public static function titleToVar($title, $maxLength = 31) {
    $variable = self::munge($title, '_', $maxLength);

    // FIXME: nothing below this line makes sense. The above call to self::munge will always
    // return a safe string of the correct length, so why are we now checking if it's a safe
    // string of the correct length?
    if (CRM_Utils_Rule::title($variable, $maxLength)) {
      return $variable;
    }

    // FIXME: When would this ever be reachable?
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

    // If there are no ascii characters present.
    if (!strlen(trim($name, $char))) {
      $name = self::createRandom($len, self::ALPHANUMERIC);
    }

    if ($len) {
      // lets keep variable names short
      return substr($name, 0, $len);
    }
    else {
      return $name;
    }
  }

  /**
   * Convert possibly underscore, space or dash separated words to CamelCase.
   *
   * @param string $str
   * @param bool $ucFirst
   *   Should the first letter be capitalized like `CamelCase` or lower like `camelCase`
   * @return string
   */
  public static function convertStringToCamel($str, $ucFirst = TRUE) {
    $fragments = preg_split('/[-_ ]/', $str, -1, PREG_SPLIT_NO_EMPTY);
    $camel = implode('', array_map('ucfirst', $fragments));
    return $ucFirst ? $camel : lcfirst($camel);
  }

  /**
   * Inverse of above function, converts camelCase to snake_case
   *
   * @param string $str
   * @return string
   */
  public static function convertStringToSnakeCase(string $str): string {
    // Use regular expression to replace uppercase with underscore + lowercase, avoiding duplicates
    $str = preg_replace('/(?<!^|_)(?=[A-Z])/', '_', $str);
    return strtolower($str);
  }

  /**
   * Converts `CamelCase` or `snake_case` to `dash-format`
   *
   * @param string $str
   * @return string
   */
  public static function convertStringToDash(string $str): string {
    return strtolower(implode('-', preg_split('/[-_ ]|(?=[A-Z])/', $str, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE)));
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
    $names = [];
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
      $order = ['ASCII'];
      if ($utf8) {
        $order[] = 'UTF-8';
      }
      $enc = mb_detect_encoding($str, $order, TRUE);
      return ($enc == 'ASCII' || $enc == 'UTF-8');
    }
  }

  /**
   * Encode string using URL-safe Base64.
   *
   * @param string $v
   *
   * @return string
   * @see https://tools.ietf.org/html/rfc4648#section-5
   */
  public static function base64UrlEncode($v) {
    return rtrim(str_replace(['+', '/'], ['-', '_'], base64_encode($v)), '=');
  }

  /**
   * Decode string using URL-safe Base64.
   *
   * @param string $v
   *
   * @return false|string
   * @see https://tools.ietf.org/html/rfc4648#section-5
   */
  public static function base64UrlDecode($v) {
    // PHP base64_decode() is already forgiving about padding ("=").
    return base64_decode(str_replace(['-', '_'], ['+', '/'], $v));
  }

  /**
   * @var string[]
   *   Array(string $base64 => string $base64mbz)
   */
  private static $mbzTable = ['Z' => 'Z0', '+' => 'Z1', '/' => 'Z2'];

  /**
   * Encode string using Base64 with multibyte "Z"-escaping (MBZ).
   *
   * Base64-MBZ strings are -strictly- alphanumeric, but they may be slightly longer
   * than standard Base64. For inputs with random-like data (such as crypto keys, signatures,
   * ciphertext, and compressed-files), it should be 4-5% longer.
   *
   * @param string $raw
   *
   * @return string
   *   Base64, but with some characters ('Z', '+', '/') replaced by multibyte expressions ("Z0", "Z1", "Z2").
   */
  public static function base64mbzEncode($raw) {
    return strtr(rtrim(base64_encode($raw), '='), self::$mbzTable);
  }

  public static function base64mbzDecode($str) {
    return base64_decode(strtr($str, array_flip(self::$mbzTable)));
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
      static $matches, $totalMatches, $match = [];
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
    return [];
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
    $enc = mb_detect_encoding($str, ['UTF-8'], TRUE);
    return ($enc !== FALSE);
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
    $component1 = parse_url(strtolower($url1));
    $component2 = parse_url(strtolower($url2));

    if ($component1['path'] == $component2['path'] &&
      self::extractURLVarValue($component1['query'] ?? '') == self::extractURLVarValue($component2['query'] ?? '')
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
   * @return string|false
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
    $token_html = preg_replace('!\{([a-z_.]+)\}!i', 'token:{$1}', $html);
    $token_text = \Soundasleep\Html2Text::convert($token_html, ['ignore_errors' => TRUE]);
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
    if (str_contains($name, ',')) {

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
    $result = [];
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
    $matches = [];
    preg_match('/-ALTERNATIVE ITEM 0-(.*?)-ALTERNATIVE ITEM 1-.*-ALTERNATIVE END-/s', ($full ?? ''), $matches);

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
      $_searchChars = [
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
      ];
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
      $config->set('Attr.AllowedFrameTargets', ['_blank', '_self', '_parent', '_top']);
      // Disable the cache entirely
      $config->set('Cache.DefinitionImpl', NULL);
      $config->set('HTML.DefinitionID', 'enduser-customize.html tutorial');
      $config->set('HTML.DefinitionRev', 1);
      $config->set('HTML.MaxImgLength', NULL);
      $config->set('CSS.MaxImgLength', NULL);
      $def = $config->maybeGetRawHTMLDefinition();
      $uri = $config->getDefinition('URI');
      $uri->addFilter(new CRM_Utils_HTMLPurifier_URIFilter(), $config);

      if (!empty($def)) {
        $def->addElement('figcaption', 'Block', 'Flow', 'Common');
        $def->addElement('figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common');
        // Allow `<summary>` and `<details>`
        $def->addElement('details', 'Block', 'Flow', 'Common', [
          'open' => new \HTMLPurifier_AttrDef_HTML_Bool('open'),
        ]);
        $def->addElement('summary', 'Inline', 'Inline', 'Common');
      }
      $_filter = new HTMLPurifier($config);
    }

    return $_filter->purify($string ?? '');
  }

  /**
   * Truncate $string; if $string exceeds $maxLen, place "..." at the end
   *
   * @param string $string
   * @param int $maxLen
   * @param string $ellipsis
   *  The literal form of the ellipsis.
   * @return string
   */
  public static function ellipsify($string, $maxLen, $ellipsis = '...') {
    if (mb_strlen($string, 'UTF-8') <= $maxLen) {
      return $string;
    }
    $ellipsisLen = mb_strlen($ellipsis, 'UTF-8');
    return mb_substr($string, 0, $maxLen - $ellipsisLen, 'UTF-8') . $ellipsis;
  }

  /**
   * Generate a random string.
   *
   * @param int $len
   * @param string $alphabet
   * @return string
   */
  public static function createRandom($len, $alphabet) {
    $alphabetSize = strlen($alphabet);
    $result = '';
    for ($i = 0; $i < $len; $i++) {
      $result .= $alphabet[random_int(1, $alphabetSize) - 1];
    }
    return $result;
  }

  /**
   * Examples:
   * "admin foo" => array(NULL,"admin foo")
   * "cms:admin foo" => array("cms", "admin foo")
   *
   * @param string $delim
   * @param string $string
   *   E.g. "view all contacts". Syntax: "[prefix:]name".
   * @param string|null $defaultPrefix
   * @param string $validPrefixPattern
   *   A regular expression used to determine if a prefix is valid.
   *   To wit: Prefixes MUST be strictly alphanumeric.
   *
   * @return array
   *   (0 => string|NULL $prefix, 1 => string $value)
   */
  public static function parsePrefix($delim, $string, $defaultPrefix = NULL, $validPrefixPattern = '/^[A-Za-z0-9]+$/') {
    $pos = strpos($string, $delim);
    if ($pos === FALSE) {
      return [$defaultPrefix, $string];
    }

    $lhs = substr($string, 0, $pos);
    $rhs = substr($string, 1 + $pos);
    return preg_match($validPrefixPattern, $lhs) ? [$lhs, $rhs] : [$defaultPrefix, $string];
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
   * When a user supplies a URL (e.g. to an image), we'd like to:
   *  - Remove the protocol and domain name if the URL points to the current
   *    site.
   *  - Keep the domain name for remote URLs.
   *  - Optionally, force remote URLs to use https instead of http (which is
   *    useful for images)
   *
   * @param string $url
   *   The URL to simplify. Examples:
   *     "https://example.org/sites/default/files/coffee-mug.jpg"
   *     "sites/default/files/coffee-mug.jpg"
   *     "http://i.stack.imgur.com/9jb2ial01b.png"
   * @param bool $forceHttps = FALSE
   *   If TRUE, ensure that remote URLs use https. If a URL with
   *   http is supplied, then we'll change it to https.
   *   This is useful for situations like showing a premium product on a
   *   contribution, because (as reported in CRM-14283) if the user gets a
   *   browser warning like "page contains insecure elements" on a contribution
   *   page, that's a very bad thing. Thus, even if changing http to https
   *   breaks the image, that's better than leaving http content in a
   *   contribution page.
   *
   * @return string
   *   The simplified URL. Examples:
   *     "/sites/default/files/coffee-mug.jpg"
   *     "https://i.stack.imgur.com/9jb2ial01b.png"
   */
  public static function simplifyURL($url, $forceHttps = FALSE) {
    $config = CRM_Core_Config::singleton();
    $siteURLParts = self::simpleParseUrl($config->userFrameworkBaseURL);
    $urlParts = self::simpleParseUrl($url);

    // If the image is locally hosted, then only give the path to the image
    $urlIsLocal
      = ($urlParts['host+port'] == '')
      | ($urlParts['host+port'] == $siteURLParts['host+port']);
    if ($urlIsLocal) {
      // and make sure it begins with one forward slash
      return preg_replace('_^/*(?=.)_', '/', $urlParts['path+query']);
    }

    // If the URL is external, then keep the full URL as supplied
    else {
      return $forceHttps ? preg_replace('_^http://_', 'https://', $url) : $url;
    }
  }

  /**
   * A simplified version of PHP's parse_url() function.
   *
   * @param string $url
   *   e.g. "https://example.com:8000/foo/bar/?id=1#fragment"
   *
   * @return array
   *   Will always contain keys 'host+port' and 'path+query', even if they're
   *   empty strings. Example:
   *   [
   *     'host+port' => "example.com:8000",
   *     'path+query' => "/foo/bar/?id=1",
   *   ]
   */
  public static function simpleParseUrl($url) {
    $parts = parse_url($url);
    $host = $parts['host'] ?? '';
    $port = isset($parts['port']) ? ':' . $parts['port'] : '';
    $path = $parts['path'] ?? '';
    $query = isset($parts['query']) ? '?' . $parts['query'] : '';
    return [
      'host+port' => "$host$port",
      'path+query' => "$path$query",
    ];
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

  /**
   * @deprecated
   *
   * @param string $string
   *   The long string.
   * @param string $fragment
   *   The fragment to look for.
   * @return bool
   */
  public static function startsWith($string, $fragment) {
    CRM_Core_Error::deprecatedFunctionWarning('str_starts_with');
    return str_starts_with((string) $string, (string) $fragment);
  }

  /**
   * @deprecated
   *
   * @param string $string
   *   The long string.
   * @param string $fragment
   *   The fragment to look for.
   * @return bool
   */
  public static function endsWith($string, $fragment) {
    CRM_Core_Error::deprecatedFunctionWarning('str_ends_with');
    return str_ends_with((string) $string, (string) $fragment);
  }

  /**
   * @param string|array $patterns
   * @param array $allStrings
   * @param bool $allowNew
   *   Whether to return new, unrecognized names.
   * @return array
   */
  public static function filterByWildcards($patterns, $allStrings, $allowNew = FALSE) {
    $patterns = (array) $patterns;
    $result = [];
    foreach ($patterns as $pattern) {
      if (!str_ends_with($pattern, '*')) {
        if ($allowNew || in_array($pattern, $allStrings)) {
          $result[] = $pattern;
        }
      }
      else {
        $prefix = rtrim($pattern, '*');
        foreach ($allStrings as $key) {
          if (str_starts_with($key, $prefix)) {
            $result[] = $key;
          }
        }
      }
    }
    return array_values(array_unique($result));
  }

  /**
   * Safely unserialize a string of scalar or array values (but not objects!)
   *
   * Use `xkerman/restricted-unserialize` to unserialize strings using PHP's
   * serialization format. `restricted-unserialize` works like PHP's built-in
   * `unserialize` function except that it does not deserialize object instances,
   * making it immune to PHP Object Injection {@see https://www.owasp.org/index.php/PHP_Object_Injection}
   * vulnerabilities.
   *
   * Note: When dealing with user inputs, it is generally recommended to use
   * safe, standard data interchange formats such as JSON rather than PHP's
   * serialization format when dealing with user input.
   *
   * @param string|null $string
   *
   * @return mixed
   */
  public static function unserialize($string) {
    if (!is_string($string)) {
      return FALSE;
    }
    try {
      return unserialize($string);
    }
    catch (UnserializeFailedException $e) {
      return FALSE;
    }
  }

  /**
   * Returns the plural form of an English word.
   *
   * @param string $str
   * @return string
   */
  public static function pluralize($str) {
    $lastLetter = substr($str, -1);
    $lastTwo = substr($str, -2);
    if ($lastLetter == 's' || $lastLetter == 'x' || $lastTwo == 'ch') {
      return $str . 'es';
    }
    if ($lastLetter == 'y' && !in_array($lastTwo, ['ay', 'ey', 'iy', 'oy', 'uy'])) {
      return substr($str, 0, -1) . 'ies';
    }
    return $str . 's';
  }

  /**
   * Generic check as to whether any tokens are in the given string.
   *
   * It might be a smarty token OR a CiviCRM token. In both cases the
   * absence of a '{' indicates no token is present.
   *
   * @param string $string
   *
   * @return bool
   */
  public static function stringContainsTokens(string $string) {
    return str_contains($string, '{');
  }

  /**
   * Parse a string through smarty without creating a smarty template file per string.
   *
   * This function is for swapping out any smarty tokens that appear in a string
   * and are not re-used much if at all. For example parsing a contact's greeting
   * does not need to be cached are there are some minor security / data privacy benefits
   * to not caching them per file. We also save disk space, reduce I/O and disk clearing time.
   *
   * Doing this is cleaning in Smarty3 which we are alas not using
   * https://www.smarty.net/docs/en/resources.string.tpl
   *
   * However, it highlights that smarty-eval is not evil-eval and still have the security applied.
   *
   * In order to replicate that in Smarty2 I'm using {eval} per
   * https://www.smarty.net/docsv2/en/language.function.eval.tpl#id2820446
   * From the above:
   * - Evaluated variables are treated the same as templates. They follow the same escapement and security features just as if they were templates.
   * - Evaluated variables are compiled on every invocation, the compiled versions are not saved! However if you have caching enabled, the output
   *   will be cached with the rest of the template.
   *
   * Our set up does not have caching enabled and my testing suggests this still works fine with it
   * enabled so turning it off before running this is out of caution based on the above.
   *
   * When this function is run only one template file is created (for the eval) tag no matter how
   * many times it is run. This compares to it otherwise creating one file for every parsed string.
   *
   * @param string $templateString
   * @param array $templateVars
   *
   * @return string
   *
   * @noinspection PhpDocRedundantThrowsInspection
   *
   * @throws \CRM_Core_Exception
   */
  public static function parseOneOffStringThroughSmarty($templateString, $templateVars = []) {
    if (!CRM_Utils_String::stringContainsTokens($templateString)) {
      // Skip expensive smarty processing.
      return $templateString;
    }
    $smarty = CRM_Core_Smarty::singleton();
    $cachingValue = $smarty->caching;
    set_error_handler([$smarty, 'handleSmartyError'], E_USER_ERROR);
    $smarty->caching = 0;
    $useSecurityPolicy = ($smarty->getVersion() > 2) ? !$smarty->security_policy : !$smarty->security;
    // For Smarty v2, policy is applied at lower level.
    if ($useSecurityPolicy) {
      // $smarty->enableSecurity('CRM_Core_Smarty_Security');
      Civi::service('civi.smarty.userContent')->enable();
    }
    $smarty->assign('smartySingleUseString', $templateString);
    try {
      // Do not escape the smartySingleUseString as that is our smarty template
      // and is likely to contain html.
      // The file name generated by
      // 'string:{eval var=$smartySingleUseString|smarty:nodefaults}'
      // is invalid in Windows, causing failure.
      // Adding this is preparatory to smarty 3. The original PR failed some
      // tests so we check for the function.
      if (!function_exists('smarty_function_eval') && (!defined('SMARTY_DIR') || !file_exists(SMARTY_DIR . '/plugins/function.eval.php'))) {
        if (!empty($templateVars)) {
          $templateString = (string) $smarty->fetchWith('eval:' . $templateString, $templateVars);
        }
        else {
          $templateString = (string) $smarty->fetch('eval:' . $templateString);
        }
      }
      else {
        if (!empty($templateVars)) {
          $templateString = (string) $smarty->fetchWith('string:{eval var=$smartySingleUseString|smarty:nodefaults}', $templateVars);
        }
        else {
          $templateString = (string) $smarty->fetch('string:{eval var=$smartySingleUseString|smarty:nodefaults}');
        }
      }
    }
    catch (Exception $e) {
      \Civi::log('smarty')->info('parsing smarty template {template}', [
        'template' => $templateString,
      ]);
      throw new \CRM_Core_Exception('Message was not parsed due to invalid smarty syntax : ' . $e->getMessage() . ((CIVICRM_UF === 'UnitTest' || CRM_Utils_Constant::value('SMARTY_DEBUG_STRINGS')) ? $templateString : ''));
    }
    finally {
      $smarty->caching = $cachingValue;
      $smarty->assign('smartySingleUseString');
      restore_error_handler();
      if ($useSecurityPolicy) {
        // $smarty->disableSecurity();
        Civi::service('civi.smarty.userContent')->disable();
      }
    }
    return $templateString;
  }

  /**
   * Parse a string for SearchKit-style [square_bracket] tokens.
   * @internal
   * @param string $raw
   * @return array
   */
  public static function getSquareTokens(string $raw): array {
    $matches = $tokens = [];
    if (str_contains($raw, '[')) {
      preg_match_all('/\\[([^]]+)\\]/', $raw, $matches);
      foreach (array_unique($matches[1]) as $match) {
        [$field, $suffix] = array_pad(explode(':', $match), 2, NULL);
        $tokens[$match] = [
          'token' => "[$match]",
          'field' => $field,
          'suffix' => $suffix,
        ];
      }
    }
    return $tokens;
  }

  public static function isQuotedString($value): bool {
    return is_string($value) && strlen($value) > 1 && $value[0] === $value[-1] && in_array($value[0], ['"', "'"]);
  }

  public static function unquoteString(string $string): string {
    // Strip the outer quotes if the string starts and ends with the same quote type
    if (self::isQuotedString($string)) {
      $string = substr($string, 1, -1);

      // Replace escaped quotes with unescaped quotes, avoiding escaped backslashes
      $string = preg_replace('/(?<!\\\\)\\\\\\"/', '"', $string);
      $string = preg_replace('/(?<!\\\\)\\\\\\\'/', "'", $string);
    }

    return $string;
  }

}
