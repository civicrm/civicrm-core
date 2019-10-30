<?php

return [
  'html' => '<span>First</span> <span>Second</span>',
  'shallow' => [
    ['#tag' => 'span', '#children' => [['#text' => 'First']]],
    ['#text' => ' '],
    ['#tag' => 'span', '#children' => [['#text' => 'Second']]],
  ],
  'deep' => [
    ['#tag' => 'span', '#children' => [['#text' => 'First']]],
    ['#text' => ' '],
    ['#tag' => 'span', '#children' => [['#text' => 'Second']]],
  ],
];
