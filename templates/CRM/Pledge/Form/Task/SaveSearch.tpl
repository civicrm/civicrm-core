{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="form-item">
<fieldset>
    <legend>{ts}Smart Group{/ts}</legend>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
{if $qill[0]}
<div id="search-status">
    <ul>
        {foreach from=$qill item=criteria}
          <li>{$criteria}</li>
        {/foreach}
    </ul>
    <br />
</div>
{/if}
 <div class="form-item">
 <table class="form-layout-compressed">
   <tr class="crm-pledge-form-block-title">
      <td class="label">{$form.title.label}</td>
      <td class="html-adjust">{$form.title.html}</td>
   </tr>
   <tr class="crm-pledge-form-block-description">
      <td class="label">{$form.description.label}</td>
      <td class="html-adjust">{$form.description.html}</td>
   </tr>
</table>    
 <div>
     {include file="CRM/Event/Form/Task.tpl"}
 </div>
 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
 </div>
</fieldset>
</div>

