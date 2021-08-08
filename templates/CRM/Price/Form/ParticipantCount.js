    var isAdditionalParticipants = false;
    var pPartiCount = 0;
    var pPartiRef   = Array();
    var optionSep   = '|';

    CRM.$(function($) {
      pricesetParticipantCount();
      allowGroupOnWaitlist(0, pPartiCount);
    });

    function pricesetParticipantCount( ) {

      CRM.$('input','#priceset','select','#priceset').each(function() {

        if (cj(this).attr('price')) {
          var option = JSON.parse(cj(this).attr('price'));
          var addCount = 0;
          switch (cj(this).attr('type')) {
            case 'checkbox':
              ele = option[0];
              optionPart = option[1].split(optionSep);

              if (optionPart[1]) {
                addCount = parseInt(optionPart[1]);
              }

              if (cj(this).prop('checked')) {
                pPartiCount    += addCount;
                pPartiRef[ele] += addCount;
              }

              cj(this).click(function() {
                if (cj(this).prop('checked')) {
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
              ele = option[0];
              optionPart = option[1].split(optionSep);

              if (optionPart[1]) {
                addCount = parseInt(optionPart[1]);
              }
              if (!pPartiRef[ele]) {
                pPartiRef[ele] = 0;
              }

              if(cj(this).prop('checked')) {
                pPartiCount    = parseInt(pPartiCount) + addCount - parseInt(pPartiRef[ele]);
                pPartiRef[ele] = addCount;
              }

              cj(this).click(function() {
                pPartiCount    = parseInt(pPartiCount) + addCount - parseInt(pPartiRef[ele]);
                pPartiRef[ele] = addCount;
                updateWaitingStatus( pPartiCount );
              });
              break;

            case 'text':
              // default calcution of element.
              var textval = parseFloat(cj(this).val());
              if (textval) {
                ele = option[0];
                if (!pPartiRef[ele] ) {
                  pPartiRef[ele] = 0;
                }
                optionPart = option[1].split(optionSep);
                if (optionPart[1])  {
                  addCount = parseInt( optionPart[1] );
                  var curval = textval * addCount;
                  if (textval >= 0) {
                    pPartiCount    = parseInt(pPartiCount) + curval - parseInt(pPartiRef[ele]);
                    pPartiRef[ele] = curval;
                  }
                }
              }

              //event driven calculation of element.
              cj(this).bind('keyup', function() {
                calculateTextCount(this);
              }).bind('blur' , function() {
                calculateTextCount(this);
              });
              break;

            case 'select-one':
              //default calcution of element.
              var ele = cj(this).attr('id');
              if (!pPartiRef[ele] ) {
                pPartiRef[ele] = 0;
              }
              var selectedText = JSON.parse(cj(this).attr('price'));
              if (cj(this).val( )) {
                optionPart = selectedText[cj(this).val( )].split(optionSep);
                if (optionPart[1]) {
                  addcount = parseInt(optionPart[1]);
                }
              }

              if (addcount) {
                pPartiCount = parseInt(pPartiCount) + addcount - parseInt(pPartiRef[ele]);
                pPartiRef[ele] = addcount;
              }

              //event driven calculation of element.
              cj(this).change(function() {
                var ele = cj(this).attr('id');
                if (!pPartiRef[ele] ) {
                  pPartiRef[ele] = 0;
                }
                var selectedText = JSON.parse(cj(this).attr('price'));
                var addcount = 0;

                if (cj(this).val()) {
                  var optionPart = selectedText[cj(this).val()].split(optionSep);
                  if (optionPart[1]) {
                    addcount = parseInt(optionPart[1]);
                  }
                }

                if (addcount) {
                  pPartiCount = parseInt(pPartiCount) + addcount - parseInt(pPartiRef[ele]);
                  pPartiRef[ele] = addcount;
                } else {
                  pPartiCount = parseInt(pPartiCount) - parseInt(pPartiRef[ele]);
                  pPartiRef[ele] = 0;
                }
                updateWaitingStatus(pPartiCount);
              });
              break;
          }
        }
      });
    }

    function calculateTextCount(object) {
      var option = JSON.parse(cj(object).attr('price'));
      ele = option[0];
      if (!pPartiRef[ele]) {
        pPartiRef[ele] = 0;
      }
      var optionPart = option[1].split(optionSep);
      if (optionPart[1]) {
        addCount    = parseInt(optionPart[1]);
        var textval = parseInt(CRM.$(object).val());
        var curval  = textval * addCount;
        if (textval >= 0) {
          pPartiCount    = pPartiCount + curval - parseInt(pPartiRef[ele]);
          pPartiRef[ele] = curval;
        } else {
          pPartiCount    = pPartiCount - parseInt(pPartiRef[ele]);
          pPartiRef[ele] = 0;
        }
        updateWaitingStatus(pPartiCount);
      }
    }

    function updateWaitingStatus(pricesetParticipantCount) {
        allowGroupOnWaitlist(0, pricesetParticipantCount);
    }