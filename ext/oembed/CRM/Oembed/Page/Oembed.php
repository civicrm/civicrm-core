<?php

use CRM_Oembed_ExtensionUtil as E;

/**
 * The `civicrm/oembed` route supports these modes:
 *
 * - civicrm/oembed?format=json&url=ABSOLUTE_URL
 * - civicrm/oembed?format=xml&url=ABSOLUTE_URL
 *
 * The absolute URL must be an internal reference (matching the current domain or BASE_URL).
 * It may be a reference to `civicrm/share?url=OTHER_ABSOLUTE_URL` (in which case will be
 * recursively evaluated).
 *
 * @link https://oembed.com
 */
class CRM_Oembed_Page_Oembed extends CRM_Core_Page {

  public function run() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      throw new \CRM_Core_Exception("Unsupported method");
    }

    $format = CRM_Utils_Request::retrieve('format', 'String') ?: 'share';
    $options = [];
    $this->extractOptions($options, $_GET);

    try {
      $targetUrl = CRM_Utils_Request::retrieve('url', 'String');
      $targetUrlParsed = \CRM_Utils_Url::parseInternalRoute($targetUrl);
      parse_str($targetUrlParsed['query'] ?? '', $targetUrlQuery);
    }
    catch (\CRM_Core_Exception $e) {
      return $this->sendError('Invalid URL: ' . $e->getMessage());
    }

    if ($targetUrlParsed['path'] === 'civicrm/share') {
      $this->extractOptions($options, $targetUrlQuery);
      try {
        $effectiveUrl = $targetUrlQuery['url'];
        $effectiveUrlParsed = \CRM_Utils_Url::parseInternalRoute($effectiveUrl);
        parse_str($effectiveUrlParsed['query'] ?? '', $effectiveUrlQuery);
      }
      catch (\CRM_Core_Exception $e) {
        return $this->sendError('Invalid URL (nested): ' . $e->getMessage());
      }
    }
    else {
      $effectiveUrl = $targetUrl;
      $effectiveUrlParsed = $targetUrlParsed;
      $effectiveUrlQuery = $targetUrlQuery;
    }

    if (!Civi::service('oembed')->isAllowedRoute($effectiveUrlParsed['path'])) {
      return $this->sendError('Route does permit embedding');
    }

    switch ($format) {
      case 'json':
        $oembed = Civi::service('oembed')->create($effectiveUrlParsed['path'], $effectiveUrlQuery, $options);
        return $this->send(200, ['Content-type' => 'application/json'], json_encode($oembed));

      case 'xml':
        $oembed = Civi::service('oembed')->create($effectiveUrlParsed['path'], $effectiveUrlQuery, $options);
        return $this->send(200, ['Content-type' => 'text/xml'], $this->encodeXml($oembed));

      default:
        return $this->sendError('Unsupported format');
    }
  }

  private function send(int $status, array $headers, string $body) {
    $response = new \GuzzleHttp\Psr7\Response($status, $headers, $body);
    CRM_Utils_System::sendResponse($response);
  }

  private function sendError(string $body) {
    return $this->send(501, ['Content-type' => 'text/plain'], $body);
  }

  private function encodeXml(array $oembed) {
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8" standalone="yes"?><oembed></oembed>');
    foreach ($oembed as $key => $value) {
      $xml->addChild($key, htmlentities($value));
    }
    return $xml->asXML();
  }

  /**
   * Given a set of GET query parameters, find any that are intended as control-options for oEmbed.
   */
  private function extractOptions(array &$options, array $newValues) {
    foreach (['maxwidth', 'maxheight'] as $field) {
      if (isset($newValues[$field]) && is_numeric($newValues[$field])) {
        $options[$field] = $newValues[$field];
      }
    }
  }

}
