{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{capture assign="linkTitle"}{ts}Edit settings{/ts}{/capture}
{capture assign="adminMenu"}{crmURL p="civicrm/admin" q="reset=1"}{/capture}

<div id="help" class="description">
    {ts 1=$adminMenu}Use this checklist to review and complete configuration tasks for your site. You will be redirected back to this checklist after saving each setting. Settings which you have not yet reviewed will be <span class="status-overdue">displayed in red</span>. After you have visited a page, the links will <span class="status-pending">display in green</span>  (although you may still need to revisit the page to complete or update the settings). You can access this page again from the <a href="%1">Administer CiviCRM</a> menu at any time.{/ts}
</div>

<table class="selector">
    <tr class="columnheader">
        <td colspan="2">{ts}Site Configuration and Registration{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/setting/localization" q="reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle}">{ts}Localization{/ts}</a></td>
        <td>{ts}Localization settings include user language, default currency and available countries for address input.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/domain" q="action=update&reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle}">{ts}Organization Address and Contact Info{/ts}</a></td>
        <td>{ts}Organization name, email address for system-generated emails, organization address{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/setting/component" q="action=update&reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle}">{ts}Enable components{/ts}</a></td>
        <td>{ts}Enable the required CiviCRM components.(CiviContribute, CiviEvent etc.){/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{$registerSite}" title="{ts}Register your site at CiviCRM.org. Opens in a new window.{/ts}" target="_blank">{ts}Register your site{/ts}</a></td>
        <td>{ts}Register your site, join the community, and help CiviCRM remain a leading CRM for organizations worldwide.{/ts}</td>
    </tr>

    <tr class="columnheader">
        <td colspan="2">{ts}Viewing and Editing Contacts{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/setting/preferences/display" q="reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle}">{ts}Display Preferences{/ts}</a></td>
        <td>{ts}Configure screen and form elements for Viewing Contacts, Editing Contacts, Advanced Search, Contact Dashboard and WYSIWYG Editor.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/setting/preferences/address" q="reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle}">{ts}Address Settings{/ts}</a></td>
        <td>{ts}Format addresses in mailing labels, input forms and screen display.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/setting/mapping" q="reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle}">{ts}Mapping and Geocoding{/ts}</a></td>
        <td>{ts}Configure a mapping provider (e.g. Google or Yahoo) to display maps for contact addresses and event locations.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/setting/search" q="reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle}">{ts}Search Settings{/ts}</a></td>
        <td>{ts}Adjust search behaviors including wildcards, and data to include in quick search results. Adjusting search settings can improve performance for larger datasets.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/setting/misc" q="reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle}">{ts}Misc (Undelete, PDFs, Limits, Logging, Captcha, etc.){/ts}</a></td>
        <td>{ts}Version reporting and alerts, reCAPTCHA configuration and attachments.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/options/subtype" q="reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle}">{ts}Contact Types{/ts}</a></td>
        <td>{ts}You can modify the names of the built-in contact types (Individual, Household, Organizations), and you can create or modify "contact subtypes" for more specific uses (e.g. Student, Parent, Team, etc.).{/ts}</td>
    </tr>

    <tr class="columnheader">
        <td colspan="2">{ts}Sending Emails (includes contribution receipts and event confirmations){/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/setting/smtp" q="reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle}">{ts}Outbound Email{/ts}</a></td>
        <td>{ts}Settings for outbound email - either SMTP server, port and authentication or Sendmail path and argument.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/options/from_email_address" q="reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle}">{ts}From Email Addresses{/ts}</a></td>
        <td>{ts}Define general email address(es) that can be used as the FROM address when sending email to contacts from within CiviCRM (e.g. info@example.org){/ts}</td>
    </tr>

    <tr class="columnheader">
        <td colspan="2">{ts}Online Contributions / Online Membership Signup / Online Event Registration{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/paymentProcessor" q="reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle}">{ts}Payment Processors{/ts}</a></td>
        <td>{ts}Select and configure one or more payment processing services for online contributions, events and / or membership fees.{/ts}</td>
    </tr>
    {if $config->userSystem->is_drupal EQ '1'}
        <tr class="even">
            {if $config->userFramework EQ 'Drupal'}
                <td class="tasklist"><a href="{$config->userFrameworkBaseURL}?q=admin/people/permissions&civicrmDestination=civicrm/admin/configtask">{ts}Permissions for Anonymous Users{/ts}</a></td>
            {else}
                <td class="tasklist"><a href="{$config->userFrameworkBaseURL}?q=admin/user/permissions&civicrmDestination=civicrm/admin/configtask">{ts}Permissions for Anonymous Users{/ts}</a></td>
            {/if}
            <td>{ts}You will also need to change Drupal permissions so anonymous users can make contributions, register for events and / or use profiles to enter contact information.{/ts} {docURL page="Default Permissions and Roles" resource="wiki"}</td>
        </tr>
    {/if}
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/messageTemplates" q="selectedChild=workflow&reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle}">{ts}System Workflow Templates{/ts}</a></td>
        <td>{ts}Review and modify the templates used for system-generated emails, including contribution receipts and event registration confirmations.{/ts}</td>
    </tr>
</table>
<br />

<div class="description">
    {capture assign=docLink}{docURL page="user/organising-your-data/overview" text="Organizing Your Data"}{/capture}
    {ts 1=$adminMenu 2=$docLink}The next set of tasks involve planning and have multiple steps. You may want to check out the %2 section in the User and Administrator Guide before you begin. You will not be returned to this page after completing these tasks, but you can always get back here from the <a href="%1">Administer CiviCRM</a> menu.{/ts}
</div>

<table class="selector">
    <tr class="columnheader">
        <td colspan="2">{ts}Organize your contacts{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/tag" q="reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle}">{ts}Tags (Categories){/ts}</a></td>
        <td>{ts}Tags can be assigned to any contact record, and are a convenient way to find contacts. You can create as many tags as needed to organize and segment your records.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/group" q="reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle}">{ts}Manage Groups{/ts}</a></td>
        <td>{ts}Use Groups to organize contacts (e.g. these contacts are part of our 'Steering Committee').{/ts}</td>
    </tr>

    <tr class="columnheader">
        <td colspan="2">{ts}Customize Data, Forms and Screens{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/custom/group" q="reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle}">{ts}Custom Fields{/ts}</a></td>
        <td>{ts}Configure custom fields to collect and store custom data which is not included in the standard CiviCRM forms.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap"><a href="{crmURL p="civicrm/admin/uf/group" q="reset=1&civicrmDestination=`$destination`"}" title="{$linkTitle}">{ts}Profiles{/ts}</a></td>
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
        <td class="tasklist nowrap" style="width: 10%;">{docURL page="user/contributions/what-is-civicontribute" text="CiviContribute"}</td>
        <td>{ts}Online fundraising and donor management, as well as offline contribution processing and tracking.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap" style="width: 10%;">{docURL page="user/pledges/what-is-civipledge" text="CiviPledge"}</td>
        <td>{ts}Accept and track pledges (for recurring gifts).{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap">{docURL page="user/events/what-is-civievent" text="CiviEvent"}</td>
        <td>{ts}Online event registration and participant tracking.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap">{docURL page="user/membership/introduction-to-memberships" text="CiviMember"}</td>
        <td>{ts}Online signup and membership management.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap">{docURL page="user/email/what-is-civimail" text="CiviMail"}</td>
        <td>{ts}Personalized email blasts and newsletters.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap">{docURL page="user/campaign/what-is-civicampaign" text="CiviCampaign"}</td>
        <td>{ts}Link together events, mailings, activities, and contributions. Create surveys and online petitions.{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap">{docURL page="user/case-management/what-is-civicase" text="CiviCase"}</td>
        <td>{ts}Integrated case management for human service providers{/ts}</td>
    </tr>
    <tr class="even">
        <td class="tasklist nowrap">{docURL page="user/grants/what-is-civigrant" text="CiviGrant"}</td>
        <td>{ts}Distribute funds to others, for example foundations, grant givers, etc.{/ts}</td>
    </tr>
</table>
