<?php

return [
  'html' => '<span>First</span>   <span>Second</span>',
  'pretty' => "<span>First</span>
<span>Second</span>\n",
  'stripped' => [
    ['#tag' => 'span', '#markup' => 'First'],
    ['#tag' => 'span', '#markup' => 'Second'],
  ],
  'shallow' => [
    ['#tag' => 'span', '#markup' => 'First'],
    ['#text' => '   '],
    ['#tag' => 'span', '#markup' => 'Second'],
  ],
  'deep' => [
    ['#tag' => 'span', '#children' => [['#text' => 'First']]],
    ['#text' => '   '],
    ['#tag' => 'span', '#children' => [['#text' => 'Second']]],
  ],
];
