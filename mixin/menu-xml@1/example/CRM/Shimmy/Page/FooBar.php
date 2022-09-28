<?php
class CRM_Shimmy_Page_FooBar extends CRM_Core_Page {

  public function run() {
    $response = (new \GuzzleHttp\Psr7\Response())
      ->withHeader('Content-Type', 'text/plain')
      ->withBody(\GuzzleHttp\Psr7\stream_for('hello world ' . microtime(1)));

    CRM_Utils_System::sendResponse($response);

    parent::run();
  }

}
