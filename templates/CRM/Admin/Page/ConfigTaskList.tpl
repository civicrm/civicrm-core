{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{capture assign="linkTitle"}{ts}Edit settings{/ts}{/capture}
{capture assign="adminMenu"}{crmURL p="civicrm/admin" q="reset=1"}{/capture}
<div class="help">
  {ts 1=$adminMenu}Use this checklist to review configuration tasks for your site. Settings that you have not yet reviewed will be <span class="status-overdue">displayed in red</span>. After you have visited a page, the links will <span class="status-pending">display in green</span>. You can access this page again from the <a href="%1">Administer CiviCRM</a> menu at any time.{/ts}
</div>
<table class="selector">
    <tr class="columnheader">
        <td colspan="2">{ts}Site Configuration{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/domain" q="action=update&reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle|escape}">{ts}Organization Contact Information{/ts}</a></td>
        <td>{ts}Configure your organization name, email address and postal address.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/setting/localization" q="reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle|escape}">{ts}Localization{/ts}</a></td>
        <td>{ts}Localization settings include user language, default currency and available countries for address input.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/setting/component" q="action=update&reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle|escape}">{ts}Components{/ts}</a></td>
        <td>{ts}Enable or disable Components such as CiviContribute, CiviEvent, CiviCase, etc.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/extensions" q="reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle|escape}">{ts}Extensions{/ts}</a></td>
        <td>{ts}Extensions are installable packages which give CiviCRM new functionality.{/ts} {ts}Some extenions are shipped with CiviCRM (known as core extensions, such as SearchKit and FormBuilder), but there are also many extensions developed by the community.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{$registerSite}" title="{ts}Register your site at CiviCRM.org. Opens in a new window.{/ts}" target="_blank">{ts}Register your site{/ts}</a></td>
        <td>{ts}Join the community and help CiviCRM remain a leading CRM for organizations worldwide.{/ts}</td>
    </tr>
    <tr class="columnheader">
        <td colspan="2">{ts}Viewing and Editing Contacts{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/setting/preferences/display" q="reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle|escape}">{ts}Display Preferences{/ts}</a></td>
        <td>{ts}Configure screen and form elements for Viewing Contacts, Editing Contacts, Advanced Search, Contact Dashboard and WYSIWYG Editor.{/ts} {ts 1="href='https://civicrm.org/themes' target='_blank'"}You can also change the theme here, but first it must be installed as an extension. You can <a %1>explore CiviCRM themes</a> on the extension directory.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/setting/preferences/address" q="reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle|escape}">{ts}Address Settings{/ts}</a></td>
        <td>{ts}Format addresses in mailing labels, input forms and how they are displayed on screens.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/setting/mapping" q="reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle|escape}">{ts}Mapping and Geocoding{/ts}</a></td>
        <td>{ts}Configure a mapping provider (e.g. OpenStreetMap or Google) to display maps for contact addresses and event locations.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/setting/search" q="reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle|escape}">{ts}Search Settings{/ts}</a></td>
        <td>{ts}Adjust search behaviors including wildcards, and data to include in quick search results. Adjusting search settings can improve performance for larger datasets.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/options/subtype" q="reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle|escape}">{ts}Contact Types{/ts}</a></td>
        <td>{ts}You can modify the names of the built-in contact types (Individual, Household, Organizations), and you can create or modify "contact subtypes" for more specific uses (e.g. Student, Parent, Team, etc.).{/ts}</td>
    </tr>
    <tr class="columnheader">
        <td colspan="2">{ts}Sending Emails (also used for contribution receipts and event confirmations){/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/options/from_email_address" q="reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle|escape}">{ts}From Email Addresses{/ts}</a></td>
        <td>{ts}Define general email address(es) that can be used as the FROM address when sending email to contacts from within CiviCRM (e.g. info@example.org){/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/setting/smtp" q="reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle|escape}">{ts}Outbound Email{/ts}</a></td>
        <td>{ts}Settings for outbound email - either SMTP server, port and authentication or Sendmail path and argument.{/ts}</td>
    </tr>
    <tr class="columnheader">
        <td colspan="2">{ts}Online Contributions / Online Membership Signup / Online Event Registration{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/paymentProcessor" q="reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle|escape}">{ts}Payment Processors{/ts}</a></td>
        <td>{ts}Select and configure one or more payment processing services for online contributions, events and / or membership fees.{/ts}</td>
    </tr>
    {if $config->userSystem->is_drupal EQ '1'}
        <tr class="even">
            {if $config->userFramework EQ 'Drupal'}
                <td class="tasklist"><a href="{$config->userFrameworkBaseURL}?q=admin/people/permissions&civicrmDestination=civicrm/admin/configtask">{ts}Permissions for Anonymous Users{/ts}</a></td>
            {else}
                <td class="tasklist"><a href="{$config->userFrameworkBaseURL}?q=admin/user/permissions&civicrmDestination=civicrm/admin/configtask">{ts}Permissions for Anonymous Users{/ts}</a></td>
            {/if}
            <td>{ts}You will also need to change Drupal permissions so anonymous users can make contributions, register for events and / or use profiles to enter contact information.{/ts} {docURL page="user/initial-set-up/permissions-and-access-control"}</td>
        </tr>
    {/if}
    {if $enabledComponents.CiviContribute eq 1}
      <tr class="even">
          <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/setting/preferences/contribute" q="selectedChild=workflow&reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle|escape}">{ts}CiviContribute Component Settings{/ts}</a></td>
          <td>{ts}Review and modify the CiviContribute Component settings such as Taxes and Invoicing, Deferred Revenue, and Access Control by Financial Type{/ts}</td>
      </tr>
    {/if}
</table>
<br />

<div class="description">
    {capture assign=docUrlText}{ts}Organizing Your Data{/ts}{/capture}
    {capture assign=docLink}{docURL page="user/organising-your-data/overview" text=$docUrlText}{/capture}
    {ts 1=$adminMenu 2=$docLink}The next set of tasks involve planning and have multiple steps. You may want to check out the %2 section in the User and Administrator Guide before you begin. You will not be returned to this page after completing these tasks, but you can always get back here from the <a href="%1">Administer CiviCRM</a> menu.{/ts}
</div>

<table class="selector">
    <tr class="columnheader">
        <td colspan="2">{ts}Organize your contacts{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/tag" q="reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle|escape}">{ts}Tags{/ts}</a></td>
        <td>{ts}Tags can be assigned to any contact record, and are a convenient way to find contacts. You can create as many tags as needed to organize and segment your records.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/group" q="reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle|escape}">{ts}Manage Groups{/ts}</a></td>
        <td>{ts}Use Groups to organize contacts (e.g. these contacts are part of our 'Steering Committee').{/ts}</td>
    </tr>

    <tr class="columnheader">
        <td colspan="2">{ts}Customize Data, Forms and Screens{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/custom/group" q="reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle|escape}">{ts}Custom Fields{/ts}</a></td>
        <td>{ts}Configure custom fields to collect and store custom data which is not included in the standard CiviCRM forms.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/uf/group" q="reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle|escape}">{ts}Profiles{/ts}</a></td>
        <td>{ts}Profiles allow you to aggregate groups of fields and include them in your site as input forms, contact display pages, and search and listings features.{/ts}</td>
    </tr>
</table>
<br />
<div class="description">
    {ts}Now you can move on to exploring, configuring and using the various optional components for fundraising and constituent engagement. The links below will take you to the online documentation for each component.{/ts}
</div>
<table class="selector">
    <tr class="columnheader">
        <td colspan="2">{ts}Components{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap" style="width: 10%;">{docURL page="user/contributions/what-is-civicontribute" text=$componentTitles.CiviContribute}</td>
        <td>{ts}Online fundraising and donor management, as well as offline contribution processing and tracking.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap" style="width: 10%;">{docURL page="user/pledges/what-is-civipledge" text=$componentTitles.CiviPledge}</td>
        <td>{ts}Accept and track pledges (for recurring gifts).{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap">{docURL page="user/events/what-is-civievent" text=$componentTitles.CiviEvent}</td>
        <td>{ts}Online event registration and participant tracking.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap">{docURL page="user/membership/what-is-civimember" text=$componentTitles.CiviMember}</td>
        <td>{ts}Online signup and membership management.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap">{docURL page="user/email/what-is-civimail" text=$componentTitles.CiviMail}</td>
        <td>{ts}Personalized email blasts and newsletters.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap">{docURL page="user/campaign/what-is-civicampaign" text=$componentTitles.CiviCampaign}</td>
        <td>{ts}Link together events, mailings, activities, and contributions. Create surveys and online petitions.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap">{docURL page="user/case-management/what-is-civicase" text=$componentTitles.CiviCase}</td>
        <td>{ts}Integrated case management for human service providers{/ts}</td>
    </tr>
</table>
