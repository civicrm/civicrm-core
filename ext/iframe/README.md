# IFrame Connector

The IFrame Connector enables external websites to embed CiviCRM pages using an HTML `<iframe>`.  Specifically, it supports public-facing pages (such
as "Contribution Pages").  This is a primary building-block for the [oEmbed](https://oembed.com/) integration
([dev/core#2994](https://lab.civicrm.org/dev/core/-/issues/2994)).

At time of writing, the extension is in an incubation period. It does not appear in the regular list of extensions.

## Quick start

* Enable the `iframe` extension.

    ```bash
    ## During the incubation period, you can enable via CLI or API
    cv en iframe
    ```

* Install the entry-point script (`iframe.php`). There are three ways to do this:
    * __Web UI__: Open the "System Status". If you have permission, it will show a button "Deploy now".
    * __Manual__: Open the "System Status". It will show a button "Deploy instructions". Copy-paste the content into the target file.
    * __CLI/API__: Run `cv api4 Iframe.installScript`

* Pick a CiviCRM page (eg `civicrm/contribute/transact?reset=1&id=1`). On an external website, you can make an HTML page which embeds the CiviCRM page:
    ```html
    <IFRAME SRC="http://example.org/iframe.php/civicrm/contribute/transact?reset=1&id=1"/>
    ```

* Optionally, navigate to "Administer > System Settings > IFrame Connector Settings" to fine-tune the options.

## Overview

The extension is a building-block for oEmbed support.  Many oEmbed providers use `<iframe>`s as the lower-level mechanisms for embedding rich content on
remote sites, and `<iframe>`s generally work with a range of browsers+servers.  The approach should support core CiviCRM technologies such as
UFs/Extensions/Hooks/CRUD APIs as well as QuickForm/jQuery and Angular/Afform/SearchKit.

However, embedding is not trivial.  Embedded pages differ from regular pages in important ways:

* __Browser authorization__: Based on default CMS policies, browsers will decline to show iframes for regular pages. We need to opt-in to support specific pages.
* __Page-flow and state__: Many useful pages require AJAX subrequests and/or HTTP POST-backs. The requests often rely on sessions, but iframes don't reliably support ordinary (cookie-based) sessions.
* __Styling, navigation, and sizing__: The host web-page will have its own appearance. Embedded content should have minimal styling that allows it to visually fit-in.
* __User identity__: Embedded pages are generally anonymous. You do not inherit the user-identity from CiviCRM-UF.

On the whole, embeds represent a distinctive runtime context.  For comparison, many web platforms address 3+ runtime contexts: "public frontend",
"staff backend", "web services".  Each context has a different set of rules regarding sessions, headers, pageflow/state, styling, etc.  The "embedded
frontend" is similar runtime context.

The extension implements this with a new entry-point, `iframe.php`. Compare:

```
Site URL:             https://example.org/
Example Page:         https://example.org/civicrm/contribute/transact?reset=1&id=1

IFRAME Entry Point:   https://example.org/iframe.php
IFRAME Example Page:  https://example.org/iframe.php/civicrm/contribute/transact?reset=1&id=1
```

## In depth: Developing the entry-point

* The `iframe.php` script needs to be deployed by content-providers. It does not need to be deployed by content-consumers.

* The `iframe.php` script is based on a template.  There are multiple template files (`Civi/Iframe/EntryPoint/*.php`) for different web-environments.  The
  current convention is to name each template for its intended environment (`Drupal.php`, `Drupal8.php`, etc).

* Whenever you edit your template in `Civi/Iframe/EntryPoint/`, the status-check will complain that `iframe.php` is out-of-date and prompt you to re-deploy it.

    (The quickest way to republish is to run `cv api4 Iframe.installScript` or `cv api4 -I Iframe.RenderScript > /path/to/iframe.php`)

* The D10 template is more involved. It needs some helper classes. These are in `lib/`.

* Does this absolutely need to be a separate PHP file in the web-root?  Maybe not; it depends.
    * It has some benefits.  It can use the regular UF bootstrap methods.  The URL is easy to predict and interpret.  It gets high privileges in manipulating the app (e.g.  to configure session options).
    * Alas, it does require an extra installation step, and some mass-market/vertically-integrated web-hosts may not allow the installation step.  So rewriting another way might be nice.
    * Suggestion for anyone wanting to go that way:
        1. Wait until we've hit some more milestones (*like: abstracting the embed-route-generator -- so you can layer-in whatever gnarly URLs*)
        2. Read the existing templates for inspiration on the kind of steps needed for each UF. Some UF's may provide sufficient runtime APIs; others may not.

## In depth: Page-flow and state

TODO

## Issues / TODOs

* [ ] March 5.72-RC Target
    * [x] Rename existing stuff to `ext/iframe`
    * [ ] Add the real `ext/oembed` (https://oembed.com/#section4)
        * [ ] End-point for `civicrm/share/` which outputs an oEmbed stub
    * [ ] IFRAME sizing: setting
        * Setting for default (maybe width=480? width=720? https://mediag.com/blog/popular-screen-resolutions-designing-for-all/)
    * [ ] IFRAME sizing: customizable (`civicrm/share?size=NNNxNNN`)
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
    * [x] Doc: info.xml FIXMEs
    * [ ] E2E tests for cookie/session behavior of `iframe.php`
    * [ ] E2E tests for rendering
    * [ ] E2E tests for settings `oembed_allow`, `oembed_allow_other`
    * [x] Consider stricter X-Frame-Options/Content-Security-Policy
* [ ] Important but not ASAP
    * [ ] Stricter JWT: https://lab.civicrm.org/dev/core/-/issues/2994#note_156045
        * If referrer doesn't match our iframe setup, then reset or reject the session id; probably includes test
    * [ ] Add entry-point for UF=Standalone (boot; invoke; X-Frame-Options; etc)
    * [ ] Add entry-point for UF=WordPress (script-file)
    * [ ] Add entry-point for UF=Backdrop
* [ ] Wishlist (Budget guided)
    * [ ] Add entry-point for UF=WordPress (wp-rest)
    * [ ] Share Workflow: Public Links (templates/CRM/common/SocialNetwork.tpl; crmRegion or hook)
    * [ ] Share Workflow: Admin Links (Contribution Page, Event Page, Petition?)
    * [ ] Share Workflow: Page Default/Singular/Standard/Built-in
    * [ ] Share Workflow: Afform Share Widget
    * [ ] Session-propagation targetting
      On the last page, there are hyperlinks like "Tell Friend" and "Create PCP" and "Back to CiviCRM Home Page".
      URL's generated by BAO (wchich calls to CRM_Utils_System::url()). Question is how to make a selector to change these?
      - Tweak each call to url()? Maybe `Civi::url('civicrm/contribute/campaign...')->setSession(TRUE)
      - Add some JS that filters all hyperlinks within a certain area? (`CRM.$('thankyoupage a).addSessionId('xyz')`)
      - Add some alterContent to do the same as above? Or add a filter to the regeion?
      - Define an attr for `<A HREF= SESSION=1`> or `<A HREF= CLASS="crm-session">`. But interpret client-side or server-side?
    * [ ] IFRAME sizing: dynamic
* [ ] Wishlist (Long term)
    * [ ] Add entry-point for UF=Joomla
