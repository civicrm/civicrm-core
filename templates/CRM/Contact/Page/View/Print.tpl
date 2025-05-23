{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
<form action="{crmURL p='civicrm/contact/view' q="cid=`$contactId`&reset=1"}" method="post" id="Print1" >
  <div class="form-item">
       <span class="element-right"><button onclick="window.print(); return false" class="crm-button crm-form-submit default" name="_qf_Print_next" type="submit">{ts}Print{/ts}</button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<button class="crm-button crm-form-submit" name="_qf_Print_back" type="submit">{ts}Done{/ts}</button></span>
  </div>
</form>
<br />
<div class="solid-border-top"><br />
    {include file="CRM/Contact/Page/View/Summary.tpl"}
    <form action="{crmURL p='civicrm/contact/view' q="cid=`$contactId`&reset=1"}" method="post" id="Print2" >
      <div class="form-item">
           <span class="element-right"><input onclick="window.print(); return false" class="crm-form-submit default" name="_qf_Print_next" value="{ts escape='htmlattribute'}Print{/ts}" type="submit" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input class="crm-form-submit" name="_qf_Print_back" value="{ts escape='htmlattribute'}Done{/ts}" type="submit" /></span>
      </div>
    </form>
</div>
{literal}
<script type="text/javascript">
cj('#mainTabContainer').children(':first').remove();

</script>
{/literal}
