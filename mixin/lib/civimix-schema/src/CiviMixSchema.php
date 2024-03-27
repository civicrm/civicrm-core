<?php
namespace CiviMix\Schema;

/**
 * This object is known as $GLOBALS['CiviMixSchema']. It is a reloadable service-object.
 * (It may be reloaded if you enable a new extension that includes an upgraded copy.)
 */
return new class() {

  /**
   * @param string $extensionKey
   *   Ex: 'org.civicrm.flexmailer'
   * @return \CiviMix\Schema\SchemaHelperInterface
   */
  public function getHelper(string $extensionKey) {
    throw new \RuntimeException("Not implemented");
    return NULL;
  }

};
