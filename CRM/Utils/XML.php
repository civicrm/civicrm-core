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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_XML {

  /**
   * Read a well-formed XML file
   *
   * @param string $file
   *
   * @return array
   *   (0 => SimpleXMLElement|FALSE, 1 => errorMessage|FALSE)
   */
  public static function parseFile($file) {
    // SimpleXMLElement
    $xml = FALSE;
    // string
    $error = FALSE;

    if (!file_exists($file)) {
      $error = 'File ' . $file . ' does not exist.';
    }
    else {
      $oldLibXMLErrors = libxml_use_internal_errors();
      libxml_use_internal_errors(TRUE);

      // Note that under obscure circumstances calling simplexml_load_file
      // hit https://bugs.php.net/bug.php?id=62577
      $string = file_get_contents($file);
      $xml = simplexml_load_string($string, 'SimpleXMLElement', LIBXML_NOCDATA);
      if ($xml === FALSE) {
        $error = self::formatErrors(libxml_get_errors());
      }

      libxml_use_internal_errors($oldLibXMLErrors);
    }

    return [$xml, $error];
  }

  /**
   * Read a well-formed XML file
   *
   * @param $string
   *
   * @return array
   *   (0 => SimpleXMLElement|FALSE, 1 => errorMessage|FALSE)
   */
  public static function parseString($string) {
    // SimpleXMLElement
    $xml = FALSE;
    // string
    $error = FALSE;

    $oldLibXMLErrors = libxml_use_internal_errors();
    libxml_use_internal_errors(TRUE);

    $xml = simplexml_load_string($string,
      'SimpleXMLElement', LIBXML_NOCDATA
    );
    if ($xml === FALSE) {
      $error = self::formatErrors(libxml_get_errors());
    }

    libxml_use_internal_errors($oldLibXMLErrors);

    return [$xml, $error];
  }

  /**
   * @param $errors
   *
   * @return string
   */
  protected static function formatErrors($errors) {
    $messages = [];

    foreach ($errors as $error) {
      if ($error->level != LIBXML_ERR_ERROR && $error->level != LIBXML_ERR_FATAL) {
        continue;
      }

      $parts = [];
      if ($error->file) {
        $parts[] = "File=$error->file";
      }
      $parts[] = "Line=$error->line";
      $parts[] = "Column=$error->column";
      $parts[] = "Code=$error->code";

      $messages[] = implode(" ", $parts) . ": " . trim($error->message);
    }

    return implode("\n", $messages);
  }

  /**
   * Convert an XML element to an array.
   *
   * @param $obj
   *   SimpleXMLElement.
   *
   * @return array
   */
  public static function xmlObjToArray($obj) {
    $arr = [];
    if (is_object($obj)) {
      $obj = get_object_vars($obj);
    }
    if (is_array($obj)) {
      foreach ($obj as $i => $v) {
        if (is_object($v) || is_array($v)) {
          $v = self::xmlObjToArray($v);
        }
        if (empty($v)) {
          $arr[$i] = NULL;
        }
        else {
          $arr[$i] = $v;
        }
      }
    }
    return $arr;
  }

  /**
   * Apply a filter to the textual parts of the markup.
   *
   * @param string $markup
   *   Ex: '<b>Hello world &amp; universe</b>'
   * @param callable $filter
   *   Ex: 'mb_strtoupper'
   * @return string
   *   Ex: '<b>HELLO WORLD &amp; UNIVERSE</b>'
   */
  public static function filterMarkupText(string $markup, callable $filter): string {
    $tokens = static::tokenizeMarkupText($markup);
    foreach ($tokens as &$tokenRec) {
      if ($tokenRec[0] === 'text') {
        $tokenRec[1] = htmlentities($filter(html_entity_decode($tokenRec[1])));
      }
    }
    return implode('', array_column($tokens, 1));
  }

  /**
   * Split marked-up text into markup and text.
   *
   * @param string $markup
   *   Ex: '<a href="#foo">link</a>'
   * @return array
   *   Ex: [
   *     ['node', '<a href="#foo">'],
   *     ['text', 'link'],
   *     ['node', '</a>'],
   *   ]
   */
  protected static function tokenizeMarkupText(string $markup): array {
    $modes = []; /* text, node, (') quoted attr, (") quoted attr */
    $tokens = [];
    $buf = '';

    $startToken = function (string $type) use (&$modes) {
      array_unshift($modes, $type);
    };

    $finishToken = function () use (&$tokens, &$buf, &$modes) {
      $type = array_shift($modes);
      if ($buf !== '') {
        $tokens[] = [$type, $buf];
        $buf = '';
      }
    };

    $startToken('text');
    for ($i = 0; $i < mb_strlen($markup); $i++) {
      $ch = $markup[$i];
      switch ($modes[0] . ' ' . $ch) {
        // Aside: Our style guide makes this harder to read. It's better with 1-case-per-line.
        case 'text <':
          $finishToken();
          $startToken('node');
          $buf .= $ch;
          break;

        case 'node >':
          $buf .= $ch;
          $finishToken();
          $startToken('text');
          break;

        case "node '":
          $buf .= $ch;
          array_unshift($modes, "attr'");
          break;

        case 'node "':
          $buf .= $ch;
          array_unshift($modes, 'attr"');
          break;

        case "attr' '":
          $buf .= $ch;
          array_shift($modes);
          break;

        case 'attr" "':
          $buf .= $ch;
          array_shift($modes);
          break;

        case "attr' \\":
          $buf .= $markup[$i] . $markup[++$i];
          break;

        case 'attr" \\':
          $buf .= $markup[$i] . $markup[++$i];
          break;

        default:
          $buf .= $ch;
          break;
      }
    }
    $finishToken();

    return $tokens;
  }

}
