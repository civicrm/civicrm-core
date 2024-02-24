# oEmbed Provider (developmental)

This extension aims to make CiviCRM into a provider of [oEmbed](https://oembed.com/) content.  As discussed in [dev/core#2994](https://lab.civicrm.org/dev/core/-/issues/2994), this
includes a range of external-facing pages -- such as ContributionPages and EventRegistrationPages.  At time of writing, this README is a living document -- it should be updated as
techniques and implementations develop.

## Installation

* Checkout this branch
* Enable the extension `oembed`
* Install the entry-point script. There are three ways to do this:
    * __CLI/API__: Run `cv api oembed.installscript`
    * __Web UI__: Open the "System Status". If you have permission, it will show a button "Deploy now"
    * __Manual__: Open the "System Status". It will show a button "Deploy instructions". Copy-paste the content into the target file.
* Now you can open a page like `http://example.com/oembed.php/civicrm/contribute/transact?reset=1&id=1`. You can embed this on remote IFRAMEs.

## Overview

The extension will include the basic oEmbed protocol, but first it must address the lower-level mechanisms for embedding CiviCRM content on remote sites.  Specifically, the IFRAME
mechanism generally works with oEmbed and generally works with a range of client+server technologies -- so we use that.  The approach supports core CiviCRM technologies (such as
UFs/Extensions/Hooks/CRUD APIs -- as well as QuickForm/jQuery and Angular/Afform/SearchKit).  However, embedding is not trivial.  It differs from existing pages in several ways:

* Authorization headers
* Page-flow and state
* Styling and sizing

On the whole, embeds represent a distinctive runtime context.  (For comparison, many web platforms address 3+ runtime contexts: "public frontend", "staff backend", "web services".  Each
context has a different set of rules regarding headers, pageflow/state, styling, URLs, etc.  The "embedded frontend" is similar in scope.) Specifically, the extension represents this as
a distinctive entry-point. Compare:

```
Site URL:             https://example.org/
Example Page:         https://example.org/civicrm/contribute/transact?reset=1&id=1

IFRAME Entry-Point:   https://example.org/oembed.php
IFRAME Example Page:  https://example.org/oembed.php/civicrm/contribute/transact?reset=1&id=1
```

## In depth: Developing the entry-point

* The entry-point needs to be deployed by oembed-providers. It does not need to be deployed by oembed-consumers.

* The top-level `/oembed.php` script is based on a template.  There are multiple template files, stored in `Civi/Oembed/EntryPoint/`.  They
  are currently named after the UFs. This could change if need-be.

* Whenever you edit the file in `Civi/Oembed/EntryPoint/`, the status-check will complain that `/oembed.php` is out-of-date and prompt you to re-deploy it.

* The D10 template needs some helper classes. These are in `lib/`.

* Does this absolutely need to be a separate PHP file in the web-root?  Maybe not; it depends.
    * It has some benefits.  It can use the regular UF bootstrap methods.  The URL is easy to predict and interpret.  It gets high privileges in manipulating the app (e.g.  to change session options).
    * Alas, it does require an extra installation step, and some mass-market/vertically-integrated web-hosts may not allow the installation step.  So rewriting another way might be nice.
    * Suggestion for anyone wanting to go that way:
        1. Wait until we've hit some more milestones (*like: abstracting the embed-route-generator -- so you can layer-in whatever gnarly URLs*)
        2. Read the existing templates for inspiration on the kind of steps needed for each UF. Some UF's may provide sufficient runtime APIs; others may not.

## In depth: Page-flow and state

TODO

## Issues / TODOs

* [x] Layout
  * [x] Setting `oembed_layout` (`cms`, `basic`, `raw`)
  * [x] Implement basic layout (like current `raw` but with `<HEAD>` and `<BODY>`)
  * [x] Setting `oembed_theme` similar to `frontend_theme`, `backend_theme` (for `basic` layout)
* [x] Settings:
  * [x] Setting: Allow pages (`oembed_allow`; multiselect: "All Frontend", "All AJAX")
  * [x] Setting: Allow pages (`oembed_allow_other`; textarea; other)
  * [x] Customize entry-point path + URL (via $civicrm_paths)
* [x] Fix JS/CSS loading -- rework URL schemes and routing
  * [x] Internal API for non-UF URL schemes -- `Civi::url('oembed://foo/bar')`
  * [x] Drop `CIVICRM_UF_BASEURL` hack.
  * [x] Generate proper postURL
  * [x] Debug other JS/CSS loading issue
  * [x] Alter all internal redirects
* [ ] March 5.72-RC Target
    * [ ] Rename existing stuff to `ext/iframe`
    * [ ] Add the real `ext/oembed` (https://oembed.com/#section4)
        * [ ] End-point for `civicrm/share/` which outputs an oEmbed stub
    * [ ] IFRAME sizing: setting
        * Setting for default (maybe width=480? width=720? https://mediag.com/blog/popular-screen-resolutions-designing-for-all/)
* [ ] March 5.72-RC Addendum Extension
    * [ ] JS or CSS to hide elements that we don't want to deal with yet ("Tell a Friend", "Create PCP")
* [ ] March 5.72-RC Testing
    * Use case retest/targetting/reassessment (*works better after fixing layout+URL issues above; still need to figure out inline HREFs targetting*)
        * [ ] contribution page
        * [ ] event page
        * [ ] jma petition
        * et al
* [ ] Tidy up (March-ish/April-ish)
    * [ ] Document: Page-flow and state (JWTs, cookies)
    * [ ] Make the "Deploy instructions" prettier. Add more help. Mention `$civicrm_paths`
    * [ ] Doc: info.xml FIXMEs
    * [ ] E2E tests for cookie/session behavior of `oembed.php`
    * [ ] E2E tests for rendering
    * [ ] E2E tests for settings `oembed_allow`, `oembed_allow_other`
    * [x] Consider stricter X-Frame-Options/Content-Security-Policy
* [ ] Important but not ASAP
    * [ ] Stricter JWT: https://lab.civicrm.org/dev/core/-/issues/2994#note_156045
        * If referrer doesn't match our iframe setup, then reset or reject the session id; probably includes test
    * [ ] Add entry-point for UF=Standalone (boot; invoke; X-Frame-Options; etc)
    * [ ] Add entry-point for UF=WordPress (script-file)
    * [ ] Add entry-point for UF=WordPress (wp-rest)
    * [ ] Add entry-point for UF=Backdrop
* [ ] Wishlist (Budget guided)
    * [ ] Share Workflow: Public Links (templates/CRM/common/SocialNetwork.tpl; crmRegion or hook)
    * [ ] Share Workflow: Admin Links (Contribution Page, Event Page, Petition?)
    * [ ] Share Workflow: Page Default/Singular/Standard/Built-in
    * [ ] Share Workflow: Afform Share Widget
    * [ ] IFRAME sizing: customizable (`civicrm/share?size=NNNxNNN`)
    * [ ] Session-propagation targetting
      On the last page, there are hyperlinks like "Tell Friend" and "Create PCP" and "Back to CiviCRM Home Page".
      URL's generated by BAO (wchich calls to CRM_Utils_System::url()). Question is how to make a selector to change these?
      - Tweak each call to url()? Maybe `Civi::url('civicrm/contribute/campaign...')->setSession(TRUE)
      - Add some JS that filters all hyperlinks within a certain area? (`CRM.$('thankyoupage a).addSessionId('xyz')`)
      - Add some alterContent to do the same as above? Or add a filter to the regeion?
      - Define an attr for `<A HREF= SESSION=1`> or `<A HREF= CLASS="crm-session">`. But interpret client-side or server-side?
* [ ] Wishlist (Long term)
    * [ ] IFRAME sizing: dynamic
    * [ ] Add entry-point for UF=Joomla

## Comments

* Earlier drafts needed you to manually authorize IFRAME access. (Ex: For D7, setup `$conf['x_frame_options']`. For D10, setup `x_frame_options_configuration`).
  This was too manual and too broad. Now `oembed.php` has this built-in. It *only* applies to `embed.php`.
