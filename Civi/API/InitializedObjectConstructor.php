<?php

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Civi\API;

use Doctrine\ORM\EntityManager;
use JMS\Serializer\Construction\ObjectConstructorInterface;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Naming\PropertyNamingStrategyInterface;
use JMS\Serializer\VisitorInterface;

/**
 * Object constructor that allows deserialization into already constructed
 * objects passed through the deserialization context
 */
class InitializedObjectConstructor implements ObjectConstructorInterface {
  private $entityManager;

  private $fallbackConstructor;

  private $propertyNaming;

  /**
   * Constructor.
   *
   * @param ObjectConstructorInterface $fallbackConstructor Fallback object constructor
   * @param AnnotationReader $annotationReader
   */
  public function __construct(ObjectConstructorInterface $fallbackConstructor, EntityManager $entityManager, PropertyNamingStrategyInterface $propertyNaming) {
    $this->fallbackConstructor = $fallbackConstructor;
    $this->entityManager = $entityManager;
    $this->propertyNaming = $propertyNaming;
  }

  /**
   * {@inheritdoc}
   */
  public function construct(VisitorInterface $visitor, ClassMetadata $metadata, $data, array $type, DeserializationContext $context) {
    if ($context->attributes->containsKey('target') && $context->getDepth() === 1) {
      $target = $context->attributes->get('target')->get();

      // Find @Id columns and ensure that JSON/XML doesn't define a conflicting value
      foreach ($metadata->propertyMetadata as $propertyMetadata) {
        /** @var \JMS\Serializer\Metadata\PropertyMetadata $propertyMetadata */
        // TODO Consider defining new @Civi\API\Annotation\Immutable instead of checking @Doctrine\ORM\Mapping\Id
        if ($this->entityManager->getClassMetadata($type['name'])->isIdentifier($propertyMetadata->name)) {
          $propertyName = $this->propertyNaming->translateName($propertyMetadata);
          if (isset($data[$propertyName])) {
            $oldValue = $propertyMetadata->getValue($target);
            if ($oldValue !== $data[$propertyName]) {
              throw new \CRM_Core_Exception("Attempted to modify immutable property [$propertyName]");
            }
          }
        }
      }

      return $target;
    }

    return $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
  }

}
