<?php
namespace Civi\API;

/**
 * A registry of API-enabled Doctrine entities
 */
class Registry {
  /**
   * @var array (string $name => string $className)
   */
  private $names;

  /**
   * @var array (string $slug => string $className)
   */
  private $slugs;

  /**
   * @param \Doctrine\ORM\Configuration $config
   * @param \Doctrine\Common\Annotations\Reader $annotationReader
   */
  public function __construct($config, $annotationReader) {
    if ($config->getMetadataCacheImpl()->contains('Civi\API\Registry#names')) {
      $this->names = $config->getMetadataCacheImpl()->fetch('Civi\API\Registry#names');
    }
    if ($config->getMetadataCacheImpl()->contains('Civi\API\Registry#slugs')) {
      $this->slugs = $config->getMetadataCacheImpl()->fetch('Civi\API\Registry#slugs');
    }
    if ($this->names === NULL || $this->slugs === NULL) {
      $this->names = array();
      $this->slugs = array();
      $this->scan($annotationReader, $config->getMetadataDriverImpl()->getAllClassNames());
      $config->getMetadataCacheImpl()->save('Civi\API\Registry#names', $this->names);
      $config->getMetadataCacheImpl()->save('Civi\API\Registry#slugs', $this->slugs);
    }
  }

  /**
   * Scan classes for API annotations; store them in the index.
   *
   * If the class does not declare the CiviAPI\Entity annotation, then the name will be
   * based on the last part of the class name.
   *
   * @param \Doctrine\Common\Annotations\Reader $annotationReader
   * @param array $classes class names
   */
  public function scan($annotationReader, $classes) {
    foreach ($classes as $class) {
      /** @var \Civi\API\Annotation\Entity $anno */
      $anno = $annotationReader->getClassAnnotation(new \ReflectionClass($class), 'Civi\API\Annotation\Entity');
      if (!$anno) {
        $parts = explode('\\', $class);
        $basename = array_pop($parts);
        $anno = new \Civi\API\Annotation\Entity(array(
          'value' => $basename,
        ));
      }
      // TODO generate warning if two classes have the same entity name
      $this->names[$anno->name] = $class;
      $this->slugs[$anno->slug] = $class;
    }
  }

  /**
   * Look up the name of an entity class based on the entity name.
   *
   * @param string $entityName
   * @return string|NULL class name
   */
  public function getClassByName($entityName) {
    return isset($this->names[$entityName]) ? $this->names[$entityName] : NULL;
  }

  /**
   * Look up the name of an entity class based on the sluggified entity name.
   *
   * @param string $slug
   * @return string|NULL class name
   */
  public function getClassBySlug($slug) {
    return isset($this->slugs[$slug]) ? $this->slugs[$slug] : NULL;
  }

  /**
   * Look up the slug based on the entity name.
   *
   * @param string $entityName
   * @return string|NULL slug
   */
  public function getSlugByName($entityName) {
    $class = $this->getClassByName($entityName);
    return ($class === NULL) ? NULL : $this->getSlugByClass($class);
  }

  /**
   * Look up the slug based on the class name.
   *
   * @param string $class
   * @return string|NULL
   */
  public function getSlugByClass($class) {
    $pos = array_search($class, $this->slugs);
    return $pos === FALSE ? NULL : $pos;
  }
}