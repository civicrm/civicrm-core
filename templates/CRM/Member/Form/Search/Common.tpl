{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
  <td>{$form.member_test.label} {help id="is-test" file="CRM/Contact/Form/Search/Advanced"} {$form.member_test.html}
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
    {if !empty($form.member_auto_renew)}
      <label>{$form.member_auto_renew.label}</label>
      {help id="id-member_auto_renew" file="CRM/Member/Form/Search.hlp"}
      <br/>
      {$form.member_auto_renew.html}
    {/if}
  </td>
  <td>{$form.member_is_override.label} {help id="id-member_is_override" file="CRM/Member/Form/Search.hlp"} {$form.member_is_override.html}</td>
</tr>
<tr>
  {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="membership_join_date" to='' from='' colspan='2' class='' hideRelativeLabel=0}
</tr>
<tr>
  {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="membership_start_date" to='' from='' colspan='2' class='' hideRelativeLabel=0}
</tr>
<tr>
  {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="membership_end_date" to='' from='' colspan='2' class='' hideRelativeLabel=0}
</tr>

{* campaign in membership search *}
{include file="CRM/Campaign/Form/addCampaignToSearch.tpl"
campaignTrClass='' campaignTdClass=''}

{if !empty($membershipGroupTree)}
<tr>
  <td colspan="4">
  {include file="CRM/Custom/Form/Search.tpl" groupTree=$membershipGroupTree showHideLinks=false}
  </td>
</tr>
{/if}
