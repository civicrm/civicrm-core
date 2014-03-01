<?php
use Doctrine\ORM\EntityManager;

class CRM_DB_EntityManager {
  /**
   * @return Doctrine\ORM\EntityManager
   */
  static function singleton() {
    return \Civi\Core\Container::singleton()->get('entity_manager');
  }
}