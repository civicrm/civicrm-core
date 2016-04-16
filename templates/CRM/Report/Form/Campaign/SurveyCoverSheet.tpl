{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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

{/if }


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

     {assign var=resFldCnt value=`$resFldCnt+1`}
     {/foreach}

</table>
</div>
{/if}

