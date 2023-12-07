<?php

namespace Civi\Core;

use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class UnoptimizedEventDispatcher
 * @package Civi\Core
 *
 * Since symfony 4.3, the EventDispatcher contains optimization code that
 * converts all functions into closures. This causes test runs to crash.
 * Until we can figure out why this simply skips the optimization which is
 * roughly the same as before 4.3. The optimization is skipped if the object
 * being instantiated is not the base class EventDispatcher (https://github.com/symfony/event-dispatcher/blob/75f99d7489409207d09c6cd75a6c773ccbb516d5/EventDispatcher.php#L41)
 */
class UnoptimizedEventDispatcher extends EventDispatcher {
}
