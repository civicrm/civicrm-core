{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

{if $priceSet && $allowGroupOnWaitlist}
{literal}
<script type="text/javascript">
    var isAdditionalParticipants = false;
    var pPartiCount = 0;
    var pPartiRef   = Array( );
    var optionSep   = '|';

    CRM.$(function($) {
      pricesetParticipantCount( );
      allowGroupOnWaitlist(0, pPartiCount);
    });

    function pricesetParticipantCount( ) {

      cj("input,#priceset select,#priceset").each(function () {

        if ( cj(this).attr('price') ) {
            switch( cj(this).attr('type') ) {
              case 'checkbox':
            eval( 'var option = ' + cj(this).attr('price') ) ;
       ele        = option[0];
                optionPart = option[1].split(optionSep);
      var addCount = 0;

            if ( optionPart[1] ) {
                addCount = parseInt(optionPart[1]);
      }

       if( cj(this).prop('checked') ) {
              pPartiCount    += addCount;
              pPartiRef[ele] += addCount;
          }

       cj(this).click( function(){
        if( cj(this).prop('checked') ) {
                pPartiCount    += addCount;
                pPartiRef[ele] += addCount;
            } else {
           pPartiCount    -= addCount;
                pPartiRef[ele] -= addCount;
        }
       updateWaitingStatus( pPartiCount );
      });
                  break;

              case 'radio':
            //default calcution of element.
          eval( 'var option = ' + cj(this).attr('price') );
          ele = option[0];
          optionPart = option[1].split(optionSep);
      var addCount = 0;

            if ( optionPart[1] ) {
                addCount = parseInt(optionPart[1]);
      }
          if ( ! pPartiRef[ele] ) {
              pPartiRef[ele] = 0;
          }

            if( cj(this).prop('checked') ) {
                    pPartiCount    = parseInt(pPartiCount) + addCount - parseInt(pPartiRef[ele]);
                    pPartiRef[ele] = addCount;
                  }

                  cj(this).click( function(){
                        pPartiCount = parseInt(pPartiCount) + addCount - parseInt(pPartiRef[ele]);
            pPartiRef[ele] = addCount;
      updateWaitingStatus( pPartiCount );
          })

      break;

         case 'text':
            // default calcution of element.
             var textval = parseFloat( cj(this).val() );
      var addCount = 0;
          if ( textval ) {
              eval( 'var option = '+ cj(this).attr('price') );
              ele = option[0];
              if ( ! pPartiRef[ele] ) {
                pPartiRef[ele] = 0;
        }
                   optionPart = option[1].split(optionSep);
       if ( optionPart[1] )  {
          addCount   = parseInt( optionPart[1] );
          var curval  = textval * addCount;
          if ( textval >= 0 ) {
           pPartiCount    = parseInt(pPartiCount) + curval - parseInt(pPartiRef[ele]);
           pPartiRef[ele] = curval;
               }
       }
                 }

        //event driven calculation of element.
         cj(this).bind( 'keyup', function() { calculateTextCount( this );
       }).bind( 'blur' , function() {  calculateTextCount( this );
         });

                  break;

        case 'select-one':

                 //default calcution of element.
         var ele = cj(this).attr('id');
           if ( ! pPartiRef[ele] ) {
       pPartiRef[ele] = 0;
           }
     var addcount = 0;
           eval( 'var selectedText = ' + cj(this).attr('price') );
           if ( cj(this).val( ) ) {
             optionPart   = selectedText[cj(this).val( )].split(optionSep);
       if ( optionPart[1] ) {
               addcount = parseInt( optionPart[1] );
       }
           }

           if ( addcount ) {
       pPartiCount = parseInt(pPartiCount) + addcount - parseInt(pPartiRef[ele]);
       pPartiRef[ele] = addcount;
         }

           //event driven calculation of element.
               cj(this).change( function() {
                 var ele = cj(this).attr('id');
                 if ( ! pPartiRef[ele] ) {
             pPartiRef[ele] = 0;
                 }
           eval( 'var selectedText = ' + cj(this).attr('price') );
     var addcount = 0;

     if ( cj(this).val( ) ) {
       var optionPart   = selectedText[cj(this).val( )].split(optionSep);
       if ( optionPart[1] ) {
         addcount = parseInt( optionPart[1] );
       }
     }

           if ( addcount ) {
       pPartiCount = parseInt(pPartiCount) + addcount - parseInt(pPartiRef[ele]);
       pPartiRef[ele] = addcount;
                 } else {
       pPartiCount    = parseInt(pPartiCount) - parseInt(pPartiRef[ele]);
       pPartiRef[ele] = 0;
           }
     updateWaitingStatus( pPartiCount );
     });
          break;
            }
        }
      });
    }

    function calculateTextCount( object ) {
        eval( 'var option = ' + cj(object).attr('price') );
        ele = option[0];
        if ( ! pPartiRef[ele] ) {
        pPartiRef[ele] = 0;
    }
    var optionPart = option[1].split(optionSep);
  if ( optionPart[1] ) {
      addCount    = parseInt( optionPart[1] );
      var textval = parseInt( cj(object).attr('value') );
      var curval  = textval * addCount;
        if ( textval >= 0 ) {
      pPartiCount    = pPartiCount + curval - parseInt(pPartiRef[ele]);
      pPartiRef[ele] = curval;
      } else {
      pPartiCount    = pPartiCount - parseInt(pPartiRef[ele]);
      pPartiRef[ele] = 0;
      }
      updateWaitingStatus( pPartiCount );
  }
    }

    function updateWaitingStatus( pricesetParticipantCount ) {
        allowGroupOnWaitlist( 0, pricesetParticipantCount );
    }
</script>
{/literal}
{/if}
