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
{* Contact Summary template to print contact information *}
{literal}
<style type="text/css" media="screen, print">
<!--
  #crm-container div {
    font-size: 12px;
  }
-->
</style>
{/literal}
<form action="{crmURL p='civicrm/contact/view' q="&cid=`$contactId`&reset=1"}" method="post" id="Print1" >
  <div class="form-item">
       <span class="element-right"><input onclick="window.print(); return false" class="crm-form-submit default" name="_qf_Print_next" value="{ts}Print{/ts}" type="submit" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input class="crm-form-submit" name="_qf_Print_back" value="{ts}Done{/ts}" type="submit" /></span>
  </div>
</form>
<br />
<div class="solid-border-top"><br />
    {include file="CRM/Contact/Page/View/Summary.tpl"}
    <form action="{crmURL p='civicrm/contact/view' q="&cid=`$contactId`&reset=1"}" method="post" id="Print2" >
      <div class="form-item">
           <span class="element-right"><input onclick="window.print(); return false" class="crm-form-submit default" name="_qf_Print_next" value="{ts}Print{/ts}" type="submit" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input class="crm-form-submit" name="_qf_Print_back" value="{ts}Done{/ts}" type="submit" /></span>
      </div>
    </form>
</div>
{literal}
<script type="text/javascript">
cj('#mainTabContainer').children(':first').remove();
cj('#contact-summary' ).children(':first').remove();

</script>
{/literal}
