(function (angular, $, _, chartKitChartTypes, chartKitTypeBackends, chartKitColumnOptions, chartKitUtils) {
  "use strict";

  angular.module('crmChartKitAdmin').component('searchAdminDisplayChartKit', {
    bindings: {
      display: '<',
      apiEntity: '<',
      apiParams: '<'
    },
    require: {
      parent: '^crmSearchAdminDisplay',
      crmSearchAdmin: '^crmSearchAdmin'
    },
    templateUrl: '~/crmChartKitAdmin/searchAdminDisplayChartKit.html',
    controller: function ($scope, searchMeta) {
      const ts = $scope.ts = CRM.ts('chart_kit');

      this.getChartTypeOptions = () => chartKitChartTypes;

      this.getInitialDisplaySettings = () => ({
        columns: [],
        format: {
          labelColor: '#000000',
          backgroundColor: '#f2f2ed',
          height: 480,
          width: 700,
          padding: {
            outer: 10,
            clip: 20,
            top: 50,
            bottom: 50,
            left: 50,
            right: 50,
          }
        }
      });

      this.getInitialDisplaySettingsForChartType = () => this.chartType.getInitialDisplaySettings();

      this.getChartTypeAdminTemplate = () => this.chartType.adminTemplate;

      this.searchColumns = [];

      this.$onInit = () => {
        this.searchColumns = this.apiParams.select.map((select) => {
          const info = searchMeta.parseExpr(select);
          const field = (_.findWhere(info.args, {type: 'field'}) || {}).field || {};
          let dataType = (info.fn && info.fn.data_type) || field.data_type;
          // hack: search kit reports option group columns as
          // "Integer" data type - but for our purposes they
          // shouldn't be used for numeric scales
          if (select.includes(':label')) {
            dataType = 'Option';
          }
          return {
            type: 'field',
            key: info.alias,
            dataType: dataType,
            label: searchMeta.getDefaultLabel(select),
          };
        });

        if (!this.display.settings) {
          this.display.settings = {
            chartType: null
          };
        }
        else {
          // run initial settings through our legacy adaptor
          this.display.settings = chartKitUtils.legacySettingsAdaptor(this.display.settings);
        }

        this.chartTypeOptions = this.getChartTypeOptions();

        $scope.$watch('$ctrl.display.settings.chartType', () => this.onSetChartType(), true);
      };

      this.onSetChartType = () => {
        if (!this.display.settings.chartType) {
          return;
        }

        this.initChartType();

        this.initAxesForChartType();

        this.initDisplaySettingsForChartType();
      };

      this.initChartType = () => {
        const type = chartKitChartTypes.find((type) => type.key === this.display.settings.chartType);
        this.chartType = chartKitTypeBackends[type.backend];
      };

      this.initAxesForChartType = () => {
        const axes = this.chartType.getAxes();

        Object.keys(axes).forEach((key) => {
          // merge axis defaults into the axes array
          axes[key] = Object.assign({}, this.axisDefaults, axes[key]);

          // TODO: change config to provide limit directly
          axes[key].maxColumns = axes[key].multiColumn ? -1 : 1;
        });

        this.axes = axes;
      };

      this.getAxes = () => this.axes;

      this.getAxis = (axisKey) => this.getAxes()[axisKey];

      /**
       * @returns Object[] columns, with names set according to their index on their axis
       */
      this.getColumns = () => {
        const countByAxis = {};
        const axes = this.getAxes();

        return this.display.settings.columns.map((col, index) => {
            // add index in the settings.columns array, so we can update values
            col.index = index;

            const axis = axes[col.axis];

            // skip columns with unrecognised axis keys
            if (axis === undefined) {
              return null;
            }

            // check next index for this axis. if we havent seen this axis before, start at 0
            const axisIndex = countByAxis[col.axis] ? countByAxis[col.axis] : 0;

            // if limit is not -1, and next index exceeds the limit for the axis, skip this column
            if ((0 <= axis.maxColumns) && (axis.maxColumns <= axisIndex)) {
              return null;
            }

            col.name = `${col.axis}_${axisIndex}`;

            // increment the counter
            countByAxis[col.axis] = axisIndex + 1;

            return col;
          })
          // remove null/skipped columns
          .filter((col) => col);
      };

      this.getColumnByIndex = (index) => this.getColumns().find((col) => col.index === index);

      this.getColumnsForAxis = (axisKey) => this.getColumns().filter((col) => col.axis === axisKey);

      this.initDisplaySettingsForChartType = () => {
        // TODO: some kind of deep merge so new settings are added to old charts at all levels
        const baseSettings = this.getInitialDisplaySettings();
        const typeSettings = this.getInitialDisplaySettingsForChartType();

        this.display.settings = Object.assign(
          {},
          baseSettings,
          typeSettings,
          this.display.settings
        );

        this.display.settings.format = Object.assign(
          {},
          baseSettings.format,
          typeSettings.format,
          this.display.settings.format
        );

        // populate starting column for each axis
        Object.keys(this.getAxes()).forEach(axisKey => {
          if (!this.getAxis(axisKey).prepopulate) {
            return;
          }
          if (this.getColumns().some((col) => (col.axis === axisKey))) {
            return;
          }
          this.initColumn(axisKey);
        });
      };

      this.axisDefaults = ({
        // by default allow all types we know
        reduceTypes: chartKitColumnOptions.reduceType.map((type) => type.key),
        scaleTypes: chartKitColumnOptions.scaleType.map((type) => type.key),
        dataLabelTypes: chartKitColumnOptions.dataLabelType.map((type) => type.key),
        // by default no option
        seriesTypes: [],
        dataLabelFormatters: chartKitColumnOptions.dataLabelFormatter.map((type) => type.key),
        multiColumn: false,
        prepopulate: true,
      });

      this.getAxisLabel = (axisKey) => {
        return this.getAxis(axisKey).label;
      };

      this.getAxisSourceDataTypes = (axisKey) => {
        return this.getAxis(axisKey).sourceDataTypes;
      };

      this.getAxisScaleTypeOptions = (axisKey) => {
        return this.getAxis(axisKey).scaleTypes;
      };

      this.getAxisReduceTypeOptions = (axisKey) => {
        if (axisKey === 'x') {
          return ['list'];
        }
        return this.getAxis(axisKey).reduceTypes;
      };

      this.getAxisSeriesTypeOptions = (axisKey) => {
        return this.getAxis(axisKey).seriesTypes;
      };

      this.getAxisDataLabelTypeOptions = (axisKey) => {
        return this.getAxis(axisKey).dataLabelTypes;
      };

      this.getAxisDataLabelFormatterOptions = (axisKey) => {
        return this.getAxis(axisKey).dataLabelFormatters;
      };

      this.getColumnSourceDataTypes = (col) => {
        return this.getAxisSourceDataTypes(col.axis);
      };

      this.getColumnSearchColumnOptions = (col) => {
        const allowedTypes = this.getColumnSourceDataTypes(col);

        if (!allowedTypes && allowedTypes != []) {
          // all keys
          return this.searchColumns.map((searchCol) => searchCol.key);
        }

        return this.searchColumns
          .filter((searchCol) => allowedTypes.includes(searchCol.dataType))
          .map((searchCol) => searchCol.key);
      };

      this.getSearchColumn = (key) => {
        return this.searchColumns.find((searchColumn) => (searchColumn.key === key));
      };

      this.getColumnSourceDataType = (col) => {
        const details = this.getSearchColumn(col.key);
        return details ? details.dataType : null;
      };

      this.getColumnSourceDataTypeIsDate = (col) => {
        const dataType = this.getColumnSourceDataType(col);
        return dataType && ['Date', 'Time', 'Timestamp'].includes(dataType);
      };

      this.getColumnScaleTypeOptions = (col) => {
        let options = this.getAxisScaleTypeOptions(col.axis);

        // date is only valid if the column type is date
        if (this.getColumnSourceDataTypeIsDate(col)) {
          options = options.filter((item) => ['date', 'categorical'].includes(item));
        } else if (this.getColumnSourceDataType(col) === 'String') {
          options = options.filter((item) => item === 'categorical');
        } else {
          options = options.filter((item) => item !== 'date');
        }
        // this is a bit hacky, but if option groups can be categorical, they
        // probably should be
        if (col.key && col.key.includes(':label') && options.includes('categorical')) {
          return ['categorical'];
        }
        return options;
      };

      this.getColumnDatePrecisionOptions = (col) => {
        if (this.getColumnSourceDataTypeIsDate(col)) {
          return chartKitColumnOptions.datePrecision.map((option) => option.key);
        }
        return [];
      };

      this.getColumnReduceTypeOptions = (col) => {
        let options = this.getAxisReduceTypeOptions(col.axis);

        switch (col.scaleType) {
          case 'categorical':
          case 'date':
            options = options.filter((item) => ['count', 'list'].includes(item));
            break;
        }

        return options;
      };

      this.getColumnSeriesTypeOptions = (col) => {
        return this.getAxisSeriesTypeOptions(col.axis);
      };

      this.getColumnDataLabelTypeOptions = (col) => {
        return this.getAxisDataLabelTypeOptions(col.axis);
      };

      this.getColumnDataLabelFormatterOptions = (col) => {
        const options = this.getAxisDataLabelFormatterOptions(col.axis);

        // categorical will often be rendered to string, which
        // dont like being formatted
        if (col.scaleType === 'categorical') {
          return ['none', 'round', 'formatMoney'];
        }
        // default to money for money columns
        if (col.sourceDataType === 'Money') {
          return ['formatMoney', 'round', 'none'];
        }

        if (col.scaleType === 'date') {
          // TODO support fancy date formatting?
          return ['none'];
        }

        return options;
      };

      this.onColumnSearchColumnChange = (index) => {
        const col = this.getColumnByIndex(index);

        const selectedFieldDetails = this.getSearchColumn(col.key);
        if (selectedFieldDetails) {
          this.display.settings.columns[index].label = selectedFieldDetails.label;
          this.display.settings.columns[index].sourceDataType = selectedFieldDetails.dataType;
        }

        // check for reduce/data/label types and pick the first if available
        // otherwise set null
        this.getColumnConfigKeys().forEach((configKey) => {
          if (configKey === 'searchColumn') {
            // this is what's just been changed, so dont touch
            return;
          }
          const optionKeys = this.getColumnConfigOptionKeys(col, configKey);
          this.display.settings.columns[index][configKey] = optionKeys.length ? optionKeys[0] : null;
        });
      };

      this.getColumnConfigOptionKeys = (col, configKey) => this.getColumnConfigOptionGetters()[configKey](col);

      this.getColumnConfigOptionDetails = (col, configKey) => this.getColumnConfigOptionKeys(col, configKey)
        .map((optionKey) => this.getOptionDetailsForKey(configKey, optionKey))
        .filter((details) => !!details);

      this.getAllOptionDetails = (configKey) => {
        if (configKey === 'searchColumn') {
          return this.searchColumns;
        }
        return chartKitColumnOptions[configKey];
      };

      this.getOptionDetailsForKey = (configKey, optionKey) => this.getAllOptionDetails(configKey).find((option) => option.key === optionKey);

      this.getColumnConfigOptionGetters = () => ({
        searchColumn: this.getColumnSearchColumnOptions,
        scaleType: this.getColumnScaleTypeOptions,
        datePrecision: this.getColumnDatePrecisionOptions,
        reduceType: this.getColumnReduceTypeOptions,
        seriesType: this.getColumnSeriesTypeOptions,
        dataLabelType: this.getColumnDataLabelTypeOptions,
        dataLabelFormatter: this.getColumnDataLabelFormatterOptions
      });

      this.getColumnConfigKeys = () => Object.keys(this.getColumnConfigOptionGetters());

      this.initColumn = (axisKey) => {
        // initialise new column object
        const newCol = {
          axis: axisKey,
        };

        // check if any search column options and set the first if available
        const alreadyUsedKeys = this.getColumns().map((col) => col.key);
        const searchColumnOptions = this.getColumnSearchColumnOptions(newCol)
          // filter options for data keys already used
          .filter((key) => !alreadyUsedKeys.includes(key));
        newCol.key = searchColumnOptions.length ? searchColumnOptions[0] : null;

        // add to the settings array
        this.display.settings.columns.push(newCol);

        // trigger automatic best option selection
        const index = this.display.settings.columns.length - 1;
        this.onColumnSearchColumnChange(index);
      };

      this.removeColumn = (index) => this.display.settings.columns.splice(index, 1);

      this.getSetOrderColumn = (index) => {

        if (index === undefined) {
          const orderCol = this.getColumns().find((col) => col.isOrder);
          return orderCol ? `${orderCol.index}` : '';
        }

        index = parseInt(index);

        this.display.settings.columns.forEach((col, j) => {
          this.display.settings.columns[j].isOrder = false;
        });
        this.display.settings.columns[index].isOrder = true;
      };

    }
  });
})(angular, CRM.$, CRM._, CRM.chart_kit.chartTypes, CRM.chart_kit.typeBackends, CRM.chart_kit.columnOptions, CRM.chart_kit.utils);
