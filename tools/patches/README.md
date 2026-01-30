# civicrm-core: Dependency patch files

> _For more general discussion about CiviCRM dependencies, see https://docs.civicrm.org/dev/en/latest/core/dependencies/_

CiviCRM dependencies may require patches to address compatibility or critical bugs. While we try to
avoid these patches, they are sometimes necessary.

The plugin `cweagans/composer-patches` provides a mechanism to apply these patches on all CiviCRM
deployments. However, the plugin has several options, and they don't always work intuitively. Here,
we describe a recommended workflow.

## Usage: Prepare patches

Focus on this folder structure:

* `./tools/patches/raw/{VENDOR}/{PACKAGE}/{PATCH_NAME}.patch` (*actual patch*)
* `./tools/patches/raw/{VENDOR}/{PACKAGE}/{PATCH_NAME}.txt` (*brief summary*)

You may update this folder by adding, modifying, renaming, or deleting patch-files. Be sure to include a brief summary for each.

After making a change in this folder, you should apply the patches locally. Run:

```bash
./tools/patches/bin/patchmgr use-local && composer install
```

If the patch needs more revision, then fix it and repeat the command (as necessary). Otherwise, let's finalize the patch.

## Usage: Finalize patches (Contributors)

> __This is for ordinary contributors who don't have direct access to publication infrastructure.__

The `composer.json` used for local-development is not good for general-usage. In particular, sites built with
D8/9/10/11 will not be able to load the local patch-files. Instead, we must specify absolute URLs.

Use `patchmgr` to switch to remote URLs:

```bash
./tools/patches/bin/patchmgr use-remote
```

And then you can save these changes.

```bash
composer update --lock
git add tools/patches/raw/
git add composer.json composer.lock
git commit
```

And submit the pull-request.

> NOTE: The pull-request will likely fail in the first test-run. This is because we haven't published the patches
> at their final URLs. You will need to ask a maintainer with additional access rights to publish the
> patch-files. (Related: "Future Improvements")

## Usage: Finalize patches (Maintainers)

> __This is for maintainers who have write-access to the CDN.__

We need to publish the patch files on the CDN and use them in `composer`.

To publish, use `patchmgr` and [`gcloud`](https://cloud.google.com/sdk/gcloud/):

```bash
./tools/patches/bin/patchmgr build
gcloud storage rsync -r tools/patches/dist gs://civicrm/patches
```

Now, you can test `composer` with the published patches:

```bash
./tools/patches/bin/patchmgr use-remote && composer install
```

If this works, then commit any changes and proceed with ordinary testing/review.

## Future Improvements

Patch-files must be published, and this currently involves some manual intervention. It would be great if
that was more automatic.

However, there are some snags to be considered:

* (A) You could auto-publish patches after the PR is merged (*which means that reviewers have signed-off*),
  But before you get to that point, you would face failing PR-tests. Catch-22. (You can't merge before
  doing QA, and you can't do QA before merging.)
* (B) You could auto-publish patches when the PR is opened or updated (*which means that the PR-tests can work*).
  But then you're publishing assets that haven't been reviewed yet. As good citizens of the Internet, we want
  _some_ kind of review.
* (C) You could tweak the test-script so that tests run with `use-local` mode. However, this can only
  pass on SA/D7/BD/WP -- it will always fail on D8/9/10/11.
* (D) You could add some interface (web UI or chat command) to formalize a multistep workflow.
    1. Contributor proposes PR.
    2. Maintainer gives tenative approval to publish the draft patch (with button on web-page or chat-command).
    3. CI runs the full test suite.
    4. Maintainer gives final approval (merge).

At time of writing, my favorite is (B)... auto-publish when the PR is opened/updated... but also include policy
constraints. For example, you might add a guard which validates that:

1. The checksum in the URL matches the actual content.
2. The patch-file is reasonably sized (like <100kb).
3. The patch-file is will-formed (textual with `+`s and `-`s in appropriate spots).
4. The patch-file applies cleanly.
5. The PR-submitter is listed in `contributor-key.yml`.

Which sounds well-and-good. (*Maybe it publishes extra drafts... but they're small/rare, so who care.*)

Alas, any good option is going to take more time to implement. And I'm not
sure how much time its worth, given that we only do this a couple per year.
