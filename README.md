CiviCRM (WordPress Integration)
===============================

This is the development repository for the *CiviCRM* plugin for *WordPress*. What you see here is not intended as the code for the installation of *CiviCRM* in *WordPress* - if you want to do that, please visit:

[https://civicrm.org/download](https://civicrm.org/download)

### "hooks" Branch Instructions ###

It now invokes CiviCRM on the front-end before any template is reached. I have split the handling of Civi into the four contexts in which it may be called: 

* In WordPress admin 
* On the wpBasePage 
* via AJAX/snippet/iCal etc 
* via a shortcode 

The plugin now invokes Civi on the admin side properly, so that Civi's resources are only added when the Civi admin page is loaded. Previously they were added on every admin page. 

On the wpBasePage, the Civi content is rendered into a property, then returned later when called for by 'the_content'. I have added things like filtering 'wp_title' and the page title with the title of the Civi entity. This works nicely, but introduces a new issue, which is that I think the logic of displaying the Civi title in the Civi template is the reverse of what it should be... I don't want the Civi title to be rendered on wpBasePage, because I am now able to override the theme's page title. In shortcodes, by contrast, I *do* want the Civi entity's title, because it forms a part of the parent page and does not try to hijack it any more. 

AJAX/snippet/iCal calls exit much earlier now, which seems appropriate. As a result, I've ditched the multi-purpose 'wp_frontend' method, which I think was causing confusion by trying to be, um, all things in all contexts. I realise that this still requires the 'is_page_request()' method to distinguish between contexts - perhaps we can think that through sometime. 

And lastly, shortcodes... 

When there is a single shortcode to display, its markup is generated and stored, then returned when the shortcode is rendered in 'the_content', regardless of whether it's an archive or singular page/post. 

Multiple shortcodes are not handled particularly well as yet (there is dummy content like you suggested that links to the singular page it is embedded in) but the architecture is there to enhance this. At present, Civi is only allowed to be invoked once - I assume this was by design? - and this would have to be changed if multiple shortcodes are to be rendered on the same page.

I have added an option in the CiviCRM button modal dialog which allows a single instance of a shortcode to "hijack" the containing page - it overwrites the HTML title, page title and page content with the relevant parts of the CiviCRM entity that the shortcode represents. To enable this, a change to CiviCRM core is required. You must replace `function setTitle() `in /wp-content/plugins/civicrm/civicrm/CRM/Utils/System/WordPress.php with...

```php

function setTitle($title, $pageTitle = NULL) {
  if (!$pageTitle) {
    $pageTitle = $title;
  }
    
  // always set global
  global $civicrm_wp_title;
  $civicrm_wp_title = $pageTitle;
  
  $context = civi_wp()->civicrm_context_get();
  switch ( $context ) {
    case 'admin':
    case 'shortcode':
      $template = CRM_Core_Smarty::singleton();
      $template->assign('pageTitle', $pageTitle);
  }
}

```

The commit trail is a bit ragged since I was making such wholesale changes, but I think if you read through the code as it stands, it will make more sense than the current version of the plugin.

----
### Contribute ###

If you want to contribute to the development of this plugin, please bear the following in mind:

* Bug fixes should go in the branch 4.5 branch (stable)* Structural changes should go under master (trunk, i.e. 4.6).

----
### About CiviCRM ###
CiviCRM is web-based, open source, Constituent Relationship Management (CRM) software geared toward meeting the needs of non-profit and other civic-sector organizations.

As a non profit committed to the public good itself, CiviCRM understands that forging and growing strong relationships with constituents is about more than collecting and tracking constituent data - it is about sustaining relationships with supporters over time.

To this end, CiviCRM has created a robust web-based, open source, highly customizable, CRM to meet organizations’ highest expectations right out-of-the box. Each new release of this open source software reflects the very real needs of its users as enhancements are continually given back to the community.

With CiviCRM's robust feature set, organizations can further their mission through contact management, fundraising, event management, member management, mass e-mail marketing, peer-to-peer campaigns, case management, and much more.

CiviCRM is localized in over 20 languages including: Chinese (Taiwan, China), Dutch, English (Australia, Canada, U.S., UK), French (France, Canada), German, Italian, Japanese, Russian, and Swedish.

For more information, visit the [CiviCRM website](https://civicrm.org).