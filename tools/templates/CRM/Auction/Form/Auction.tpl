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
<div class="form-item"> 
<fieldset><legend>{ts}Auction Information{/ts}</legend>
    <table class="form-layout-compressed">
         <tr><td class="label">{$form.title.label}</td><td>{$form.title.html}</td></tr>
         <tr><td class="label">{$form.description.label}</td><td>{$form.description.html}</td></tr>

         <tr><td class="label">{$form.start_date.label}</td><td>{$form.start_date.html}</td></tr>
         <tr><td>&nbsp;</td><td>{include file="CRM/common/calendar/desc.tpl" trigger=trigger_event_1 doTime=1}
         {include file="CRM/common/calendar/body.tpl" dateVar=start_date offset=3 doTime=1 trigger=trigger_event_1 ampm=1}</td></tr>

         <tr><td class="label">{$form.end_date.label}</td><td>{$form.end_date.html}</td></tr>
         <tr><td>&nbsp;</td><td>{include file="CRM/common/calendar/desc.tpl" trigger=trigger_event_2 doTime=1}
         {include file="CRM/common/calendar/body.tpl" dateVar=end_date offset=3 doTime=1 trigger=trigger_event_2 ampm=1}</td></tr>

         <tr><td class="label">{$form.item_start_date.label}</td><td>{$form.item_start_date.html}</td></tr>
         <tr><td>&nbsp;</td><td>{include file="CRM/common/calendar/desc.tpl" trigger=trigger_event_3 doTime=1}
         {include file="CRM/common/calendar/body.tpl" dateVar=item_start_date offset=3 doTime=1 trigger=trigger_event_3 ampm=1}</td></tr>

         <tr><td class="label">{$form.item_end_date.label}</td><td>{$form.item_end_date.html}</td></tr>
         <tr><td>&nbsp;</td><td>{include file="CRM/common/calendar/desc.tpl" trigger=trigger_event_4 doTime=1}
         {include file="CRM/common/calendar/body.tpl" dateVar=item_end_date offset=3 doTime=1 trigger=trigger_event_4 ampm=1}</td></tr>

         <tr><td class="label">{$form.max_items.label}</td><td>{$form.max_items.html|crmReplace:class:four}<br />
         <tr><td class="label">{$form.max_items_user.label}</td><td>{$form.max_items_user.html|crmReplace:class:four}<br />

         <tr><td>&nbsp;</td><td>{$form.is_item_approval.html} {$form.is_item_approval.label}<br />
         <tr><td>&nbsp;</td><td>{$form.is_item_groups.html} {$form.is_item_groups.label}<br />
         <tr><td>&nbsp;</td><td>{$form.is_active.html} {$form.is_active.label}</td></tr> 

        <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
    </table>
    <dl>    
       <dt></dt><dd class="html-adjust">{$form.buttons.html}</dd>   
    </dl> 
</fieldset>     
</div>
