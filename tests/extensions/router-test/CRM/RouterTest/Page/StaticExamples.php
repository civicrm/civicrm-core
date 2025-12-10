<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CRM_RouterTest_Page_StaticExamples {

  public static function textAndExit() {
    header('Content-type: text/plain');
    echo "Text and Exit";
    CRM_Utils_System::civiExit();
  }

  public static function pathWithData() {
    header('Content-type: text/plain');
    echo "Path Data " . base64_encode(CRM_Utils_System::currentPath());
    CRM_Utils_System::civiExit();
  }

  public static function psr7PathWithData(ServerRequestInterface $request): ResponseInterface {
    return new \GuzzleHttp\Psr7\Response(200,
      ['Content-Type' => 'text/plain'],
      "Path Data " . base64_encode($request->getUri()->getPath())
    );
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
    ], 429);
  }

  public static function psr7(ServerRequestInterface $request): ResponseInterface {
    $parsed = json_decode($request->getBody()->getContents(), TRUE);
    return new \GuzzleHttp\Psr7\Response(428,
      ['X-Foo' => 'Bar', 'Content-Type' => 'application/json'],
      json_encode([
        __FUNCTION__ => 'hello ' . (is_numeric($parsed['number']) ? $parsed['number'] : ''),
        'input' => $request->getQueryParams()['whiz'],
      ])
    );
  }

  public static function psr7OldInput(ServerRequestInterface $request): ResponseInterface {
    $parsed = json_decode(file_get_contents('php://input'), TRUE);
    return new \GuzzleHttp\Psr7\Response(200,
      ['X-Foo' => 'Bar', 'Content-Type' => 'application/json'],
      json_encode([
        __FUNCTION__ => 'hello ' . (is_numeric($parsed['number']) ? $parsed['number'] : ''),
        'input' => $request->getQueryParams()['whiz'],
      ])
    );
  }

}
