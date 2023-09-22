# eventcart

This extracts most of the event cart functionality into an extension.

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Remaining work

1. Move CRM_Event_Cart_DAO_Cart and CRM_Event_Cart_DAO_EventInCart from CiviCRM core (see https://github.com/civicrm/civicrm-core/pull/17339 for details).
1. There are various places in CiviCRM which still check the setting `enable_cart`. These should be moved to this extension.
1. The "Conference Slots" functionality is only enabled if Event Cart is enabled so that should be moved into this extension too.
