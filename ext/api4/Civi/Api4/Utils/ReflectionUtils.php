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

namespace Civi\Api4\Utils;

/**
 * Just another place to put static functions...
 */
class ReflectionUtils {
  /**
   * @param \Reflector|\ReflectionClass $reflection
   * @param string $type
   *   If we are not reflecting the class itself, specify "Method", "Property", etc.
   *
   * @return array
   */
  public static function getCodeDocs($reflection, $type = NULL) {
    $docs = self::parseDocBlock($reflection->getDocComment());

    // Recurse into parent functions
    if (isset($docs['inheritDoc'])) {
      unset($docs['inheritDoc']);
      $newReflection = NULL;
      try {
        if ($type) {
          $name = $reflection->getName();
          $reflectionClass = $reflection->getDeclaringClass()->getParentClass();
          if ($reflectionClass) {
            $getItem = "get$type";
            $newReflection = $reflectionClass->$getItem($name);
          }
        }
        else {
          $newReflection = $reflection->getParentClass();
        }
      }
      catch (\ReflectionException $e) {}
      if ($newReflection) {
        // Mix in
        $additionalDocs = self::getCodeDocs($newReflection, $type);
        if (!empty($docs['comment']) && !empty($additionalDocs['comment'])) {
          $docs['comment'] .= "\n\n" . $additionalDocs['comment'];
        }
        $docs += $additionalDocs;
      }
    }
    return $docs;
  }

  /**
   * @param string $comment
   * @return array
   */
  public static function parseDocBlock($comment) {
    $info = [];
    foreach (preg_split("/((\r?\n)|(\r\n?))/", $comment) as $num => $line) {
      if (!$num || strpos($line, '*/') !== FALSE) {
        continue;
      }
      $line = ltrim(trim($line), '* ');
      if (strpos($line, '@') === 0) {
        $words = explode(' ', $line);
        $key = substr($words[0], 1);
        if ($key == 'var') {
          $info['type'] = explode('|', $words[1]);
        }
        elseif ($key == 'options') {
          $val = str_replace(', ', ',', implode(' ', array_slice($words, 1)));
          $info['options'] = explode(',', $val);
        }
        else {
          // Unrecognized annotation, but we'll duly add it to the info array
          $val = implode(' ', array_slice($words, 1));
          $info[$key] = strlen($val) ? $val : TRUE;
        }
      }
      elseif ($num == 1) {
        $info['description'] = $line;
      }
      elseif (!$line) {
        if (isset($info['comment'])) {
          $info['comment'] .= "\n";
        }
      }
      else {
        $info['comment'] = isset($info['comment']) ? "{$info['comment']}\n$line" : $line;
      }
    }
    if (isset($info['comment'])) {
      $info['comment'] = trim($info['comment']);
    }
    return $info;
  }

}
