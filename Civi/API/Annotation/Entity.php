<?php
namespace Civi\API\Annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class Entity {
  /**
   * @var string Symbolic name of the entity
   */
  public $name;

  /**
   * @var string URL-safe variant of the entity name
   */
  public $slug;

  public function __construct(array $values) {
    if (isset($values['value'])) {
      $this->name = $values['value'];
    }
    if (isset($values['name'])) {
      $this->name = $values['name'];
    }
    if (isset($values['slug'])) {
      $this->slug = $values['slug'];
    } else {
      $this->slug = $this->sluggify($this->name);
    }
  }

  public function sluggify($name) {
    // TODO use proper URL conventions ("/foo-bar")
    return strtolower($name{0}) . substr($name, 1);
  }
}