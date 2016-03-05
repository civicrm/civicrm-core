<?php

namespace Civi\Test;

/**
 * Interface HeadlessInterface
 * @package Civi\Test
 *
 * To run your test against a fake, headless database, flag it with the
 * HeadlessInterface. CiviTestListener will automatically boot
 *
 * Alternatively, if you wish to run a test in a live (CMS-enabled) environment,
 * flag it with EndToEndInterface.
 *
 * You may mix-in additional features for headless tests:
 *  - HookInterface: Auto-register any functions named "hook_civicrm_foo()".
 *  - TransactionalInterface: Wrap all work in a transaction, and rollback at the end.
 *
 * @see EndToEndInterface
 * @see HookInterface
 * @see TransactionalInterface
 */
interface HeadlessInterface {

  /**
   * The setupHeadless functions runs at the start of each test case, right before
   * the headless environment reboots.
   *
   * It should perform any necessary steps required for putting the database
   * in a consistent baseline -- such as loading schema and extensions.
   *
   * The utility `\Civi\Test::headless()` provides a number of helper functions
   * for managing this setup, and it includes optimizations to avoid redundant
   * setup work.
   *
   * @see \Civi\Test
   */
  public function setUpHeadless();

}
