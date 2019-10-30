<?php

return [
  'html' => '<strong>New text!</strong>',
  'shallow' => [
    ['#tag' => 'strong', '#children' => [['#text' => 'New text!']]],
  ],
  'deep' => [
    ['#tag' => 'strong', '#children' => [['#text' => 'New text!']]],
  ],
];
