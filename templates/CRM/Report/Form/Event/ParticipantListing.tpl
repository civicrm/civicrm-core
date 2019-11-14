{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

{literal}
<script type="text/javascript">
  CRM.$(function($) {
    $('#birth_date_from, #birth_date_to').attr({startOffset: '200', endoffset: '0'});
  });
</script>
{/literal}

{include file="CRM/Report/Form.tpl"}
