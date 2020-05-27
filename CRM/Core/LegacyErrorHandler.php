<?php

/**
 * Class CRM_Core_LegacyErrorHandler
 */
class CRM_Core_LegacyErrorHandler {

  /**
   * @param \Civi\Core\Event\UnhandledExceptionEvent $event
   * @throws Exception
   */
  public static function handleException($event) {
    $e = $event->exception;
    if ($e instanceof CRM_Core_Exception) {
      $params = $e->getErrorData();
      $message = $e->getMessage();
      $session = CRM_Core_Session::singleton();
      $session->setStatus(
        $message,
        CRM_Utils_Array::value('message_title', $params),
        CRM_Utils_Array::value('message_type', $params, 'error')
      );
    }
  }

}
