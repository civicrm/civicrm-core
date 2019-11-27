<?php

return [
  'html' => '<span>First</span> <span>Second</span>',
  'pretty' => "<span>First</span>\n<span>Second</span>\n",
  'shallow' => [
    ['#tag' => 'span', '#children' => [['#text' => 'First']]],
    ['#text' => ' '],
    ['#tag' => 'span', '#children' => [['#text' => 'Second']]],
  ],
  'stripped' => [
    ['#tag' => 'span', '#children' => [['#text' => 'First']]],
    ['#tag' => 'span', '#children' => [['#text' => 'Second']]],
  ],
  'deep' => [
    ['#tag' => 'span', '#children' => [['#text' => 'First']]],
    ['#text' => ' '],
    ['#tag' => 'span', '#children' => [['#text' => 'Second']]],
  ],
];
