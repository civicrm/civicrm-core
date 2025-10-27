(function (d3, dc, ts) {

  CRM.chart_kit = CRM.chart_kit || {};

  /**
   * Util functions for chart kit
   *
   * TODO: merge dataPrecision/dateFormat into datePrecision options
   */
  CRM.chart_kit.utils = {
    legacySettingsAdaptor: (settings) => {
      let updated = false;
      // for pie/row charts, x axis was moved to w. if pie/row chart has x
      // columns but no y columns, then transfer them
      if (settings.chartType === 'pie' || settings.chartType === 'row') {
        // if no cols for w axis
        if (!settings.columns.find((col) => col.axis === 'w')) {

          // update any x cols to w
          settings.columns.forEach((col, i) => {
            if (col.axis === 'x') {
              settings.columns[i].axis = 'w';
              updated = true;
            }
          });
        }
      }

      if (settings.chartOrderColIndex) {
        if (!settings.columns[settings.chartOrderColIndex].isOrder) {
          settings.columns[settings.chartOrderColIndex].isOrder = true;
          updated = true;
        }
      }

      if (updated) {
        CRM.alert(ts("Please resave charts in SearchKit to avoid this message"), ts("Deprecated chart settings detected"));
      }
      return settings;
    },
  };

})(CRM.chart_kit.d3, CRM.chart_kit.dc, CRM.ts('chart_kit'));
