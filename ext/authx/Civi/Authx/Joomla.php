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

class Joomla implements AuthxInterface {

  /**
   * Joomla constructor.
   */
  public function __construct() {
    if (version_compare(JVERSION, '4.0', 'lt')) {
      jimport('joomla.application.component.helper');
      jimport('joomla.database.table');
      jimport('joomla.user.helper');
    }
  }

  /**
   * @inheritDoc
   */
  public function checkPassword(string $username, string $password) {
    if (version_compare(JVERSION, '4.0', 'ge')) {
      $db = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
      $JUserTable = new \Joomla\CMS\Table\User($db);
    }
    else {
      $JUserTable = \JTable::getInstance('User', 'JTable');
      $db = $JUserTable->getDbo();
    }

    $query = $db->getQuery(TRUE);
    $query->select('id, name, username, email, password');
    $query->from($JUserTable->getTableName());
    $query->where('(LOWER(username) = LOWER(' . $db->quote($username) . ')) AND (block = 0)');
    $db->setQuery($query, 0, 0);
    $users = $db->loadObjectList();

    if (!empty($users)) {
      $user = array_shift($users);
      if (version_compare(JVERSION, '4.0', 'ge')) {
        $verified = \Joomla\CMS\User\UserHelper::verifyPassword($password, $user->password, $user->id);
        return $verified ? $user->id : NULL;
      }
      elseif (is_callable(['JUserHelper', 'verifyPassword'])) {
        $verified = \JUserHelper::verifyPassword($password, $user->password, $user->id);
        return $verified ? $user->id : NULL;
      }
      else {
        throw new \CRM_Core_Exception("This version of Joomla does not support verifyPassword().");
      }
    }

    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function loginSession($userId) {
    if (version_compare(JVERSION, '4.0', 'ge')) {
      $user = new \Joomla\CMS\User\User($userId);
      $session = \Joomla\CMS\Factory::getApplication()->getSession();
    }
    else {
      $user = new \JUser($userId);
      $session = \JFactory::getSession();
    }
    $session->set('user', $user);
  }

  /**
   * @inheritDoc
   */
  public function logoutSession() {
    if (version_compare(JVERSION, '4.0', 'ge')) {
      $session = \Joomla\CMS\Factory::getApplication()->getSession();
    }
    else {
      $session = \JFactory::getSession();
    }
    $session->destroy();
  }

  /**
   * @inheritDoc
   */
  public function loginStateless($userId) {
    // Joomla CMSApplication always starts a session. It is impossible for any extension/plugin/component to influence this.
    // See eg https://github.com/joomla/joomla-cms/issues/8772
    // Our implementation here uses a simple (if ugly) mitigation to ensure that
    // (1) all data is thread-local and (2) nothing is persisted from the request.

    // I'm not aware of a more canonical solution. Since this tied-up with the `AbstractApplication` class
    // hierarchy, perhaps one is supposed to make a new entry-point (`[cms.root]/components/com_civicrm/myapp.php`)?

    // In any event, this work-around passes `AllFlowsTest::testMultipleStateless`.

    $this->logoutSession();
    $this->loginSession($userId);
  }

  /**
   * @inheritDoc
   */
  public function getCurrentUserId() {
    if (version_compare(JVERSION, '4.0', 'ge')) {
      $user = \Joomla\CMS\Factory::getApplication()->getIdentity();
    }
    else {
      $user = \JFactory::getUser();
    }
    return (empty($user) || $user->guest) ? NULL : $user->id;
  }

  /**
   * @inheritDoc
   */
  public function getUserIsBlocked($userId) {
    // ToDo
    return FALSE;
  }

}
