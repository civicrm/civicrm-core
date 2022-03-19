{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $criteriaForm OR $instanceForm OR $instanceFormError}
  <div class="crm-block crm-form-block crm-report-field-form-block">
    {include file="CRM/Report/Form/Fields.tpl"}
  </div>
{/if}

<div class="crm-block crm-content-block crm-report-form-block">
  {*include actions*}
  {include file="CRM/Report/Form/Actions.tpl"}

  {*Statistics at the Top of the page*}
  {include file="CRM/Report/Form/Statistics.tpl" top=true bottom=false}

<table class="report-layout display">
   {foreach from=$rows item=row}
     <thead><th colspan=16><font color="black" size="3">{$row.label}</font></th></thead>

     <thead class="sticky">
     <tr>
       {foreach from=$columnHeaders item=label key=header}
         <th>{$label.title}</th>
       {/foreach}
     </tr>
     </thead>
     {foreach from=$row.rows item=innerRow key=rowid}
     <tr>
       {foreach from=$columnHeaders item=ignore key=header}
         <td>{$innerRow.$header}</td>
       {/foreach}
     {/foreach}
     </tr>
   {/foreach}
</table>

  <br />
  {*Statistics at the bottom of the page*}
  {include file="CRM/Report/Form/Statistics.tpl" top=false bottom=true}

  {include file="CRM/Report/Form/ErrorMessage.tpl"}
</div>
{if $outputMode == 'print'}
  <script type="text/javascript">
    window.print();
  </script>
{/if}
