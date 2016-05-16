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
{literal}
<script type="text/javascript">
  CRM.$(function($) {
    var $form = $("form.{/literal}{$form.formClass}{literal}");

    // FIXME: This could be much simpler as an entityRef field but pcp doesn't have a searchable api :(
    var pcpURL = CRM.url('civicrm/ajax/rest', 'className=CRM_Contact_Page_AJAX&fnName=getPCPList&json=1&context=contact&reset=1');
    $('input[name=pcp_made_through_id]', $form).crmSelect2({
      minimumInputLength: 1,
      ajax: {
        url: pcpURL,
        data: function(term, page) {
          return {term: term, page_num: page};
        },
        results: function(response) {
          return response;
        }
      },
      initSelection: function(el, callback) {
        callback({id: $(el).val(), text: $('[name=pcp_made_through]', $form).val()});
      }
    })
      // This is just a cheap trick to store the name when the form reloads
      .on('change', function() {
        var fieldNameVal = $(this).select2('data');
        if (!fieldNameVal) {
          fieldNameVal = '';
        }
        $('[name=pcp_made_through]', $form).val(fieldNameVal.text);
      });
  });
</script>
{/literal}
