{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
    <p>{docURL page='user/organising-your-data/smart-groups/'}</p>
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
    {if !empty($form.group_type)}
      <tr class="crm-contact-task-createsmartgroup-form-block-group_type">
        <td class="label">{$form.group_type.label}</td>
        <td>{$form.group_type.html}</td>
      </tr>
      <tr>
        <td colspan=2>{include file="CRM/common/customDataBlock.tpl" groupID='' customDataType='Group' customDataSubType=false cid=false}</td>
      </tr>
    {/if}
  </table>

  {*CRM-14190*}
  {include file="CRM/Group/Form/GroupsCommon.tpl"}

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
