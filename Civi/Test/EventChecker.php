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

namespace Civi\Test;

use Civi\Core\Event\EventScanner;

class EventChecker {

  /**
   * @var \Civi\Test\EventCheck[]|null
   */
  private $allChecks = NULL;

  /**
   * @var \Civi\Test\EventCheck[]|null
   */
  private $activeChecks = NULL;

  /**
   * @param \PHPUnit\Framework\Test|NULL $test
   *
   * @return $this
   */
  public function start($test) {
    if ($this->activeChecks === NULL) {
      $this->activeChecks = [];
      foreach ($this->findAll() as $template) {
        /** @var EventCheck $template */
        if ($template->isSupported($test)) {
          $checker = clone $template;
          $this->activeChecks[] = $checker;
        }
      }
    }
    return $this;
  }

  /**
   * @return $this
   */
  public function addListeners() {
    $d = \Civi::dispatcher();
    foreach ($this->activeChecks ?: [] as $checker) {
      /** @var EventCheck $checker */
      $d->addListenerMap($checker, EventScanner::findListeners($checker));
      // For the moment, KISS. But we may want a counter at some point - to ensure things actually run.
      //foreach (EventScanner::findListeners($checker) as $event => $listeners) {
      //  foreach ($listeners as $listener) {
      //    $d->addListener($event,
      //      function($args...) use ($listener) {
      //        $count++;
      //        $m = $listener[1];
      //        $checker->$m(...$args);
      //      },
      //      $listener[1] ?? 0
      //    );
      //  }
      //}
    }
    return $this;
  }

  /**
   * @return $this
   */
  public function stop() {
    // NOTE: In test environment, dispatcher will be removed regardless.
    $this->activeChecks = NULL;
    return $this;
  }

  /**
   * @return EventCheck[]
   */
  protected function findAll() {
    if ($this->allChecks === NULL) {
      $all = [];
      $testDir = \Civi::paths()->getPath('[civicrm.root]/tests/events');
      $files = \CRM_Utils_File::findFiles($testDir, '*.evch.php', TRUE);
      sort($files);
      foreach ($files as $file) {
        $all[$file] = require $testDir . '/' . $file;
      }
      $this->allChecks = $all;
    }

    return $this->allChecks;
  }

}
