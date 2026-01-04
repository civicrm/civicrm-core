# legacydedupefinder
Legacy dedupe finder is intended to provide backwards compatibility for sites who have
customised dedupe finding using the deprecated
`CRM_Utils_Hook::dupeQuery($this, 'table', $tableQueries);`

or by extending
`CRM_Dedupe_BAO_QueryBuilder`

These 2 customisation methods have inhibited improving the dedupe speed and hence
this extension allows the core code to be improved and sped up while providing
and off-ramp for customisers.

This is an [extension for CiviCRM](https://docs.civicrm.org/sysadmin/en/latest/customize/extensions/), licensed under [AGPL-3.0](LICENSE.txt).

## Getting Started

This extension should only be installed if you know you have a customisation that needs it.
In most cases uninstalling it will improve deduping.

## Known Issues
