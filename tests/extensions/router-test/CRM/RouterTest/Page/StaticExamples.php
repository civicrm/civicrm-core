<?php

class CRM_RouterTest_Page_StaticExamples {

  public static function textAndExit() {
    header('Content-type: text/plain');
    echo "Text and Exit";
    CRM_Utils_System::civiExit();
  }

  public static function submitJson() {
    $data = file_get_contents('php://input');
    $parsed = json_decode($data, TRUE);

    header('Content-type: text/plain');
    echo "OK " . (is_numeric($parsed['number']) ? $parsed['number'] : '');
    CRM_Utils_System::civiExit();
  }

  public static function ajaxReturnJsonResponse() {
    CRM_Core_Page_AJAX::returnJsonResponse([
      __FUNCTION__ => 'OK',
    ]);
  }

  public static function systemSendJsonResponse() {
    CRM_Utils_System::sendJSONResponse([
      __FUNCTION__ => 'OK',
    ], 499);
  }

}
