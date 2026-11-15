<?php

use Civi\Core\Attribute\CiviEventListener;

/**
 * Class CRM_Core_LegacyErrorHandler
 */
class CRM_Core_LegacyErrorHandler {

  /**
   * @param \Civi\Core\Event\UnhandledExceptionEvent $event
   * @throws Exception
   */
  #[CiviEventListener(event: 'hook_civicrm_unhandled_exception', priority: -200)]
  public static function handleException($event) {
    $e = $event->exception;
    if ($e instanceof CRM_Core_Exception) {
      $params = $e->getErrorData();
      $message = $e->getMessage();
      $session = CRM_Core_Session::singleton();
      $session->setStatus(
        htmlspecialchars($message),
        htmlspecialchars($params['message_title'] ?? ts('Error')),
        $params['message_type'] ?? 'error'
      );
    }
  }

}
