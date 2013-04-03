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
{literal}
<script type="text/javascript">
  cj( function() {
    //bind the click event for action icon
    cj('.action-icon').click( function( ) {
      copyFieldValues( cj(this).attr('fname') );
    });
  });

  /**
   * This function use to copy fieldsi
   *
   * @param fname string field name
   * @return void
   */
  function copyFieldValues( fname ) {
    // this is the most common pattern for elements, so first check if it exits
    // this check field starting with "field[" and contains [fname] and is not
    // hidden ( for checkbox hidden element is created )
    var elementId    = cj('.crm-copy-fields [name^="field["][name*="[' + fname +']"][type!=hidden]');

    // get the first element and it's value
    var firstElement = elementId.eq(0);
    var firstElementValue = firstElement.val();

    //console.log( elementId );
    //console.log( firstElement );
    //console.log( firstElementValue );

    //check if it is date element
    var isDateElement     = elementId.attr('format');

    // check if it is wysiwyg element
    var editor = elementId.attr('editor');

    //get the element type
    var elementType       = elementId.attr('type');

    // set the value for all the elements, elements needs to be handled are
    // select, checkbox, radio, date fields, text, textarea, multi-select
    // wysiwyg editor, advanced multi-select ( to do )
    if ( elementType == 'radio' ) {
      firstElementValue = elementId.filter(':checked').eq(0).val();
      elementId.filter("[value=" + firstElementValue + "]").prop("checked",true).change();
    }
    else if ( elementType == 'checkbox' ) {
      // handle checkbox
      // get the entity id of first element
      var firstEntityId = cj('.crm-copy-fields > tbody > tr');

      if ( firstEntityId.length == 0 ) {
        firstEntityId = firstElement.closest('div.crm-grid-row');
      }

      firstEntityId = firstEntityId.attr('entity_id');

      var firstCheckElement = cj('.crm-copy-fields [type=checkbox][name^="field['+ firstEntityId +']['+ fname +']"][type!=hidden]');

      if ( firstCheckElement.length > 1 ) {
        // lets uncheck all the checkbox except first one
        cj('.crm-copy-fields [type=checkbox][name^="field["][name*="[' + fname +']"][type=checkbox]:not([name^="field['+ firstEntityId +']['+ fname +']["])').removeAttr('checked');

        //here for each checkbox for first row, check if it is checked and set remaining checkboxes
        firstCheckElement.each(function() {
          if (cj(this).prop('checked') ) {
            var elementName = cj(this).attr('name');
            var correctIndex = elementName.split('field['+ firstEntityId +']['+ fname +'][');
            correctIndexValue = correctIndex[1].replace(']', '');
            cj('.crm-copy-fields [type=checkbox][name^="field["][name*="['+ fname +']['+ correctIndexValue+']"][type!=hidden]').attr('checked',true).change();
          }
        });
      }
      else {
        if ( firstCheckElement.prop('checked') ) {
          cj('.crm-copy-fields [type=checkbox][name^="field["][name*="['+ fname +']"][type!=hidden]').attr('checked',true).change();
        }
        else {
          cj('.crm-copy-fields [type=checkbox][name^="field["][name*="['+ fname +']"][type!=hidden]').removeAttr('checked').change();
        }
      }
    }
    else if ( editor ) {
        var firstElementId = firstElement.attr('id');
        switch ( editor ) {
          case 'ckeditor':
            //get the content of first element
            oEditor = CKEDITOR.instances[firstElementId];
            var htmlContent = oEditor.getData( );

            // copy first element content to all the elements
            elementId.each( function() {
              var elemtId = cj(this).attr('id');
              oEditor = CKEDITOR.instances[elemtId];
              oEditor.setData( htmlContent );
            });
            break;
          case 'tinymce':
            //get the content of first element
            var htmlContent = tinyMCE.get( firstElementId ).getContent();

            // copy first element content to all the elements
            elementId.each( function() {
              var elemtId = cj(this).attr('id');
              tinyMCE.get( elemtId ).setContent( htmlContent );
            });
            break;
          case 'joomlaeditor':
          // TO DO
          case 'drupalwysiwyg':
          // TO DO
          default:
            elementId.val( firstElementValue ).change();

        }
      }
      else {
        if (elementId.is('select') === true && firstElement.parent().find(':input').select().index() >= 1) {
          // its a multiselect case       
          firstElement.parent().find(':input').select().each( function(count) {
            var firstElementValue = cj(this).val();
            var elementId = cj('.crm-copy-fields [name^="field["][name*="[' + fname +'][' + count + '"][type!=hidden]');
            elementId.val(firstElementValue).not(":first").change();
          });
        }
        else {
          elementId.val(firstElementValue).change();
        }
      }

    // since we use different display field for date we also need to set it.
    // also check for date time field and set the value correctly
    if ( isDateElement ) {
      copyValuesDate( fname );
    }
  }

  /**
   * Special function to handle setting values for date fields
   *
   * @param fname string field name
   * @return void
   */
  function copyValuesDate(fname) {
    var fnameDisplay = fname + '_display';
    var fnameTime    = fname + '_time';

    var displayElement = cj('.crm-copy-fields [name^="field_"][name$="_' + fnameDisplay +'"][type!=hidden]');
    var timeElement    = cj('.crm-copy-fields [name^="field["][name*="[' + fnameTime +']"][type!=hidden]');

    displayElement.val( displayElement.eq(0).val() );
    timeElement.val( timeElement.eq(0).val() );
  }

</script>
{/literal}
