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
{* this template is used for adding/editing/deleting Credit Card  *}
<div class="form-item form-item crm-block crm-form-block crm-contribution-form-block">
<fieldset><legend>{if $action eq 1}{ts}New Credit Card{/ts}{elseif $action eq 2}{ts}Edit Credit Card{/ts}{else}{ts}Delete Credit Card{/ts}{/if}</legend>

   {if $action eq 8}
      <div class="messages status no-popup">
        <div class="icon inform-icon"></div>
          {ts}WARNING: If you delete this option, contributors will not be able to use this credit card type on your Online Contribution pages.{/ts} {ts}Do you want to continue?{/ts}
      </div>
     {else}
      <table class="form-layout-compressed">
         <tr class="crm-contribution-form-block-name">
      <td class="label">{$form.name.label}</td>
      <td class="html-adjust">{$form.name.html}<br />
        <span class="description">{ts}The name for this credit card type as it should be provided to your payment processor.{/ts}</span>
       </td>
    </tr>
    <tr class="crm-contribution-form-block-title">
      <td class="label">{$form.title.label}</td>
  <td class="html-adjust">{$form.title.html}<br />
        <span class="description">{ts}The name for this credit card type as it is displayed to contributors. This may be the same value as the Name above, or a localised title.{/ts}</span>
       </td>
    </tr>
    <tr class="crm-contribution-form-block-is_active">
       <td class="label">{$form.is_active.label}</td>
       <td class="html-adjust">{$form.is_active.html}</td>
    </tr>
    </table>
     {/if}
    <div class="crm-submit-buttons">{$form.buttons.html}</div>
</fieldset>
</div>
