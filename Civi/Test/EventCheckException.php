<?php

namespace Civi\Test;

/**
 * The "event checks" ensure that hooks/events satisfy some expected contracts.
 * This exception is emitted if there is a non-conformant hook/event.
 * It should only be emitted on development environments.
 */

if (class_exists('PHPUnit\Framework\AssertionFailedError')) {

  class EventCheckException extends \PHPUnit\Framework\AssertionFailedError {

  }

}
else {

  class EventCheckException extends \RuntimeException {

  }

}
