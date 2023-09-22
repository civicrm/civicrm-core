{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this div is being used to apply special css *}
{if $section eq 1}
  <div class="crm-block crm-content-block crm-report-layoutGraph-form-block">
    {*include the graph*}
    {include file="CRM/Report/Form/Layout/Graph.tpl"}
  </div>
{elseif $section eq 2}
  <div class="crm-block crm-content-block crm-report-layoutTable-form-block">
    {*include the table layout*}
    {if !$chartEnabled || empty($chartSupported)}
      {include file="CRM/Report/Form/Layout/Table.tpl"}
    {/if}
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
    {include file="CRM/Report/Form/Statistics.tpl" top=true bottom=false}

    {*include the graph*}
    {include file="CRM/Report/Form/Layout/Graph.tpl"}

    {*include the table layout*}

    {if !$chartEnabled || empty($chartSupported)}
      {include file="CRM/Report/Form/Layout/Table.tpl"}
    {/if}
    <br />
    {*Statistics at the bottom of the page*}
    {include file="CRM/Report/Form/Statistics.tpl" top=false bottom=true}

    {include file="CRM/Report/Form/ErrorMessage.tpl"}
  </div>
{/if}
{if !empty($outputMode) && $outputMode == 'print'}
  <script type="text/javascript">
    window.print();
  </script>
{/if}
