# ewaysingle

![Screenshot](/images/screenshot.png)

This extension is aimed at containing the original Core eWAY (Single Currency) Payment Processor Type that is legacy. See known issues below

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

* PHP v7.1+
* CiviCRM 5.31

## Installation (Web UI)

Navigate to the Extensions Page and install the extension.

## Installation (CLI)

To enable this extension in the CLI do the following

```bash
cv en ewaysingle
```

## Usage

The eWAY (Single Currency) Payment Processor Type will show up as one of the options when your adding in a PaymentProcessor.

## Known Issues

This Payment Processor does not do any kind of recurring payments at all for that you would need another extension e.g. [Agileware Eway Recurring](https://github.com/agileware/au.com.agileware.ewayrecurring)
