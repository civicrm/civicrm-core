<?php
return CRM_Core_CodeGen_OptionGroup::create('name_badge', 'a/0073')
  ->addMetadata([
    'title' => ts('Name Badge Format'),
  ])
  ->addValues([
    [
      'label' => ts('Avery 5395'),
      'value' => '{"name":"Avery 5395","paper-size":"a4","metric":"mm","lMargin":15,"tMargin":26,"NX":2,"NY":4,"SpaceX":10,"SpaceY":5,"width":83,"height":57,"font-size":12,"orientation":"portrait","font-name":"helvetica","font-style":"","lPadding":3,"tPadding":3}',
      'name' => 'Avery 5395',
    ],
    [
      'label' => ts('A6 Badge Portrait 150x106'),
      'value' => '{"paper-size":"a4","orientation":"landscape","font-name":"times","font-size":6,"font-style":"","NX":2,"NY":1,"metric":"mm","lMargin":25,"tMargin":27,"SpaceX":0,"SpaceY":35,"width":106,"height":150,"lPadding":5,"tPadding":5}',
      'name' => 'A6 Badge Portrait 150x106',
    ],
    [
      'label' => ts('Fattorini Name Badge 100x65'),
      'value' => '{"paper-size":"a4","orientation":"portrait","font-name":"times","font-size":6,"font-style":"","NX":2,"NY":4,"metric":"mm","lMargin":6,"tMargin":19,"SpaceX":0,"SpaceY":0,"width":100,"height":65,"lPadding":0,"tPadding":0}',
      'name' => 'Fattorini Name Badge 100x65',
    ],
    [
      'label' => ts('Hanging Badge 3-3/4" x 4-3"/4'),
      'value' => '{"paper-size":"a4","orientation":"portrait","font-name":"times","font-size":6,"font-style":"","NX":2,"NY":2,"metric":"mm","lMargin":10,"tMargin":28,"SpaceX":0,"SpaceY":0,"width":96,"height":121,"lPadding":5,"tPadding":5}',
      'name' => 'Hanging Badge 3-3/4" x 4-3"/4',
    ],
  ]);
