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
   * @param array $vars
   *   Variable substitutions to perform in the docblock
   * @return array
   */
  public static function getCodeDocs($reflection, $type = NULL, $vars = []) {
    $comment = $reflection->getDocComment();
    foreach ($vars as $key => $val) {
      $comment = str_replace('$' . strtoupper(\CRM_Utils_String::pluralize($key)), \CRM_Utils_String::pluralize($val), $comment);
      $comment = str_replace('$' . strtoupper($key), $val, $comment);
    }
    $docs = self::parseDocBlock($comment);

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
        $additionalDocs = self::getCodeDocs($newReflection, $type, $vars);
        if (!empty($docs['comment']) && !empty($additionalDocs['comment'])) {
          $docs['comment'] .= "\n\n" . $additionalDocs['comment'];
        }
        $docs += $additionalDocs;
      }
    }
    return $docs;
  }

  /**
   * Parses a PHPDoc-style comment block into structured metadata.
   *
   * Supports array shapes in @param, @return, and @var, including multiline and
   * nested array shapes.
   *
   * @param string $comment
   *   The PHPDoc comment block.
   *
   * @return array
   *   The structured parsed information.
   */
  public static function parseDocBlock(string $comment): array {
    $info = [];
    $param = NULL;

    $rawLines = preg_split("/((\r?\n)|(\r\n?))/", $comment);
    $lines = [];
    foreach (array_keys($rawLines) as $num) {
      // We change rawLines as we iterate so load in-loop via $num.
      $line = $rawLines[$num];
      if ($num === 0 || str_contains($line, '*/')) {
        continue;
      }
      $line = self::cleanLine($line);
      if (str_contains($line, 'array{')) {
        // We have the start of an array shape.
        // Count opening and closing braces to detect when shape ends.
        $openBraces = substr_count($line, '{');
        $closeBraces = substr_count($line, '}');
        $nextLine = $num + 1;
        while ($openBraces > $closeBraces) {
          if (!array_key_exists($nextLine, $rawLines)) {
            // If we get to the end and it is still unbalanced then just
            // ignore the whole broken array shape.
            $line = '*/';
            break;
          }
          // Here we need to absorb as many lines as possible to capture the full array shape.
          $line .= ' ' . self::cleanLine($rawLines[$nextLine]);
          // Set the line we just absorbed to be skipped.
          $rawLines[$nextLine] = '*/';
          $openBraces = substr_count($line, '{');
          $closeBraces = substr_count($line, '}');
          $nextLine++;
        }
      }
      $lines[] = $line;
    }

    foreach ($lines as $num => $line) {

      if (str_starts_with(ltrim($line), '@')) {
        $words = preg_split('/\s+/', ltrim($line, ' @'));
        $key = array_shift($words);
        $param = NULL;
        if ($key == 'var') {
          $varType = implode(' ', $words);
          if (str_starts_with($varType, 'array{') && str_contains($varType, '}')) {
            $info['type'] = ['array'];
            $info['shape'] = self::parseArrayShape($varType);
          }
          else {
            $info['type'] = explode('|', strtolower($words[0]));
          }
        }
        elseif ($key === 'return') {
          $return_type = implode(' ', $words);
          if (str_starts_with($return_type, 'array{')) {
            $info['return'] = [
              'type' => ['array'],
              'shape' => self::parseArrayShape($return_type),
            ];
          }
          else {
            $info['return'] = explode('|', $return_type);
          }
        }
        elseif ($key == 'options') {
          $val = str_replace(', ', ',', implode(' ', $words));
          $info[$key] = explode(',', $val);
        }
        elseif ($key == 'throws' || $key == 'see') {
          $info[$key][] = implode(' ', $words);
        }
        elseif ($key == 'param' && $words) {
          // Locate param name starting with $
          $paramIndex = NULL;
          foreach ($words as $i => $w) {
            if (str_starts_with($w, '$')) {
              $paramIndex = $i;
              break;
            }
          }

          if ($paramIndex !== NULL) {
            $param = rtrim($words[$paramIndex], '-:()/');
            //ltrim(implode(' ', $words), '-: ') : ''
            $typeString = implode(' ', array_slice($words, 0, $paramIndex));
            $description = ltrim(implode(' ', array_slice($words, $paramIndex + 1)), '-: ');
          }
          else {
            // Fallback
            $param = '$unknown';
            $typeString = implode(' ', $words);
            $description = '';
          }
          if (str_starts_with($typeString, 'array{')) {
            $info['params'][$param] = [
              'type' => ['array'],
              'shape' => self::parseArrayShape($typeString),
              'description' => $description,
              'comment' => '',
            ];
          }
          else {
            $type = $typeString !== '' ? explode('|', strtolower($typeString)) : NULL;
            $info['params'][$param] = [
              'type' => $type,
              'description' => $description,
              'comment' => '',
            ];
          }
        }
        else {
          // Unrecognized annotation, but we'll duly add it to the info array
          $val = implode(' ', $words);
          $info[$key] = strlen($val) ? $val : TRUE;
        }
      }
      elseif ($param) {
        $info['params'][$param]['comment'] .= $line . "\n";
      }
      elseif ($num === 0) {
        $info['description'] = ucfirst($line);
      }
      elseif (!$line) {
        $info['comment'] = isset($info['comment']) ? "{$info['comment']}\n" : NULL;
      }
      // For multi-line description.
      elseif (count($info) === 1 && isset($info['description']) && substr($info['description'], -1) !== '.') {
        $info['description'] .= ' ' . $line;
      }
      else {
        $info['comment'] = isset($info['comment']) ? "{$info['comment']}\n$line" : $line;
      }
    }

    if (isset($info['comment'])) {
      $info['comment'] = rtrim($info['comment']);
    }

    return $info;
  }

  /**
   * Parses a complex PHPDoc array shape definition into a structured array.
   *
   * Supports nested array shapes using recursion.
   *
   * @param string $definition
   *   The array shape definition, e.g., 'array{foo: string, bar: array{baz: int}}'.
   *
   * @return array
   *   A structured representation of the array shape.
   */
  protected static function parseArrayShape(string $definition): array {
    $definition = trim($definition);

    if (str_starts_with($definition, 'array{') && str_ends_with($definition, '}')) {
      // Remove array{ and ending }.
      $definition = substr($definition, 6, -1);
    }

    $shape = [];
    $length = strlen($definition);
    $buffer = '';
    $brace_level = 0;
    $parts = [];

    for ($i = 0; $i < $length; $i++) {
      $char = $definition[$i];

      if ($char === '{') {
        $brace_level++;
      }
      elseif ($char === '}') {
        $brace_level--;
      }
      elseif ($char === ',' && $brace_level === 0) {
        $parts[] = trim($buffer);
        $buffer = '';
        continue;
      }

      $buffer .= $char;
    }

    if (trim($buffer) !== '') {
      $parts[] = trim($buffer);
    }

    foreach ($parts as $part) {
      if (!str_contains($part, ':')) {
        continue;
      }

      [$key, $type] = explode(':', $part, 2);
      $key = trim($key);
      $type = trim($type);

      if (str_starts_with($type, 'array{')) {
        $shape[$key] = [
          'type' => ['array'],
          'shape' => self::parseArrayShape($type),
        ];
      }
      else {
        $shape[$key] = array_map('trim', explode('|', $type));
      }
    }

    return $shape;
  }

  /**
   * List all traits used by a class and its parents.
   *
   * @param object|string $class
   * @return string[]
   */
  public static function getTraits($class): array {
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

  /**
   * Get a list of standard properties which can be written+read by outside callers.
   *
   * @param string $class
   */
  public static function findStandardProperties($class): iterable {
    try {
      /** @var \ReflectionClass $clazz */
      $clazz = new \ReflectionClass($class);

      yield from [];
      foreach ($clazz->getProperties(\ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PUBLIC) as $property) {
        if (!$property->isStatic() && $property->getName()[0] !== '_') {
          yield $property;
        }
      }
    }
    catch (\ReflectionException $e) {
      throw new \RuntimeException(sprintf("Cannot inspect class %s.", $class));
    }
  }

  /**
   * Check if a class method is deprecated
   *
   * @param string $className
   * @param string $methodName
   * @return bool
   * @throws \ReflectionException
   */
  public static function isMethodDeprecated(string $className, string $methodName): bool {
    $reflection = new \ReflectionClass($className);
    $docBlock = $reflection->getMethod($methodName)->getDocComment();
    return str_contains($docBlock, "@deprecated");
  }

  /**
   * Find any methods in this class which match the given prefix.
   *
   * @param string $class
   * @param string $prefix
   */
  public static function findMethodHelpers($class, string $prefix): iterable {
    try {
      /** @var \ReflectionClass $clazz */
      $clazz = new \ReflectionClass($class);

      yield from [];
      foreach ($clazz->getMethods(\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED) as $m) {
        if (str_starts_with($m->getName(), $prefix)) {
          yield $m;
        }
      }
    }
    catch (\ReflectionException $e) {
      throw new \RuntimeException(sprintf("Cannot inspect class %s.", $class));
    }
  }

  /**
   * Cast the $value to the preferred $type (if we're fairly confident).
   *
   * This is like PHP's `settype()` but totally not. It only casts in narrow circumstances.
   * This reflects an opinion that some castings are better than others.
   *
   * These will be converted:
   *
   *    cast('123', 'int') => 123
   *    cast('123.4', 'float') => 123.4
   *    cast('0', 'bool') => FALSE
   *    cast(1, 'bool') => TRUE
   *
   * However, a string like 'hello' will never cast to bool, int, or float -- because that's
   * a senseless request. We'll leave that to someone else to figure.
   *
   * @param mixed $value
   * @param array $paramInfo
   * @return mixed
   *   If the $value is agreeable to casting according to a type-rule from $paramInfo, then
   *   we return the converted value. Otherwise, return the original value.
   */
  public static function castTypeSoftly($value, array $paramInfo) {
    if (count($paramInfo['type'] ?? []) !== 1) {
      // I don't know when or why fields can have multiple types. We're just gone leave-be.
      return $value;
    }

    switch ($paramInfo['type'][0]) {
      case 'bool':
        if (in_array($value, [0, 1, '0', '1'], TRUE)) {
          return (bool) $value;
        }
        break;

      case 'int':
        if (is_numeric($value)) {
          return (int) $value;
        }
        break;

      case 'float':
        if (is_numeric($value)) {
          return (float) $value;
        }
        break;

    }

    return $value;
  }

  /**
   * @param string $line
   *
   * @return string
   */
  private static function cleanLine(string $line): string {
    $line = ltrim(trim($line), '*');
    if (strlen($line) && $line[0] === ' ') {
      $line = substr($line, 1);
    }
    return $line;
  }

}
