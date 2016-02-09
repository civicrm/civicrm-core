<?php

namespace Civi\Test;

/**
 * Interface HeadlessInterface
 * @package Civi\Test
 *
 * Mark a test with TransactionalInterface to instruct CiviTestListener to wrap
 * each test in a transaction (and rollback).
 *
 * Note: At time of writing, CiviTestListener only supports using TransactionalInterface if
 * the test is in-process and runs with CIVICRM_UF==UnitTests.
 *
 * @see HeadlessInterface
 */
interface TransactionalInterface {

}
