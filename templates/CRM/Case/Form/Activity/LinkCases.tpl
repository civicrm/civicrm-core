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
{* Template for to create a link between two cases. *}
   <div class="crm-block crm-form-block crm-case-linkcases-form-block">
    <tr class="crm-case-linkcases-form-block-link_to_case">
  <td class="label">{$form.link_to_case.label}</td>
  <td>{$form.link_to_case.html}</td>
    </tr>

{literal}
<script type="text/javascript">
var unclosedCaseUrl = {/literal}"{crmURL p='civicrm/case/ajax/unclosed' h=0 q='excludeCaseIds='}{$excludeCaseIds}{literal}";
cj( "#link_to_case").autocomplete( unclosedCaseUrl, { width : 250, selectFirst : false, matchContains:true
                            }).result( function(event, data, formatted) {
                   cj( "#link_to_case_id" ).val( data[1] );
             var subject = {/literal}"Create link between {$client_name} - {$caseTypeLabel} (CaseID: {$caseId})"{literal} + ' AND ' + data[4] + ' - ' + data[3] + ' (CaseID: ' + data[1] + ')';
             cj( "#subject" ).val( subject );
                            }).bind( 'click', function( ) {
                   cj( "#link_to_case_id" ).val('');
             cj( "#subject" ).val( '' );
          });
</script>
{/literal}
  </div>