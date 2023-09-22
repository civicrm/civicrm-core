# greenwich

The `greenwich` theme-extension is bundled into `civicrm-core`.

## Development

This extension includes compiled (S)CSS content. It is compiled automatically via `composer`.
Compiled files should *not* be committed to git. They will be generated during `composer install`.

These commands will help during development:

```bash
cd /path/to/my/composer/root

## Compile (S)CSS - one time
composer compile

## Compile (S)CSS - and continue watching for changes
composer compile:watch
```
