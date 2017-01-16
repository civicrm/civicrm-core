{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
{if $action ne 2 AND $action ne 8}
{include file="CRM/PCP/Form/Search.tpl"}
{/if}
{if $action eq 8}
{include file="CRM/PCP/Form/PCP/Delete.tpl"}
{/if}
{*approve/reject Personal Campaign Page*}
{literal}
<script type="text/javascript">
CRM.$(function($) {
  $('#crm-container')
    .on('click', 'a.button, a.action-item[title*="Reject Personal Campaign Page"]', function(e) {
       e.preventDefault();
    var $el = $(this).closest('tr');
    CRM.confirm({
      title: ts('{/literal}{ts escape="js"}Reject Campaign Page{/ts}{literal}'),
      message: ts('{/literal}{ts escape="js"}Are you sure you want to revert the Campaign Page?{/ts}{literal}'),
      options: {{/literal}yes: '{ts escape="js"}Yes{/ts}', no: '{ts escape="js"}No{/ts}'{literal}},
      width: 300,
      height: 'auto'
    }).on('crmConfirm:yes', function() {
      CRM.status({/literal}"{ts escape='js'}Saving...{/ts}"{literal}, request);
      var postUrl = {/literal}"{crmURL p='civicrm/ajax/rest' h=0 q='snippet=4&className=CRM_PCP_Page_AJAX&fnName=reject'}"{literal};
      var request = $.post(postUrl, {id : $el.attr('data-id'), action : $el.attr('action'), dataType: 'json',});
      request.done(function(data) {
	if (data.status = "Reverted") {
	  CRM.status({/literal}"{ts escape='js'}Record Reverted{/ts}"{literal}, request);
	  CRM.refreshParent($el);
	} 
      });
    });
  });
  
  $('#crm-container')
    .on('click', 'a.button, a.action-item[title*="Approve Personal Campaign Page"]', function(e) {
       e.preventDefault();
    var $el = $(this).closest('tr');
    CRM.confirm({
      title: ts('{/literal}{ts escape="js"}Approve Campaign Page{/ts}{literal}'),
      message: ts('{/literal}{ts escape="js"}Are you sure you want to approve the Campaign Page?{/ts}{literal}'),
      options: {{/literal}yes: '{ts escape="js"}Yes{/ts}', no: '{ts escape="js"}No{/ts}'{literal}},
      width: 300,
      height: 'auto'
    }).on('crmConfirm:yes', function() {
      CRM.status({/literal}"{ts escape='js'}Saving...{/ts}"{literal}, request);
      var postUrl = {/literal}"{crmURL p='civicrm/ajax/rest' h=0 q='snippet=4&className=CRM_PCP_Page_AJAX&fnName=approve'}"{literal};
      var request = $.post(postUrl, {id : $el.attr('data-id'), action : $el.attr('action'), dataType: 'json',});
      request.done(function(data) {
	if (data.status = "Approved") {
	  CRM.status({/literal}"{ts escape='js'}Record Approved{/ts}"{literal}, request);
	  CRM.refreshParent($el);
	} 
      });
    });
  });

});
</script>
{/literal}
