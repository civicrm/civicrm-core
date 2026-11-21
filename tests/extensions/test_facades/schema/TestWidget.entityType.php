<?php
use CRM_TestFacades_ExtensionUtil as E;

return [
  'name' => 'TestWidget',
  'facade_of' => 'TestThing',
  'where' => ['type' => 'widget'],
  'getInfo' => fn() => [
    'title' => E::ts('Test Widget'),
    'title_plural' => E::ts('Test Widgets'),
    'description' => E::ts('Widgets (facade of TestThing)'),
    'icon' => 'fa-cog',
    'label_field' => 'name',
    'searchable' => 'primary',
  ],
];
