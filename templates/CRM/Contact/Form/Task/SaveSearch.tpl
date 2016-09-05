{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
<div class="crm-form-block crm-block crm-contact-task-createsmartgroup-form-block">
  <h3>{ts}Smart Group{/ts}</h3>
  <div class="help">
    <p>{ts}This smart group will stay up-to-date with all contacts who meet the search criteria.{/ts}</p>
    {if !empty($partiallySelected)}
      <p>{ts}NOTE: Even if only a few contacts have been selected from the search results, all contacts that meet the following criteria will be in the group.{/ts}</p>
    {/if}
    {if !empty($qill[0])}
    <div id="search-status">
      <ul>
        {foreach from=$qill[0] item=criteria}
          <li>{$criteria}</li>
        {/foreach}
      </ul>
    </div>
    {/if}
    <p>{docURL page='user/current/organising-your-data/smart-groups/'}</p>
  </div>
  <table class="form-layout-compressed">
    <tr class="crm-contact-task-createsmartgroup-form-block-title">
      <td class="label">{$form.title.label}</td>
      <td>{$form.title.html}</td>
    </tr>
    <tr class="crm-contact-task-createsmartgroup-form-block-description">
      <td class="label">{$form.description.label}</td>
      <td>{$form.description.html}</td>
    </tr>
    {if $form.group_type}
      <tr class="crm-contact-task-createsmartgroup-form-block-group_type">
        <td class="label">{$form.group_type.label}</td>
        <td>{$form.group_type.html}</td>
      </tr>
    {/if}
  </table>

  {*CRM-14190*}
  {include file="CRM/Group/Form/ParentGroups.tpl"}

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
