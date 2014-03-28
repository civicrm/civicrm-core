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
{* this template is used for adding/editing Wordpress Access Control  *}
<div id="help">
    <p>{ts}Use this form to Grant access to CiviCRM components and other CiviCRM permissions to WordPress roles.{/ts}<br /><br />
  {ts}<strong>NOTE: Super Admin</strong> and <strong>Administrator</strong> roles will have all permissions in CiviCRM.{/ts}
  </p>
</div>
<div class="crm-block crm-form-block crm-export-form-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
<table>
     <tr>
        {assign var="i" value=1}
      {foreach from=$roles key=role_name item=role_value }
      <td style="padding:0;">
          <table border="0" cellpadding="0" cellspacing="0">
          <tr>
                <td class="label">&nbsp;</td>
                <td align="center"><b>{$role_value}</b><br /></td>
          </tr>
          {assign var="j" value=0}
          {foreach from=$rolePerms.$role_name key=name item=value }
            {if $j mod 2 eq 1}
                <tr style="background-color: #E6E6DC;">
            {else}
                <tr style="background-color: #FFFFFF;">
            {/if}
                <td style="height:30px;">
                {if $i eq 1}
                  {$form.$role_name.$name.label}
                {/if}
                </td>
                <td align="center">{$form.$role_name.$name.html}<br /></td>
              </tr>
          {assign var="j" value=$j+1}
          {/foreach}
          </table>
          </td>
          {assign var="i" value=$i+1}
      {/foreach}
     </tr>
</table>

<div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

</div>
