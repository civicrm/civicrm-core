<?php

namespace Civi\Afform;

/**
 * An AfformBehavior is a collection of configuration and functionality for an entity on a form.
 *
 * Note: Rather than implementing this interface directly, extend the AbstractBehavior class.
 */
interface BehaviorInterface {

  /**
   * Return list of supported Afform entities (e.g. Individual, Household...)
   *
   * @return array
   */
  public static function getEntities(): array;

  /**
   * Title of the behavior
   *
   * @return string
   */
  public static function getTitle(): string;

  /**
   * Optional description of the behavior
   *
   * @return string|null
   */
  public static function getDescription():? string;

  /**
   * Optional template for configuring the behavior in the AfformGuiEditor
   *
   * @return string|null
   */
  public static function getTemplate(): ?string;

  /**
   * Dashed name, name of entity attribute for selected mode
   * @return string
   */
  public static function getKey(): string;

  /**
   * Array of attributes added to the entity by this behavior.
   *
   * Array is keyed by attribute name, with a value of an ArrayHtml data type, e.g.
   * ```
   * [
   *   'my-mode' => 'text',
   *   'my-config-data' => 'js',
   * ]
   * ```
   * @return array
   */
  public static function getAttributes(): array;

  /**
   * Get array of modes for a given entity
   *
   * The mode determines whether this behavior is enabled and what it should do
   *
   * @param string $entityName
   *   Only matters if this behavior supports multiple entities and if the modes are different
   * @return array
   */
  public static function getModes(string $entityName): array;

  /**
   * Default mode. If set then mode will not be de-selectable.
   *
   * @return string|null
   */
  public static function getDefaultMode(): ?string;

}
