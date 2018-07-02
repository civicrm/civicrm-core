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
{if $criteriaForm OR $instanceForm OR $instanceFormError}
  <div class="crm-block crm-form-block crm-report-field-form-block">
    {include file="CRM/Report/Form/Fields.tpl"}
  </div>
{/if}

<div class="crm-block crm-content-block crm-report-form-block">
  {*include actions*}
  {include file="CRM/Report/Form/Actions.tpl"}

  {*Statistics at the Top of the page*}
  {include file="CRM/Report/Form/Statistics.tpl" top=true}
  
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
  {include file="CRM/Report/Form/Statistics.tpl" bottom=true}

  {include file="CRM/Report/Form/ErrorMessage.tpl"}
</div>
{if $outputMode == 'print'}
  <script type="text/javascript">
    window.print();
  </script>
{/if}
