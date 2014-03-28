{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
<div class="crm-block crm-form-block crm-config-backend-form-block">
<div id="help">
    <p>
    {ts}Use this form if you need to reset the Base Directory Path and Base URL settings for your CiviCRM installation. These settings are stored in the database, and generally need adjusting after moving a CiviCRM installation to another location in the file system and/or to another URL.{/ts}</p>
    <p>
    {ts}CiviCRM will attempt to detect the new values that should be used. These are provided below as the default values for the <strong>New Base Directory</strong> and <strong>New Base URL</strong> fields.{/ts}</p>
</div>
        <div>{$form._qf_UpdateConfigBackend_next_cleanup.html}</div>
        <table>
            <tr class="crm-config-backend-form-block-oldBaseDir">
                <td class="label">{ts}Old Base Directory{/ts}</td>
                <td>{$oldBaseDir}</td>
            </tr>
            <tr class="crm-config-backend-form-block-newBaseDir">
                <td class="label">{$form.newBaseDir.label}</td>
                <td>{$form.newBaseDir.html|crmAddClass:'huge'}<br />
                <span class="description">{ts}For Drupal and WordPress installs, this is the absolute path to the location of the 'files' directory. For Joomla installs this is the absolute path to the location of the 'media' directory.{/ts}</span></td>
            </tr>
            <tr class="crm-config-backend-form-block-oldBaseURL">
                <td class="label">{ts}Old Base URL{/ts}</td>
                <td>{$oldBaseURL}</td>
            </tr>
            <tr class="crm-config-backend-form-block-newBaseURL">
                <td class="label">{$form.newBaseURL.label}</td>
                <td>{$form.newBaseURL.html|crmAddClass:'huge'}<br />
                <span class="description">{ts}This is the URL for your Drupal, Joomla or WordPress site (e.g. http://www.mysite.com/drupal/).{/ts}</span></td>
            </tr>
{if $oldSiteName}
            <tr class="crm-config-backend-form-block-oldSiteName">
                <td class="label">{ts}Old Site Name{/ts}</td>
                <td>{$oldSiteName}</td>
            </tr>
            <tr class="crm-config-backend-form-block-newSiteName">
                <td class="label">{$form.newSiteName.label}</td>
                <td>{$form.newSiteName.html|crmAddClass:'huge'}<br />
                <span class="description">{ts}This is the your site name for a multisite install.{/ts}</span></td>
            </tr>
{/if}
        </table>
        <div>{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
<div class="spacer"></div>
</div>
