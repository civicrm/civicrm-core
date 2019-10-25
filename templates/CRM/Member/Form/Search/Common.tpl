{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
<tr>
  <td><label>{$form.membership_type_id.label}</label><br />
      {$form.membership_type_id.html|crmAddClass:twenty}
  </td>
  <td><label>{$form.membership_status_id.label}</label><br />
      {$form.membership_status_id.html}
  </td>
</tr>

<tr>
  <td>{$form.member_source.label}<br />
    {$form.member_source.html}
  </td>
  <td>
    {$form.membership_is_current_member.label}<br />
    {$form.membership_is_current_member.html}
  </td>
</tr>
<tr>
  <td>{$form.member_test.label} {help id="is-test" file="CRM/Contact/Form/Search/Advanced"} &nbsp;{$form.member_test.html}
  </td>
  <td>
    {$form.member_is_primary.label} {help id="id-member_is_primary" file="CRM/Member/Form/Search.hlp"} {$form.member_is_primary.html}
  </td>
</tr>
<tr><td><label>{$form.membership_id.label}</label> {$form.membership_id.html}</td>
  <td>{$form.member_pay_later.label}&nbsp;{$form.member_pay_later.html}</td>
</tr>
<tr>
  <td>
    {if $form.member_auto_renew}
      <label>{$form.member_auto_renew.label}</label>
      {help id="id-member_auto_renew" file="CRM/Member/Form/Search.hlp"}
      <br/>
      {$form.member_auto_renew.html}
    {/if}
  </td>
  <td>{$form.member_is_override.label}{help id="id-member_is_override" file="CRM/Member/Form/Search.hlp"}{$form.member_is_override.html}</td>
</tr>
<tr>
  {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="membership_join_date" colspan='2'}
</tr>
<tr>
  {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="membership_start_date" colspan='2'}
</tr>
<tr>
  {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="membership_end_date" colspan='2'}
</tr>

{* campaign in membership search *}
{include file="CRM/Campaign/Form/addCampaignToComponent.tpl" campaignContext="componentSearch"
campaignTrClass='' campaignTdClass=''}

{if $membershipGroupTree}
<tr>
  <td colspan="4">
  {include file="CRM/Custom/Form/Search.tpl" groupTree=$membershipGroupTree showHideLinks=false}
  </td>
</tr>
{/if}
