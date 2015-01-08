{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
{literal}
<script type="text/javascript">
cj( function( ) {
    var url       = "{/literal}{$customUrls.$element_name}{literal}";
    var custom    = "{/literal}#{$element_name|replace:']':''|replace:'[':'_'}{literal}";
    var custom_id = "{/literal}input[name=\"{$element_name|cat:'_id'|regex_replace:'/\]_id$/':'_id]'}\"]{literal}";

    var customObj   = cj(custom);
    var customIdObj = cj(custom_id);

    if ( !customObj.hasClass('ac_input') ) {
        customObj.autocomplete( url,
            { width : 250, selectFirst : false, matchContains: true, max: {/literal}{crmSetting name="search_autocomplete_count" group="Search Preferences"}{literal}
            }).result(
                function(event, data ) {
                    customIdObj.val( data[1] );
                }
        );
        customObj.click( function( ) {
            customIdObj.val('');
      });
     }
});
</script>
{/literal}
