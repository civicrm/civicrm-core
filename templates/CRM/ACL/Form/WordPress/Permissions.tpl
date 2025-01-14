{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding/editing WordPress Access Control *}
<div class="help">
  <p>{ts}Use this form to Grant access to CiviCRM components and other CiviCRM permissions to WordPress roles.{/ts}</p>
  <p>{ts}<strong>NOTE: Super Admin</strong> and <strong>Administrator</strong> roles will have all permissions in CiviCRM.{/ts}</p>
</div>
<div class="crm-block crm-sticky">
  <table class="table-striped">
    <thead>
      <tr>
        <th class="label">&nbsp;</th>
        {assign var="num" value=0}
        {foreach from=$roles key=role_name item=role_value}
          <th align="center">{$role_value}</th>
          {assign var="num" value=$num+1}
        {/foreach}
      </tr>
    </thead>
    <tbody>
      {foreach from=$table key=perm_name item=row}
        <tr class="{cycle values="odd-row,even-row"}">
          <td style="height: 2.6em;">
            {$row.label}
            {if !empty($row.desc)}
              <br/><span class="description">{$row.desc}</span>
            {/if}
          </td>
          {foreach from=$row.roles key=index item=role_name}
            <td align="center" style="padding-top: 0.6em;">
              {$form.$role_name.$perm_name.html}
            </td>
          {/foreach}
        </tr>
      {/foreach}
    </tbody>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
