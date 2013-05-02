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
<fieldset>
  <dl>
    {foreach from=$locales item=locale}
      {assign var='elem' value="$field $locale"|replace:' ':'_'}
      <dt>{$form.$elem.label}</dt><dd>{$form.$elem.html}</dd>
    {/foreach}
  </dl>
  {if $context == 'dialog'}
    <input type="submit" value="Save"/>
  {else}
    {$form.buttons.html}
  {/if}
</fieldset>
{$form.action}
{literal}
<script type="text/javascript">
var fieldName = "{/literal}{$field}{literal}";
var tsLocale = "{/literal}{$tsLocale}{literal}";
cj('#Form').submit(function() {
      cj(this).ajaxSubmit({
                            beforeSubmit: function (formData, jqForm, options) {
                                                    var queryString = cj.param(formData);
                                                    var postUrl     = cj('#Form').attr('action');
                                                    cj.ajax({
                                                             type   : "POST",
                                                             url    : postUrl,
                                                             async  : false,
                                                             data   : queryString,
                                                             success: function( response ) {
                                    cj('#' + fieldName).val( cj('#' + fieldName +'_' + tsLocale ).val() );
                                                                      cj("#locale-dialog_"+fieldName).dialog("close");
                                                                     }
                                                    });
                                                return false;
                                            }});
          return false;
});
</script>
{/literal}