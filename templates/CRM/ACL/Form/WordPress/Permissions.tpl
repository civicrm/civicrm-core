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
<div class="crm-block crm-form-block crm-export-form-block">
  <table>
    <tr>
      <td class="label">&nbsp;</td>
      {assign var="num" value=0}
      {foreach from=$roles key=role_name item=role_value}
        <td align="center"><strong>{$role_value}</strong></td>
        {assign var="num" value=$num+1}
      {/foreach}
    </tr>

    {assign var="x" value=0}
    {foreach from=$table key=perm_name item=row}
      {if $x mod 2 eq 1}
        <tr style="background-color: #E6E6DC;">
      {else}
        <tr style="background-color: #FFFFFF;">
      {/if}

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
      {assign var="x" value=$x+1}
    {/foreach}

  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
