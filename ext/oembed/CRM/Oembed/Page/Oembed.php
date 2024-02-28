<?php

use CRM_Oembed_ExtensionUtil as E;

/**
 * The `civicrm/oembed` route supports three modes:
 *
 * civicrm/oembed?format=json&url=ABSOLUTE_URL
 * civicrm/oembed?format=xml&url=ABSOLUTE_URL
 * civicrm/oembed?format=share&route=LOGICAL_URL
 *
 * The JSON and XML modes represent the actual oEmbed callback implementations.
 *
 * The "Share" mode defines the "Oembed Share Links". These are stub URLs. They exist to
 * denote embeddable content, but they don't really host the content themselves.
 */
class CRM_Oembed_Page_Oembed extends CRM_Core_Page {

  public function run() {
    $format = CRM_Utils_Request::retrieve('format', 'String') ?: 'share';
    $url = CRM_Utils_Request::retrieve('url', 'String');

    try {
      $parsedUrl = \CRM_Utils_Url::parseInternalRoute($url);
      parse_str($parsedUrl['query'] ?? '', $parsedUrlQuery);
    }
    catch (CRM_Core_Exception $e) {
      return $this->sendError('Invalid URL: ' . $e->getMessage());
    }

    if ($parsedUrl['path'] == 'civicrm/oembed') {
      try {
        $parsedUrlQueryUrl = \CRM_Utils_Url::parseInternalRoute($parsedUrlQuery['url']);
        $parsedUrl['path'] = $parsedUrlQueryUrl['path'];
      } catch (CRM_Core_Exception $e) {
      }
    }

    // $isShared = ($parsedUrl['path'] === 'civicrm/oembed' && $parsedUrlQuery['format'] === 'share');
    if (!Civi::service('iframe.router')->isAllowedRoute($parsedUrl['path'])) {
      return $this->sendError('Route does permit embedding');
    }

    $options = [];
    foreach (['maxwidth', 'maxheight'] as $field) {
      if ($value = CRM_Utils_Request::retrieve($field, 'Positive')) {
        $options[$field] = $value;
      }
    }

    switch ($format) {
      case 'json':
        $oembed = Civi::service('oembed')->create($parsedUrl['path'], $parsedUrlQuery, $options);
        return $this->send(200, ['Content-type' => 'application/json'], json_encode($oembed));

      case 'xml':
        $oembed = Civi::service('oembed')->create($parsedUrl['path'], $parsedUrlQuery, $options);
        return $this->send(200, ['Content-type' => 'text/xml'], $this->encodeXml($oembed));

      case 'share-inspect':
        $linkTags = Civi::service('oembed')->createLinkTags($parsedUrl['path'], $parsedUrlQuery ?: []);
        return $this->send(200, ['Content-type' => 'text/plain'], "If you have a real web-browser, then you want: $url\n\n\nFor oEmbed, tags are:\n$linkTags");

      case 'share':
        // An HTTP redirect is usually better. But it's hard to see how clients prioritize redirect vs oembed headers.
        CRM_Core_Region::instance('html-header')->add([
          'markup' => Civi::service('oembed')->createLinkTags($parsedUrl['path'], $parsedUrlQuery, $options),
        ]);

        CRM_Core_Region::instance('oembed-share')->add([
          'script' => sprintf("window.location.href = %s;", json_encode((string) $url)),
        ]);
        CRM_Core_Region::instance('oembed-share')->add([
          'markup' => htmlentities(ts('Redirecting...')),
        ]);
        return parent::run();

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

}
