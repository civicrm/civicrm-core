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
    $oldValue = libxml_disable_entity_loader(FALSE);
    $dom = new DomDocument();
    $dom->load($file);
    libxml_disable_entity_loader($oldValue);
    $dom->xinclude();
    $xml = simplexml_import_dom($dom);
    return $xml;
  }

}
