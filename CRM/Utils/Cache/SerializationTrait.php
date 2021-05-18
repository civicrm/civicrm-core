<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Class CRM_Utils_Cache_SerializationTrait
 */
trait CRM_Utils_Cache_SerializationTrait {

  /**
   * @var callable|null
   */
  private $serializer = NULL;

  /**
   * @var callable|null
   */
  private $unserializer = NULL;

  /**
   * @param callable|null $encode
   * @param callable|null $decode
   */
  public function useSerializer($encode, $decode) {
    $this->serializer = $encode;
    $this->unserializer = $decode;
  }

  /**
   * @param mixed $data
   * @return string
   * @see \serialize()
   */
  protected function serialize($data) {
    if ($this->serializer === NULL) {
      return serialize($data);
    }
    else {
      $f = $this->serializer;
      return $f($data);
    }
  }

  /**
   * @param string $data
   * @return mixed
   * @see \unserialize()
   */
  protected function unserialize($data) {
    if ($this->serializer === NULL) {
      return unserialize($data);
    }
    else {
      $f = $this->unserializer;
      return $f($data);
    }
  }

}
