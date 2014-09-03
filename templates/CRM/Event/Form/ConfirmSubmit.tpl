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
  <div id="dialog" style="display:none;">
      Would you like to change this event only, or this and following events in series?<br/><br/>
      <div style="display: inline-block">
          <div style="display:inline-block;width:100%;">
              <div style="width:30%;float:left;">
                  <button class="dialog-button only-this-event">Only this Event</button>
              </div>
              <div style="width:70%;float:left;">All other events in the series will remain same</div></div>
          <div style="display:inline-block;width:100%;">
              <div style="width:30%;float:left;">
                  <button class="dialog-button this-and-all-following-event">This and Following Events</button>
              </div>
              <div style="width:70%;float:left;">This and all the following events will be changed</div>
          </div>
      </div>
  </div>
  <input type="hidden" value="" name="isRepeatingEvent" id="is-repeating-event"/>
{literal}
    <style type="text/css">
        .dialog-button{
          background: #f5f5f5;
          background-image: -webkit-linear-gradient(top,#f5f5f5,#f1f1f1);
          border: 1px solid rgba(0,0,0,0.1);
          padding: 5px 8px;
          text-align: center;
          border-radius: 2px;
          cursor: pointer;  
          font-size: 11px !important;
        }
    </style>
{/literal}
{*{foreach from=$form.buttons item=button key=key name=btns}
    {if $key|substring:0:4 EQ '_qf_'}
    {/if}
{/foreach}*}
{if $isRepeat eq 'repeat'}
    {literal}
        <script type="text/javascript">
        cj(document).ready(function() {
           cj("#dialog").dialog({ autoOpen: false });
            cj('div.crm-submit-buttons span.crm-button input[value="Save"], div.crm-submit-buttons span.crm-button input[value="Save and Done"]').click( function () {
                cj("#dialog").dialog('open');
                cj("#dialog").dialog({
                    title: 'Save recurring event',
                    width: '650',
                    position: 'center',
                    //draggable: false,
                    buttons: {
                        Cancel: function() { //cancel
                            cj( this ).dialog( "close" );
                        }
                    }
                });
                return false;
            });
            cj(".this-and-all-following-event").click(function(){
                var eventID ={/literal}{$id}{literal};
                if(eventID != ""){
                    var ajaxurl = CRM.url("civicrm/ajax/recurringEntity/update_cascade_type");
                    var data    = {cascadeType: 2, entityId: eventID};
                    cj.ajax({
                      dataType: "json",
                      data: data,
                      url:  ajaxurl,
                      success: function (result) {
                          cj("#dialog").dialog('close');
                      }
                    });
                }
            });
            cj(".only-this-event").click(function(){
                cj("#dialog").dialog('close');
                cj("form").submit();
            });
        });
        </script>
    {/literal}
{/if}
