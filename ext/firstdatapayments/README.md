# firstdatapayments

This code to processor firstData payments used to be in CiviCRM core. It is now in
an extension with the goal of moving it completely out of core at some point in
the future.

It is kept only to support instances that were already using the processor (if any)
and as such does not add the entries to the civicrm_processor_type table
that are required to do new installs. It is not visible to install or uninstall.

In the latter case the reason being that it should only be installed on sites
with connected transactions and it's unclear what removing the processor would do.
