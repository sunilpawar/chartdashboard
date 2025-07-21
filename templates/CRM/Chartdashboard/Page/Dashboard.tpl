<div id="chart-dashboard-container" class="chart-dashboard">

  <!-- Dashboard Header -->
  <div class="dashboard-header">
    <div class="header-content">
      <h1 class="dashboard-title">{ts}Chart Dashboard{/ts}</h1>
      <div class="dashboard-controls">
        <button id="configure-dashboard" class="btn btn-primary">
          <i class="crm-i fa-cog"></i> {ts}Configure Dashboard{/ts}
        </button>
        {if !empty($settings.enable_export)}
          <button id="export-dashboard" class="btn btn-success">
            <i class="crm-i fa-download"></i> {ts}Export Data{/ts}
          </button>
        {/if}
        <button id="refresh-all" class="btn btn-secondary">
          <i class="crm-i fa-refresh"></i> {ts}Refresh All{/ts}
        </button>
        <select id="global-time-range" class="form-control">
          <option value="">{ts}Individual Time Ranges{/ts}</option>
          {foreach from=$timeRangeOptions key=value item=label}
            <option value="{$value}">{$label}</option>
          {/foreach}
        </select>
      </div>
    </div>
  </div>

  <!-- Dashboard Statistics Summary -->
  <div id="dashboard-summary" class="dashboard-summary">
    <div class="summary-card">
      <div class="summary-icon"><i class="crm-i fa-dollar"></i></div>
      <div class="summary-content">
        <h3 id="total-donations">--</h3>
        <p>{ts}Total Donations{/ts}</p>
      </div>
    </div>
    <div class="summary-card">
      <div class="summary-icon"><i class="crm-i fa-users"></i></div>
      <div class="summary-content">
        <h3 id="total-donors">--</h3>
        <p>{ts}Active Donors{/ts}</p>
      </div>
    </div>
    <div class="summary-card">
      <div class="summary-icon"><i class="crm-i fa-chart-line"></i></div>
      <div class="summary-content">
        <h3 id="avg-donation">--</h3>
        <p>{ts}Average Donation{/ts}</p>
      </div>
    </div>
    <div class="summary-card">
      <div class="summary-icon"><i class="crm-i fa-target"></i></div>
      <div class="summary-content">
        <h3 id="campaign-progress">--</h3>
        <p>{ts}Campaign Progress{/ts}</p>
      </div>
    </div>
  </div>

  <!-- Export Modal -->
  {if !empty($settings.enable_export)}
    <div id="export-modal" class="modal" style="display: none;">
      <div class="modal-content">
        <div class="modal-header">
          <h2>{ts}Export Chart Data{/ts}</h2>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="export-options">
            <div class="export-section">
              <h3>{ts}Select Chart to Export{/ts}</h3>
              <select id="export-chart-select" class="form-control">
                <option value="">{ts}-- Select Chart --{/ts}</option>
              </select>
            </div>

            <div class="export-section">
              <h3>{ts}Export Format{/ts}</h3>
              <div class="format-options">
                <label class="format-option">
                  <input type="radio" name="export_format" value="csv" checked>
                  <i class="crm-i fa-file-csv"></i>
                  <span>{ts}CSV{/ts}</span>
                  <small>{ts}Comma-separated values, Excel compatible{/ts}</small>
                </label>
                <label class="format-option">
                  <input type="radio" name="export_format" value="excel">
                  <i class="crm-i fa-file-excel"></i>
                  <span>{ts}Excel{/ts}</span>
                  <small>{ts}Microsoft Excel format (.xlsx){/ts}</small>
                </label>
                <label class="format-option">
                  <input type="radio" name="export_format" value="pdf">
                  <i class="crm-i fa-file-pdf"></i>
                  <span>{ts}PDF{/ts}</span>
                  <small>{ts}Formatted report for printing{/ts}</small>
                </label>
                <label class="format-option">
                  <input type="radio" name="export_format" value="json">
                  <i class="crm-i fa-file-code"></i>
                  <span>{ts}JSON{/ts}</span>
                  <small>{ts}Raw data for developers{/ts}</small>
                </label>
              </div>
            </div>

            <div class="export-section">
              <h3>{ts}Time Range{/ts}</h3>
              <select id="export-time-range" class="form-control">
                {foreach from=$timeRangeOptions key=value item=label}
                  <option value="{$value}">{$label}</option>
                {/foreach}
              </select>
            </div>

            <div class="export-section">
              <h3>{ts}Additional Options{/ts}</h3>
              <label class="checkbox-option">
                <input type="checkbox" id="include-summary" checked>
                {ts}Include summary statistics{/ts}
              </label>
              <label class="checkbox-option">
                <input type="checkbox" id="include-metadata" checked>
                {ts}Include metadata (export date, time range, etc.){/ts}
              </label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">{ts}Cancel{/ts}</button>
          <button type="button" id="start-export" class="btn btn-success">
            <i class="crm-i fa-download"></i> {ts}Export Data{/ts}
          </button>
        </div>
      </div>
    </div>
  {/if}

  <!-- Chart Configuration Modal -->
  <div id="config-modal" class="modal" style="display: none;">
    <div class="modal-content">
      <div class="modal-header">
        <h2>{ts}Configure Dashboard{/ts}</h2>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="config-section">
          <h3>{ts}Available Charts{/ts}</h3>
          <div id="available-charts" class="chart-list">
            {foreach from=$availableCharts key=chartId item=chart}
              <div class="chart-item" data-chart-id="{$chartId}">
                <div class="chart-info">
                  <h4>{$chart.title}</h4>
                  <p>{$chart.description}</p>
                  <div class="chart-options">
                    <label>{ts}Chart Type:{/ts}</label>
                    <select class="chart-type-select">
                      {foreach from=$chart.chart_types item=type}
                        <option value="{$type}">{$chartTypeOptions.$type}</option>
                      {/foreach}
                    </select>
                    {if $chart.supports_time_range}
                      <label>{ts}Time Range:{/ts}</label>
                      <select class="time-range-select">
                        {foreach from=$timeRangeOptions key=value item=label}
                          <option value="{$value}">{$label}</option>
                        {/foreach}
                      </select>
                    {/if}
                  </div>
                </div>
                <div class="chart-actions">
                  <button class="btn btn-success add-chart">
                    <i class="crm-i fa-plus"></i> {ts}Add{/ts}
                  </button>
                </div>
              </div>
            {/foreach}
          </div>
        </div>

        <div class="config-section">
          <h3>{ts}Dashboard Layout{/ts}</h3>
          <div id="dashboard-layout" class="sortable-charts">
            <!-- Dynamic content populated by JavaScript -->
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">{ts}Cancel{/ts}</button>
        <button type="button" id="save-config" class="btn btn-primary">{ts}Save Configuration{/ts}</button>
      </div>
    </div>
  </div>

  <!-- Main Dashboard Grid -->
  <div id="dashboard-grid" class="dashboard-grid">
    <!-- Charts will be dynamically loaded here -->
  </div>

  <!-- Loading Indicator -->
  <div id="loading-indicator" class="loading-indicator" style="display: none;">
    <div class="spinner"></div>
    <p>{ts}Loading chart data...{/ts}</p>
  </div>

  <!-- Error Message Container -->
  <div id="error-container" class="error-container" style="display: none;">
    <div class="error-content">
      <i class="crm-i fa-exclamation-triangle"></i>
      <h3>{ts}Error Loading Chart Data{/ts}</h3>
      <p id="error-message"></p>
      <button id="retry-load" class="btn btn-primary">{ts}Retry{/ts}</button>
    </div>
  </div>

  <!-- Chart Template (Hidden) -->
  <div id="chart-template" class="chart-container" style="display: none;">
    <div class="chart-header">
      <h3 class="chart-title"></h3>
      <div class="chart-controls">
        <select class="chart-time-range">
          {foreach from=$timeRangeOptions key=value item=label}
            <option value="{$value}">{$label}</option>
          {/foreach}
        </select>
        <button class="chart-refresh btn btn-sm">
          <i class="crm-i fa-refresh"></i>
        </button>
        <button class="chart-config btn btn-sm">
          <i class="crm-i fa-cog"></i>
        </button>
        {if !empty($settings.enable_export)}
          <button class="chart-export btn btn-sm btn-success" title="{ts}Export Chart Data{/ts}">
            <i class="crm-i fa-download"></i>
          </button>
        {/if}
        <button class="chart-remove btn btn-sm btn-danger">
          <i class="crm-i fa-times"></i>
        </button>
      </div>
    </div>
    <div class="chart-content">
      <canvas class="chart-canvas"></canvas>
      <div class="chart-loading" style="display: none;">
        <div class="spinner-small"></div>
        <p>{ts}Loading...{/ts}</p>
      </div>
      <div class="chart-error" style="display: none;">
        <i class="crm-i fa-exclamation-circle"></i>
        <p class="error-text"></p>
        <button class="retry-chart btn btn-sm btn-primary">{ts}Retry{/ts}</button>
      </div>
    </div>
    <div class="chart-footer">
      <div class="chart-metadata">
        <span class="last-updated">{ts}Last updated:{/ts} <span class="update-time">--</span></span>
        <span class="data-points">{ts}Data points:{/ts} <span class="point-count">--</span></span>
      </div>
    </div>
  </div>

</div>

<script type="text/javascript">
  // Initialize dashboard configuration from PHP
  CRM.vars.chartDashboard = CRM.vars.chartDashboard || {literal}{}{/literal};
  CRM.vars.chartDashboard.availableCharts = {$availableCharts|@json_encode};
  CRM.vars.chartDashboard.dashboardConfig = {$dashboardConfig|@json_encode};
  CRM.vars.chartDashboard.timeRangeOptions = {$timeRangeOptions|@json_encode};
  CRM.vars.chartDashboard.chartTypeOptions = {$chartTypeOptions|@json_encode};
</script>
