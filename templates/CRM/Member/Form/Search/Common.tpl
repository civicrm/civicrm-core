{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
  <td><label>{ts}Membership Type(s){/ts}</label><br />
    <div class="listing-box">
    {foreach from=$form.member_membership_type_id item="membership_type_val"}
      <div class="{cycle values='odd-row,even-row'}">
        {$membership_type_val.html}
      </div>
    {/foreach}
    </div>
  </td>
  <td><label>{ts}Membership Status{/ts}</label><br />
    <div class="listing-box">
    {foreach from=$form.member_status_id item="membership_status_val"}
      <div class="{cycle values='odd-row,even-row'}">
        {$membership_status_val.html}
      </div>
    {/foreach}
    </div>
  </td>
</tr>

<tr>
  <td>
  {$form.member_source.label}
    <br />{$form.member_source.html}
    <p>
    {$form.member_test.label} {help id="is-test" file="CRM/Contact/Form/Search/Advanced"} &nbsp;{$form.member_test.html}
      <span class="crm-clear-link">
        (<a href="#" title="unselect" onclick="unselectRadio('member_test', '{$form.formName}'); return false;" >
        {ts}clear{/ts}</a>)
      </span>
    </p>
  </td>
  <td>
    <p>
    {$form.member_is_primary.label}
    {help id="id-member_is_primary" file="CRM/Member/Form/Search.hlp"}
    {$form.member_is_primary.html}
      <span class="crm-clear-link">
        (<a href="#" title="unselect" onclick="unselectRadio('member_is_primary', '{$form.formName}'); return false;" >
        {ts}clear{/ts}</a>)
      </span>
    </p>
    <p>
    {$form.member_pay_later.label}&nbsp;{$form.member_pay_later.html}
      <span class="crm-clear-link">
        (<a href="#" title="unselect" onclick="unselectRadio('member_pay_later', '{$form.formName}'); return false;" >
        {ts}clear{/ts}</a>)
      </span>
    </p>
    <p>
    {$form.member_auto_renew.label}&nbsp;{$form.member_auto_renew.html}
      <span class="crm-clear-link">
        (<a href="#" title="unselect" onclick="unselectRadio('member_auto_renew', '{$form.formName}'); return false;" >
        {ts}clear{/ts}</a>)
      </span>
    </p>
  </td>
</tr>

<tr><td><label>{ts}Member Since{/ts}</label></td></tr>
<tr>
{include file="CRM/Core/DateRange.tpl" fieldName="member_join_date" from='_low' to='_high'}
</tr>

<tr><td><label>{ts}Start Date{/ts}</label></td></tr>
<tr>
{include file="CRM/Core/DateRange.tpl" fieldName="member_start_date" from='_low' to='_high'}
</tr>

<tr><td><label>{ts}End Date{/ts}</label></td></tr>
<tr>
{include file="CRM/Core/DateRange.tpl" fieldName="member_end_date" from='_low' to='_high'}
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
