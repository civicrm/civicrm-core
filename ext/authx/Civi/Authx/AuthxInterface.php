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

namespace Civi\Authx;

/**
 * Interface AuthxInterface
 * @package Civi\Authx
 *
 * Each user-framework (Drupal, Joomla, etc) has a slightly different set of
 * methods for authenticating users and establishing a login. Provide an
 * implementation of this interface for each user-framework.
 *
 * This is conceptually similar to some methods in `CRM_Utils_System_*`,
 * but with less sadism.
 */
interface AuthxInterface {

  /**
   * Determine if the password is correct for the user.
   *
   * @param string $username
   *   The symbolic username known to the user.
   * @param string $password
   *   The plaintext secret which identifies this user.
   *
   * @return int|string|NULL
   *   If the password is correct, this returns the internal user ID.
   *   If the password is incorrect (or if passwords are not supported), it returns NULL.
   */
  public function checkPassword(string $username, string $password);

  /**
   * Set the active user the in the CMS, binding the user ID durably to the session.
   *
   * @param int|string $userId
   *   The UF's internal user ID.
   */
  public function loginSession($userId);

  /**
   * Close an open session.
   *
   * This SHOULD NOT produce an HTTP response (redirect). However, consumers
   * of the authx logout SHOULD be robust against unconventional responses.
   */
  public function logoutSession();

  /**
   * Set the active user the in the CMS -- but do *not* start a session.
   *
   * @param int|string $userId
   *   The UF's internal user ID.
   */
  public function loginStateless($userId);

  /**
   * Logout from a stateless session -- removing in-memory data about the current login.
   *
   * The idea of "logout from stateless" sounds a bit like an oxymoron,
   * but it has some appeal when doing single-process PHPUnit tests.
   *
   * This method is currently implemented in Standalone. It might be nice to have
   * a generalized/portable form. But it's not very urgent, and it would require
   * a bunch of testing. If you need the general variant, then... go for it...
   */
  // public function logoutStateless();

  /**
   * Determine which (if any) user is currently logged in.
   *
   * @return int|string|NULL
   *   The UF's internal user ID for the active user.
   *   NULL indicates anonymous (not logged into CMS).
   */
  public function getCurrentUserId();

  /**
   * Determine the user status, if is blocked or not.
   *
   * @param int|string $userId
   * @return int|NULL
   */
  public function getUserIsBlocked($userId);

}
