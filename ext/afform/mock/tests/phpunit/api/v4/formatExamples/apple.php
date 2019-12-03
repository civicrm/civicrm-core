<?php

return [
  'html' => '<strong>New &nbsp; text!</strong>',
  'pretty' => "<strong>New &nbsp; text!</strong>\n",
  'shallow' => [
    ['#tag' => 'strong', '#markup' => 'New &nbsp; text!'],
  ],
  'deep' => [
    ['#tag' => 'strong', '#children' => [['#text' => 'New &nbsp; text!']]],
  ],
];
