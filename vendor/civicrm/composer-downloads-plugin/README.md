Composer Downloads Plugin
===========================

> This is a fork of [lastcall/composer-extra-files](https://github.com/LastCallMedia/ComposerExtraFiles/). Some of the
> configuration options have changed, so it has been renamed to prevent it from conflicting in real-world usage.

This `composer` plugin allows you to download extra files (`*.zip` or `*.tar.gz`) and extract them within your package.

For example, suppose you publish a PHP package `foo/bar` which relies on an external artifact (such as JS, CSS, or image library). Place this configuration in the `composer.json` for `foo/bar`:

```json
{
  "name": "foo/bar",
  "require": {
    "civicrm/composer-downloads-plugin": "~1.0"
  },
  "extra": {
    "downloads": {
      "examplelib": {
        "url": "`https://example.com/examplelib-0.1.zip`",
        "path": "extern/examplelib",
        "ignore": ["test", "doc", ".*"]
      }
    }
  }
}
```

When a downstream user of `foo/bar` runs `composer install`, it will fetch and extract the zip file, creating `vendor/foo/bar/extern/examplelib`. 

This does not require consumers of `foo/bar` to make any special changes in their root-level project, and it uses `composer`'s built-in cache system.

## When should I use this?

The most common use-case is if you have compiled front-end code, where the compiled version is never committed to a git repository, and therefore isn't registered on packagist.org.  For example, if you want your distributed package to depend on an NPM/Bower package.

If you have the ability to maintain the root `composer.json` of consumers, then consider these alternatives -- when using multiple NPM/Bower packages, they provide more robust functionality (such as automatic updates and version-constraints).

* [Asset Packagist](https://asset-packagist.org/)
* [Composer Asset Plugin](https://github.com/fxpio/composer-asset-plugin)

The `downloads` approach is most appropriate if (a) you publish an intermediate (non-root) project to diverse consumers and (b) the external assets are relatively stable.

## Configuration: Properties

The `downloads` contains a list of files to download. Each extra-file as a symbolic ID (e.g. `examplelib` above) and some mix of properties:

* `url`: The URL to fetch the content from.  If it points to a tarball or zip file, it will be unpacked automatically.

* `path`: The releative path where content will be extracted.

* `type`: Determines how the download is handled
    * `archive`: The `url` references a zip or tarball which should be extracted at the given `path`. (Default for URLs involving `*.zip`, `*.tar.gz`, or `*.tgz`.)
    * `file`: The `url` should be downloaded to the given `path`.
    * `phar`: The `url` references a PHP executable which should be installed at the given `path`.

* `ignore`: A list of a files that should be omited from the extracted folder. (This supports a subset of `.gitignore` notation.)

## Configuration: Defaults

You may set default values for downloaded files using the `*` entry.

```json
{
  "extra": {
    "downloads": {
      "*": {
        "path": "bower_components/{$id}",
        "ignore": ["test", "tests", "doc", "docs"]
      },
      "jquery": {
        "url": "https://github.com/jquery/jquery-dist/archive/1.12.4.zip"
      },
      "jquery-ui": {
        "url": "https://github.com/components/jqueryui/archive/1.12.1.zip"
      }
    }
  }
}
```

## Tips

In each downloaded folder, this plugin will create a small metadata file (`.composer-downloads.json`) to track the origin of the current code. If you modify the `composer.json` to use a different URL, then it will re-download the file.

Download each extra file to a distinct `path`. Don't try to download into overlapping paths. (*This has not been tested, but it may lead to extraneous deletions/re-downloads.*)

What should you do if you *normally* download the extra-file as `*.tgz` but sometimes (for local dev) need to grab bleeding edge content from somewhere else?  Simply delete the autodownloaded folder and replace it with your own.  `composer-downloads` will detect that conflict (by virtue of the absent `.composer-downloads.json`) and leave your code in place (until you choose to get rid of it). To switch back, you can simply delete the code and run `composer install` again.

## Known Limitations

If you use `downloads` in a root-project (or in symlinked dev repo), it will create+update downloads, but it will not remove orphaned items automatically.  This could be addressed by doing a file-scan for `.composer-downloads.json` (and deleting any orphan folders).  Since the edge-case is not particularly common right now, and since a file-scan could be time-consuming, it might make sense as a separate subcommand.

I believe the limitation does *not* affect downstream consumers of a dependency. In that case, the regular `composer` install/update/removal mechanics should take care of any nested downloads. However, this is a little tricky to test right now.
