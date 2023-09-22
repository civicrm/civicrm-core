# Payflow Pro

This extension provides an integration with PayPal's Payflow Pro service. It supports one time and recurring payments from CiviCRM.

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

* PHP v7.2+
* CiviCRM 5.38

## Installation (Web UI)

Learn more about installing CiviCRM extensions in the [CiviCRM Sysadmin Guide](https://docs.civicrm.org/sysadmin/en/latest/customize/extensions/).

## Getting Started

After enabling the extension you will need to go to Administer >> System Settings >> Payment Processors and create a Payment Processor that uses Payflow Pro

## Known Issues

At Present the extension can create recurring contributions processes in the Payment Processor but does not use the APIs provided to check that subsequent transactions have been processed.
