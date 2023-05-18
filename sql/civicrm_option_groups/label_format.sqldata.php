<?php
return CRM_Core_CodeGen_OptionGroup::create('label_format', 'a/0059')
  ->addMetadata([
    'title' => ts('Mailing Label Format'),
  ])
  ->addValues([
    [
      'label' => ts('Avery 3475'),
      'value' => '{"paper-size":"a4","orientation":"portrait","font-name":"dejavusans","font-size":10,"font-style":"","metric":"mm","lMargin":0,"tMargin":5,"NX":3,"NY":8,"SpaceX":0,"SpaceY":0,"width":70,"height":36,"lPadding":5.08,"tPadding":5.08}',
      'name' => 3475,
      'grouping' => 'Avery',
      'is_reserved' => 1,
    ],
    [
      'label' => ts('Avery 5160'),
      'value' => '{"paper-size":"letter","orientation":"portrait","font-name":"dejavusans","font-size":8,"font-style":"","metric":"in","lMargin":0.21975,"tMargin":0.5,"NX":3,"NY":10,"SpaceX":0.14,"SpaceY":0,"width":2.5935,"height":1,"lPadding":0.20,"tPadding":0.20}',
      'name' => 5160,
      'grouping' => 'Avery',
      'is_reserved' => 1,
    ],
    [
      'label' => ts('Avery 5161'),
      'value' => '{"paper-size":"letter","orientation":"portrait","font-name":"dejavusans","font-size":8,"font-style":"","metric":"in","lMargin":0.175,"tMargin":0.5,"NX":2,"NY":10,"SpaceX":0.15625,"SpaceY":0,"width":4,"height":1,"lPadding":0.20,"tPadding":0.20}',
      'name' => 5161,
      'grouping' => 'Avery',
      'is_reserved' => 1,
    ],
    [
      'label' => ts('Avery 5162'),
      'value' => '{"paper-size":"letter","orientation":"portrait","font-name":"dejavusans","font-size":8,"font-style":"","metric":"in","lMargin":0.1525,"tMargin":0.88,"NX":2,"NY":7,"SpaceX":0.195,"SpaceY":0,"width":4,"height":1.33,"lPadding":0.20,"tPadding":0.20}',
      'name' => 5162,
      'grouping' => 'Avery',
      'is_reserved' => 1,
    ],
    [
      'label' => ts('Avery 5163'),
      'value' => '{"paper-size":"letter","orientation":"portrait","font-name":"dejavusans","font-size":8,"font-style":"","metric":"in","lMargin":0.18,"tMargin":0.5,"NX":2,"NY":5,"SpaceX":0.14,"SpaceY":0,"width":4,"height":2,"lPadding":0.20,"tPadding":0.20}',
      'name' => 5163,
      'grouping' => 'Avery',
      'is_reserved' => 1,
    ],
    [
      'label' => ts('Avery 5164'),
      'value' => '{"paper-size":"letter","orientation":"portrait","font-name":"dejavusans","font-size":12,"font-style":"","metric":"in","lMargin":0.156,"tMargin":0.5,"NX":2,"NY":3,"SpaceX":0.1875,"SpaceY":0,"width":4,"height":3.33,"lPadding":0.20,"tPadding":0.20}',
      'name' => 5164,
      'grouping' => 'Avery',
      'is_reserved' => 1,
    ],
    [
      'label' => ts('Avery 8600'),
      'value' => '{"paper-size":"letter","orientation":"portrait","font-name":"dejavusans","font-size":8,"font-style":"","metric":"mm","lMargin":7.1,"tMargin":19,"NX":3,"NY":10,"SpaceX":9.5,"SpaceY":3.1,"width":66.6,"height":25.4,"lPadding":5.08,"tPadding":5.08}',
      'name' => 8600,
      'grouping' => 'Avery',
      'is_reserved' => 1,
    ],
    [
      'label' => ts('Avery L7160'),
      'value' => '{"paper-size":"a4","orientation":"portrait","font-name":"dejavusans","font-size":9,"font-style":"","metric":"in","lMargin":0.28,"tMargin":0.6,"NX":3,"NY":7,"SpaceX":0.1,"SpaceY":0,"width":2.5,"height":1.5,"lPadding":0.20,"tPadding":0.20}',
      'name' => 'L7160',
      'grouping' => 'Avery',
      'is_reserved' => 1,
    ],
    [
      'label' => ts('Avery L7161'),
      'value' => '{"paper-size":"a4","orientation":"portrait","font-name":"dejavusans","font-size":9,"font-style":"","metric":"in","lMargin":0.28,"tMargin":0.35,"NX":3,"NY":6,"SpaceX":0.1,"SpaceY":0,"width":2.5,"height":1.83,"lPadding":0.20,"tPadding":0.20}',
      'name' => 'L7161',
      'grouping' => 'Avery',
      'is_reserved' => 1,
    ],
    [
      'label' => ts('Avery L7162'),
      'value' => '{"paper-size":"a4","orientation":"portrait","font-name":"dejavusans","font-size":9,"font-style":"","metric":"in","lMargin":0.18,"tMargin":0.51,"NX":2,"NY":8,"SpaceX":0.1,"SpaceY":0,"width":3.9,"height":1.33,"lPadding":0.20,"tPadding":0.20}',
      'name' => 'L7162',
      'grouping' => 'Avery',
      'is_reserved' => 1,
    ],
    [
      'label' => ts('Avery L7163'),
      'value' => '{"paper-size":"a4","orientation":"portrait","font-name":"dejavusans","font-size":9,"font-style":"","metric":"in","lMargin":0.18,"tMargin":0.6,"NX":2,"NY":7,"SpaceX":0.1,"SpaceY":0,"width":3.9,"height":1.5,"lPadding":0.20,"tPadding":0.20}',
      'name' => 'L7163',
      'grouping' => 'Avery',
      'is_reserved' => 1,
    ],
  ])
  ->addDefaults([
    'filter' => NULL,
  ]);
