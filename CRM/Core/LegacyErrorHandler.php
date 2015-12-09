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

      // @todo remove this code - legacy redirect path is an interim measure for moving redirects out of BAO
      // to somewhere slightly more acceptable. they should not be part of the exception class & should
      // be managed @ the form level - if you find a form that is triggering this piece of code
      // you should log a ticket for it to be removed with details about the form you were on.
      if (!empty($params['legacy_redirect_path'])) {
        if (CRM_Utils_System::isDevelopment()) {
          $intentionalENotice = "How did you get HERE?! - Please log in JIRA";
          // here we could set a message telling devs to log it per above
        }
        CRM_Utils_System::redirect($params['legacy_redirect_path'], $params['legacy_redirect_query']);
      }
    }
  }

}
