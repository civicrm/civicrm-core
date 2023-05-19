<?php

// Encounter Medium Option Values (for case activities)
return CRM_Core_CodeGen_OptionGroup::create('encounter_medium', 'b/97')
  ->addMetadata([
    // FIXME: Shouldn't these be translated?
    'title' => 'Encounter Medium',
    'description' => 'Encounter medium for case activities (e.g. In Person, By Phone, etc.)',
  ])
  ->addValueTable(['label', 'name'], [
    [ts('In Person'), 'in_person'],
    [ts('Phone'), 'phone', 'is_default' => 1],
    [ts('Email'), 'email'],
    [ts('Fax'), 'fax'],
    [ts('Letter Mail'), 'letter_mail'],
  ])
  ->addDefaults([
    'is_reserved' => 1,
  ]);
