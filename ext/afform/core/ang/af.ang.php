<?php
// This file declares an Angular module which can be autoloaded
return [
  'js' => [
    'ang/af.js',
    'ang/af/*.js',
    'ang/af/*/*.js',
  ],
  // 'css' => ['ang/af.css'],
  'partials' => ['ang/af'],
  'requires' => ['crmUtil'],
  'basePages' => [],
  'exports' => [
    'af-entity' => 'E',
    'af-fieldset' => 'A',
    'af-form' => 'E',
    'af-join' => 'A',
    'af-repeat' => 'A',
    'af-repeat-item' => 'A',
    'af-field' => 'E',
  ],
];
