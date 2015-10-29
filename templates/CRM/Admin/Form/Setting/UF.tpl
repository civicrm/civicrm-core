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
<div class="crm-block crm-form-block crm-uf-form-block">
<div class="help">
    {ts}These settings define the CMS variables that are used with CiviCRM.{/ts}
</div>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
      <table class="form-layout-compressed">
         <tr class="crm-uf-form-block-userFrameworkUsersTableName">
            <td class="label">{$form.userFrameworkUsersTableName.label}</td>
            <td>{$form.userFrameworkUsersTableName.html}</td>
        </tr>
        {if $form.wpBasePage}
         <tr class="crm-uf-form-block-wpBasePage">
            <td class="label">{$form.wpBasePage.label}</td>
            <td>{$config->userFrameworkBaseURL}{$form.wpBasePage.html}
            <p class="description">{ts 1=$config->userFrameworkBaseURL}By default, CiviCRM will generate front-facing pages using the home page at %1 as its base. If you want to use a different template for CiviCRM pages, set the path here.{/ts}</p>
            </td>
        </tr>
        {/if}
        </table>
            <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
<div class="spacer"></div>
{if $tablePrefixes}
<div class="form-item">
<fieldset>
    <legend>{ts}Views integration settings{/ts}</legend>
    <div>{ts}To enable CiviCRM Views integration, add the following to the site <code>settings.php</code> file:{/ts}</div>
    <pre>{$tablePrefixes}</pre>
</fieldset>
</div>
{/if}
</div>
