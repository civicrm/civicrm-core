<?php
use CRM_TestFacades_ExtensionUtil as E;

return [
  'name' => 'TestGizmo',
  'facade_of' => 'TestThing',
  'where' => ['type' => 'gizmo'],
  'getInfo' => fn() => [
    'title' => E::ts('Test Gizmo'),
    'title_plural' => E::ts('Test Gizmos'),
    'description' => E::ts('Gizmos (facade of TestThing)'),
  ],
  // Gizmos have all of TestThing's fields except description
  'alterFields' => fn(array $fields) => ['description' => FALSE] + $fields,
];
