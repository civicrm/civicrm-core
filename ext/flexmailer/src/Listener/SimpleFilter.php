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
namespace Civi\FlexMailer\Listener;

use Civi\FlexMailer\Event\ComposeBatchEvent;

/**
 * Class SimpleFilter
 * @package Civi\FlexMailer\Listener
 *
 * Provides a slightly sugary utility for writing a filter
 * that applies to email content.
 *
 * Note: This class is not currently used within org.civicrm.flexmailer, but
 * it ma ybe used by other extensions.
 */
class SimpleFilter {

  /**
   * Apply a filter function to each instance of a property of an email.
   *
   * This variant visits each value one-by-one.
   *
   * @param \Civi\FlexMailer\Event\ComposeBatchEvent $e
   * @param string $field
   *   The name of a MailParam field.
   * @param mixed $filter
   *   Function($value, FlexMailerTask $task, ComposeBatchEvent $e).
   *   The function returns a filtered value.
   * @throws \CRM_Core_Exception
   * @see \CRM_Utils_Hook::alterMailParams
   */
  public static function byValue(ComposeBatchEvent $e, $field, $filter) {
    foreach ($e->getTasks() as $task) {
      /** @var \Civi\FlexMailer\FlexMailerTask $task */
      $value = $task->getMailParam($field);
      if ($value !== NULL) {
        $task->setMailParam($field, call_user_func($filter, $value, $task, $e));
      }
    }
  }

  /**
   * Apply a filter function to a property of all email messages.
   *
   * This variant visits the values as a big array. This makes it
   * amenable to batch-mode filtering in preg_replace or preg_replace_callback.
   *
   * @param \Civi\FlexMailer\Event\ComposeBatchEvent $e
   * @param string $field
   *   The name of a MailParam field.
   * @param mixed $filter
   *   Function($values, ComposeBatchEvent $e).
   *   Return a modified list of values.
   * @throws \CRM_Core_Exception
   * @see \CRM_Utils_Hook::alterMailParams
   */
  public static function byColumn(ComposeBatchEvent $e, $field, $filter) {
    $tasks = $e->getTasks();
    $values = [];

    foreach ($tasks as $k => $task) {
      /** @var \Civi\FlexMailer\FlexMailerTask $task */
      $value = $task->getMailParam($field);
      if ($value !== NULL) {
        $values[$k] = $value;
      }
    }

    $values = call_user_func_array($filter, [$values, $e]);

    foreach ($values as $k => $value) {
      $tasks[$k]->setMailParam($field, $value);
    }
  }

}
