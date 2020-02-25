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
    if (isset($docs['inheritDoc']) || isset($docs['inheritdoc'])) {
      unset($docs['inheritDoc'], $docs['inheritdoc']);
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
      catch (\ReflectionException $e) {
      }
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

  /**
   * List all traits used by a class and its parents.
   *
   * @param object|string $class
   * @return array
   */
  public static function getTraits($class) {
    $traits = [];
    // Get traits of this class + parent classes
    do {
      $traits = array_merge(class_uses($class), $traits);
    } while ($class = get_parent_class($class));
    // Get traits of traits
    foreach ($traits as $trait => $same) {
      $traits = array_merge(class_uses($trait), $traits);
    }
    return $traits;
  }

}
