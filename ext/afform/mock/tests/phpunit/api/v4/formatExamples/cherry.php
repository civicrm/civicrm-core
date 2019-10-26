<?php

return [
  'html' => '<span>First</span> <span>Second</span>',
  'shallow' => [
    ['#tag' => 'span', '#children' => ['First']],
    ' ',
    ['#tag' => 'span', '#children' => ['Second']],
  ],
  'deep' => [
    ['#tag' => 'span', '#children' => ['First']],
    ' ',
    ['#tag' => 'span', '#children' => ['Second']],
  ],
];
