{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Initialize jQuery validate on a form *}
{* Extra params and functions may be added to the CRM.validate object before this template is loaded *}
{if empty($crm_form_validate_included) && $snippet_type neq 'json' && !empty($form) && !empty($form.formClass)}
  {assign var=crm_form_validate_included value=1}
  {literal}
  <script type="text/javascript">
    CRM.$(function($) {
      $("form.{/literal}{$form.formClass}{literal}").crmValidate();
    });
  </script>
  {/literal}
{/if}
