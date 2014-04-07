<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Civi\API;

use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;


/**
 * Converts between objects with getter and setter methods and arrays.
 *
 * The normalization process looks at all public methods and calls the ones
 * which have a name starting with get and take no parameters. The result is a
 * map from property names (method name stripped of the get prefix and converted
 * to lower case) to property values. Property values are normalized through the
 * serializer.
 *
 * The denormalization first looks at the constructor of the given class to see
 * if any of the parameters have the same name as one of the properties. The
 * constructor is then called with all parameters or an exception is thrown if
 * any required parameters were not present as properties. Then the denormalizer
 * walks through the given map of property names to property values to see if a
 * setter method exists for any of the properties. If a setter exists it is
 * called with the property value. No automatic denormalization of the value
 * takes place.
 *
 * NOTE: This is a forked version which adds support for updating $context['target']
 * instead of instantiating new objects.
 *
 * @author Nils Adermann <naderman@naderman.de>
 */
class GetSetMethodNormalizer extends SerializerAwareNormalizer implements NormalizerInterface, DenormalizerInterface
{
  protected $callbacks = array();
  protected $ignoredAttributes = array();
  protected $camelizedAttributes = array();

  /**
   * Set normalization callbacks
   *
   * @param array $callbacks help normalize the result
   *
   * @throws InvalidArgumentException if a non-callable callback is set
   */
  public function setCallbacks(array $callbacks)
  {
    foreach ($callbacks as $attribute => $callback) {
      if (!is_callable($callback)) {
        throw new InvalidArgumentException(sprintf('The given callback for attribute "%s" is not callable.', $attribute));
      }
    }
    $this->callbacks = $callbacks;
  }

  /**
   * Set ignored attributes for normalization
   *
   * @param array $ignoredAttributes
   */
  public function setIgnoredAttributes(array $ignoredAttributes)
  {
    $this->ignoredAttributes = $ignoredAttributes;
  }

  /**
   * Set attributes to be camelized on denormalize
   *
   * @param array $camelizedAttributes
   */
  public function setCamelizedAttributes(array $camelizedAttributes)
  {
    $this->camelizedAttributes = $camelizedAttributes;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = array())
  {
    $reflectionObject = new \ReflectionObject($object);
    $reflectionMethods = $reflectionObject->getMethods(\ReflectionMethod::IS_PUBLIC);

    $attributes = array();
    foreach ($reflectionMethods as $method) {
      if ($this->isGetMethod($method)) {
        $attributeName = lcfirst(substr($method->name, 3));

        if (in_array($attributeName, $this->ignoredAttributes)) {
          continue;
        }

        $attributeValue = $method->invoke($object);
        if (array_key_exists($attributeName, $this->callbacks)) {
          $attributeValue = call_user_func($this->callbacks[$attributeName], $attributeValue);
        }
        if (NULL !== $attributeValue && !is_scalar($attributeValue)) {
          $attributeValue = $this->serializer->normalize($attributeValue, $format);
        }

        $attributes[$attributeName] = $attributeValue;
      }
    }

    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array())
  {
    list($data, $object) = $this->createInstance($class, $data, $context);

    foreach ($data as $attribute => $value) {
      $setter = 'set'.$this->formatAttribute($attribute);

      if (method_exists($object, $setter)) {
        $object->$setter($value);
      }
    }

    return $object;
  }

  public function createInstance($class, $data, $context = array()) {
    if (isset($context['target'])) {
      return array($data, $context['target']);
    }

    $reflectionClass = new \ReflectionClass($class);
    $constructor = $reflectionClass->getConstructor();

    if ($constructor) {
      $constructorParameters = $constructor->getParameters();

      $params = array();
      foreach ($constructorParameters as $constructorParameter) {
        $paramName = lcfirst($this->formatAttribute($constructorParameter->name));

        if (isset($data[$paramName])) {
          $params[] = $data[$paramName];
          // don't run set for a parameter passed to the constructor
          unset($data[$paramName]);
        }
        elseif (!$constructorParameter->isOptional()) {
          throw new RuntimeException(
            'Cannot create an instance of ' . $class .
              ' from serialized data because its constructor requires ' .
              'parameter "' . $constructorParameter->name .
              '" to be present.');
        }
      }

      $object = $reflectionClass->newInstanceArgs($params);
      return array($data, $object);
    }
    else {
      $object = new $class;
      return array($data, $object);
    }
  }

  /**
   * Format attribute name to access parameters or methods
   * As option, if attribute name is found on camelizedAttributes array
   * returns attribute name in camelcase format
   *
   * @param string $attributeName
   * @return string
   */
  protected function formatAttribute($attributeName)
  {
    if (in_array($attributeName, $this->camelizedAttributes)) {
      return preg_replace_callback(
        '/(^|_|\.)+(.)/', function ($match) {
          return ('.' === $match[1] ? '_' : '').strtoupper($match[2]);
        }, $attributeName
      );
    }

    return $attributeName;
  }

  /**
   * {@inheritDoc}
   */
  public function supportsNormalization($data, $format = NULL)
  {
    return is_object($data) && $this->supports(get_class($data));
  }

  /**
   * {@inheritDoc}
   */
  public function supportsDenormalization($data, $type, $format = NULL)
  {
    return $this->supports($type);
  }

  /**
   * Checks if the given class has any get{Property} method.
   *
   * @param string $class
   *
   * @return Boolean
   */
  private function supports($class)
  {
    $class = new \ReflectionClass($class);
    $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
    foreach ($methods as $method) {
      if ($this->isGetMethod($method)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Checks if a method's name is get.* and can be called without parameters.
   *
   * @param \ReflectionMethod $method the method to check
   *
   * @return Boolean whether the method is a getter.
   */
  private function isGetMethod(\ReflectionMethod $method)
  {
    return (
      0 === strpos($method->name, 'get') &&
        3 < strlen($method->name) &&
        0 === $method->getNumberOfRequiredParameters()
    );
  }
}
