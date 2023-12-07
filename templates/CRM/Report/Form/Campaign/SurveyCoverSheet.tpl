{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

{* Cover sheet for walklist survey *}

<h2>{ts 1=$reportTitle}Cover Sheet for %1{/ts}</h2>

{* print survey result set option value and label *}
{if $surveyResultFields}

<h4>{ts}Result Set{/ts}</h4>
<div id='survey_result_fields'>
<table class="report-layout">
  {foreach from=$surveyResultFields key=surveyId item=result}
      <tr><th scope="row">{ts 1=$result.title}Survey Title = %1{/ts}</th></tr>
      <tr><td>
          <div id='survey_result_fields_options'>
          <table class="report-layout">
      {foreach from=$result.options key=value item=label}
         <tr><td>{$value} = {$label}</td></tr>
      {/foreach}
    </table>
    </div>
          </td>
      </tr>

  {/foreach}
</table>
</div>

{/if}


{* print survey response set option value and label *}
{if $surveyResponseFields}

<h4>{ts}Response Codes{/ts}</h4>
<div id='survey_response_fields'>
<table class="report-layout">

     {assign var=resFldCnt value=1}
     {foreach from=$surveyResponseFields key=id item=responseField}

        <tr><th>{ts 1=$resFldCnt 2=$responseField.title}Q%1 = %2{/ts}</th></tr>

   {if $responseField.options}
   <tr><td>
              <div id='survey_response_fields_codes'>
        <table class="report-layout">
          {foreach from=$responseField.options key=value item=label}
             <tr><td>{$value} = {$label}</td></tr>
          {/foreach}
        </table>
        </div>
       </td>
         </tr>
   {/if}

   {* clean separation of each response question *}
   <tr><td><br /></td></tr>

     {assign var=resFldCnt value=$resFldCnt+1}
     {/foreach}

</table>
</div>
{/if}

