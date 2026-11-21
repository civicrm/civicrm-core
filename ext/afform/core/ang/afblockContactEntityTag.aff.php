<?php
// Create-oriented block. Afform cannot yet prefill a multi-value join on an
// edit form: AbstractProcessor::loadEntity() loads a single row for a
// non-repeating join and there is no aggregation of several join rows into one
// multi-select value, so placing this on an update form will silently drop the
// contact's other tags on submit. Full edit-form support is the same ground as
// the upstream design discussion https://github.com/civicrm/civicrm-core/pull/33961.
//
// The tag_id dropdown is not filtered by `used_for`: EntityTag.tag_id's
// pseudoconstant conditions only on is_tagset, and the block renders tag_id with
// no entity_table sibling for the metadata injector to scope on, so every
// non-tagset tag is offered. A tag whose used_for excludes civicrm_contact still
// saves a valid row but will not show on the contact's own tag UI (which filters
// by used_for), so this block is intended for sites whose contact tags are the
// only ones in play; per-used_for filtering needs the same entity_table context
// the edit-form work above would add.
return [
  'title' => ts('Contact Tag(s)'),
  'type' => 'block',
  'entity_type' => 'Contact',
  'join_entity' => 'EntityTag',
];
