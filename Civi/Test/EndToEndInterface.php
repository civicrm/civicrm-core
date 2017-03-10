<?php
namespace Civi\Test;

/**
 * Interface EndToEndInterface
 * @package Civi\Test
 *
 * To run your test against a live, CMS-integrated database, flag it with the the
 * EndToEndInterface.
 *
 * Note: The global variable $_CV should be pre-populated with some interesting data:
 *
 * - $_CV['CMS_URL']
 * - $_CV['ADMIN_USER']
 * - $_CV['ADMIN_PASS']
 * - $_CV['ADMIN_EMAIL']
 * - $_CV['DEMO_USER']
 * - $_CV['DEMO_PASS']
 * - $_CV['DEMO_EMAIL']
 *
 * Alternatively, if you wish to run a test in a headless environment,
 * flag it with HeadlessInterface.
 *
 * @see HeadlessInterface
 */
interface EndToEndInterface {

}
