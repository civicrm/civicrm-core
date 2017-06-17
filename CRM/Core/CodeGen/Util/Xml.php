<?php

/**
 * Class CRM_Core_CodeGen_Util_Xml
 */
class CRM_Core_CodeGen_Util_Xml {
  /**
   * @param string $file
   *   Path to input.
   *
   * @return SimpleXMLElement|bool
   */
  public static function parse($file) {
    $dom = new DomDocument();
    $xmlString = file_get_contents($file);
    $dom->loadXML($xmlString);
    $dom->documentURI = $file;
    $dom->xinclude();
    $xml = simplexml_import_dom($dom);
    return $xml;
  }

}
