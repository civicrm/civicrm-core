{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
{* this template is used for adding/editing location type  *}
<div class="crm-block crm-form-block crm-location-type-form-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
{if $action eq 8}
  <div class="messages status no-popup">
     <div class="icon inform-icon"></div>
        {ts}WARNING: Deleting this option will result in the loss of all location type records which use the option.{/ts} {ts}This may mean the loss of a substantial amount of data, and the action cannot be undone.{/ts} {ts}Do you want to continue?{/ts}
      </div>
{else}
  <table class="form-layout-compressed">
      <tr class="crm-location-type-form-block-label">
          <td class="label">{$form.name.label}</td>
          <td>{$form.name.html}<br />
               <span class="description">{ts}WARNING: Do NOT use spaces in the Location Name.{/ts}</span>
          </td>
      </tr>
      <tr class="crm-location-type-form-block-display_name">
          <td class="label">{$form.display_name.label}</td>
          <td>{$form.display_name.html}</td>
      </tr>
      <tr class="crm-location-type-form-block-vcard_name">
          <td class="label">{$form.vcard_name.label}</td>
          <td>{$form.vcard_name.html}</td>
      </tr>
      <tr class="crm-location-type-form-block-description">
          <td class="label">{$form.description.label}</td>
          <td>{$form.description.html}</td>
      </tr>
      <tr class="crm-location-type-form-block-is_active">
          <td class="label">{$form.is_active.label}</td>
          <td>{$form.is_active.html}</td>
      </tr>
      <tr  class="crm-location-type-form-block-is_default">
           <td class="label">{$form.is_default.label}</td>
           <td>{$form.is_default.html}</td>
      </tr>
  </table>
{/if}
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
