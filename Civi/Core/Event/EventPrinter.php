<?php


namespace Civi\Core\Event;

/**
 * This provides some rarely used debug utilities.
 *
 * @package Civi\Core\Event
 */
class EventPrinter {

  /**
   * Prepare a name to identify a callable element.
   *
   * @param mixed $callback
   * @return string
   */
  public static function formatName($callback): string {
    $normalizeNamespace = function($symbol) {
      return $symbol[0] === '\\' ? substr($symbol, 1) : $symbol;
    };
    if (is_array($callback)) {
      [$a, $b] = $callback;
      if (is_object($a)) {
        return $normalizeNamespace(get_class($a)) . "->$b(\$e)";
      }
      elseif (is_string($a)) {
        return $normalizeNamespace($a) . "::$b(\$e)";
      }
    }
    elseif (is_string($callback)) {
      return $normalizeNamespace($callback) . '(\$e)';
    }
    elseif ($callback instanceof ServiceListener || $callback instanceof HookStyleListener) {
      return (string) $callback;
    }
    else {
      try {
        $f = new \ReflectionFunction($callback);
        return 'closure<' . $f->getFileName() . '@' . $f->getStartLine() . '>($e)';
      }
      catch (\ReflectionException $e) {
      }
    }

    return 'unidentified';
  }

}
