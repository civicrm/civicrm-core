{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
{if $outputMode neq 'print'}
{/if}
{* this div is being used to apply special css *}
{if $section eq 1}
  <div class="crm-block crm-content-block crm-report-layoutGraph-form-block">
    {*include the graph*}
    {include file="CRM/Report/Form/Layout/Graph.tpl"}
  </div>
{elseif $section eq 2}
  <div class="crm-block crm-content-block crm-report-layoutTable-form-block">
    {*include the table layout*}
    {include file="CRM/Report/Form/Layout/Table.tpl"}
  </div>
{else}
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

    {*include the graph*}
    {include file="CRM/Report/Form/Layout/Graph.tpl"}

    {*include the table layout*}
    {include file="CRM/Report/Form/Layout/Table.tpl"}
    <br />
    {*Statistics at the bottom of the page*}
    {include file="CRM/Report/Form/Statistics.tpl" bottom=true}

    {include file="CRM/Report/Form/ErrorMessage.tpl"}
  </div>
{/if}
{if $outputMode == 'print'}
  <script type="text/javascript">
    window.print();
  </script>
{/if}
