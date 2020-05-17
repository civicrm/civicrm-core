# eventcart

This extracts most of the event cart functionality into an extension.

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Remaining work

1. There are various places in CiviCRM which still check the setting `enable_cart`. These should be moved to this extension.
1. The "Conference Slots" functionality is only enabled if Event Cart is enabled so that should be moved into this extension too.
