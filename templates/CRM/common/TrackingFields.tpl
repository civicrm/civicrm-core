{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $trackingFields and ! empty($trackingFields)}
{literal}
<script type="text/javascript">
CRM.$(function($) {
{/literal}
    {foreach from=$trackingFields key=trackingFieldName item=dontCare}
       $("#{$trackingFieldName}").parent().parent().hide( );
    {/foreach}
{literal}
  }
);
</script>
{/literal}
{/if}
