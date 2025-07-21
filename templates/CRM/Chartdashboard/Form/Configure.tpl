<div class="crm-container">
  <div class="crm-block crm-form-block crm-chartdashboard-configure-form-block">

    <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="top"}
    </div>

    <!-- Global Dashboard Settings -->
    <div class="crm-accordion-wrapper">
      <div class="crm-accordion-header">
        <i class="crm-i fa-cog"></i> {ts}Global Dashboard Settings{/ts}
      </div>
      <div class="crm-accordion-body">
        <table class="form-layout-compressed">
          <tr class="crm-chartdashboard-configure-form-block-enable_auto_refresh">
            <td class="label">{$form.enable_auto_refresh.label}</td>
            <td>{$form.enable_auto_refresh.html}
              <div class="description">{ts}Automatically refresh charts at regular intervals{/ts}</div>
            </td>
          </tr>
          <tr class="crm-chartdashboard-configure-form-block-refresh_interval">
            <td class="label">{$form.refresh_interval.label}</td>
            <td>{$form.refresh_interval.html}
              <div class="description">{ts}How often to refresh chart data when auto-refresh is enabled{/ts}</div>
            </td>
          </tr>
          <tr class="crm-chartdashboard-configure-form-block-enable_export">
            <td class="label">{$form.enable_export.label}</td>
            <td>{$form.enable_export.html}
              <div class="description">{ts}Allow users to export chart data to CSV/Excel{/ts}</div>
            </td>
          </tr>
          <tr class="crm-chartdashboard-configure-form-block-default_time_range">
            <td class="label">{$form.default_time_range.label}</td>
            <td>{$form.default_time_range.html}
              <div class="description">{ts}Default time range for new charts{/ts}</div>
            </td>
          </tr>
        </table>
      </div>
    </div>

    <!-- Chart Configuration -->
    <div class="crm-accordion-wrapper">
      <div class="crm-accordion-header">
        <i class="crm-i fa-chart-bar"></i> {ts}Chart Configuration{/ts}
      </div>
      <div class="crm-accordion-body">
        <p class="description">{ts}Configure which charts are available and their default settings{/ts}</p>

        <div class="chart-config-grid">
          {foreach from=$availableCharts key=chartId item=chart}
            <div class="chart-config-item">
              <div class="chart-config-header">
                <h4>{$chart.title}</h4>
                <div class="chart-enable">
                  {assign var="enableField" value="enable_chart_`$chartId`"}
                  {$form.$enableField.html} {$form.$enableField.label}
                </div>
              </div>

              <div class="chart-config-body">
                <p class="chart-description">{$chart.description}</p>

                <div class="chart-config-options">
                  <div class="config-option">
                    {assign var="chartTypeField" value="default_chart_type_`$chartId`"}
                    <label>{$form.$chartTypeField.label}</label>
                    {$form.$chartTypeField.html}
                  </div>

                  {if $chart.supports_time_range}
                    <div class="config-option">
                      {assign var="timeRangeField" value="default_time_range_`$chartId`"}
                      <label>{$form.$timeRangeField.label}</label>
                      {$form.$timeRangeField.html}
                    </div>
                  {/if}
                </div>
              </div>
            </div>
          {/foreach}
        </div>
      </div>
    </div>

    <!-- Performance Settings -->
    <div class="crm-accordion-wrapper">
      <div class="crm-accordion-header">
        <i class="crm-i fa-tachometer-alt"></i> {ts}Performance Settings{/ts}
      </div>
      <div class="crm-accordion-body">
        <table class="form-layout-compressed">
          <tr class="crm-chartdashboard-configure-form-block-api_timeout">
            <td class="label">{$form.api_timeout.label}</td>
            <td>{$form.api_timeout.html}
              <div class="description">{ts}Maximum time to wait for API responses (minimum 5 seconds){/ts}</div>
            </td>
          </tr>
          <tr class="crm-chartdashboard-configure-form-block-max_data_points">
            <td class="label">{$form.max_data_points.label}</td>
            <td>{$form.max_data_points.html}
              <div class="description">{ts}Maximum number of data points to display in charts (affects performance){/ts}</div>
            </td>
          </tr>
          <tr class="crm-chartdashboard-configure-form-block-enable_caching">
            <td class="label">{$form.enable_caching.label}</td>
            <td>{$form.enable_caching.html}
              <div class="description">{ts}Cache chart data to improve performance{/ts}</div>
            </td>
          </tr>
          <tr class="crm-chartdashboard-configure-form-block-cache_duration">
            <td class="label">{$form.cache_duration.label}</td>
            <td>{$form.cache_duration.html}
              <div class="description">{ts}How long to cache data (in minutes){/ts}</div>
            </td>
          </tr>
        </table>
      </div>
    </div>

    <!-- Access Control -->
    <div class="crm-accordion-wrapper">
      <div class="crm-accordion-header">
        <i class="crm-i fa-lock"></i> {ts}Access Control{/ts}
      </div>
      <div class="crm-accordion-body">
        <table class="form-layout-compressed">
          <tr class="crm-chartdashboard-configure-form-block-dashboard_permissions">
            <td class="label">{$form.dashboard_permissions.label}</td>
            <td>{$form.dashboard_permissions.html}
              <div class="description">{ts}Who can access the chart dashboard (hold Ctrl/Cmd to select multiple){/ts}</div>
            </td>
          </tr>
        </table>
      </div>
    </div>

    <!-- Email Alerts -->
    <div class="crm-accordion-wrapper">
      <div class="crm-accordion-header">
        <i class="crm-i fa-bell"></i> {ts}Email Alerts{/ts}
      </div>
      <div class="crm-accordion-body">
        <table class="form-layout-compressed">
          <tr class="crm-chartdashboard-configure-form-block-enable_alerts">
            <td class="label">{$form.enable_alerts.label}</td>
            <td>{$form.enable_alerts.html}
              <div class="description">{ts}Send email alerts for significant donation events{/ts}</div>
            </td>
          </tr>
          <tr class="crm-chartdashboard-configure-form-block-alert_email">
            <td class="label">{$form.alert_email.label}</td>
            <td>{$form.alert_email.html}
              <div class="description">{ts}Email address to receive alerts{/ts}</div>
            </td>
          </tr>
          <tr class="crm-chartdashboard-configure-form-block-low_donation_threshold">
            <td class="label">{$form.low_donation_threshold.label}</td>
            <td>{$form.low_donation_threshold.html}
              <div class="description">{ts}Send alert when daily donations fall below this amount{/ts}</div>
            </td>
          </tr>
          <tr class="crm-chartdashboard-configure-form-block-goal_achievement_threshold">
            <td class="label">{$form.goal_achievement_threshold.label}</td>
            <td>{$form.goal_achievement_threshold.html}
              <div class="description">{ts}Send alert when campaign reaches this percentage of goal{/ts}</div>
            </td>
          </tr>
        </table>
      </div>
    </div>

    <!-- Color Scheme -->
    <div class="crm-accordion-wrapper">
      <div class="crm-accordion-header">
        <i class="crm-i fa-palette"></i> {ts}Color Scheme{/ts}
      </div>
      <div class="crm-accordion-body">
        <table class="form-layout-compressed">
          <tr class="crm-chartdashboard-configure-form-block-color_scheme">
            <td class="label">{$form.color_scheme.label}</td>
            <td>{$form.color_scheme.html}
              <div class="description">{ts}Choose a predefined color scheme or create custom colors{/ts}</div>
            </td>
          </tr>
          <tr class="crm-chartdashboard-configure-form-block-custom-colors" style="display: none;">
            <td colspan="2">
              <table class="custom-colors-table">
                <tr>
                  <td class="label">{$form.primary_color.label}</td>
                  <td>{$form.primary_color.html}</td>
                  <td class="label">{$form.secondary_color.label}</td>
                  <td>{$form.secondary_color.html}</td>
                  <td class="label">{$form.accent_color.label}</td>
                  <td>{$form.accent_color.html}</td>
                </tr>
              </table>
              <div class="description">{ts}Custom colors for charts and dashboard elements{/ts}</div>
            </td>
          </tr>
        </table>

        <!-- Color Preview -->
        <div class="color-preview" style="margin-top: 15px;">
          <h4>{ts}Color Preview{/ts}</h4>
          <div class="preview-squares">
            <div class="color-square primary" title="{ts}Primary Color{/ts}"></div>
            <div class="color-square secondary" title="{ts}Secondary Color{/ts}"></div>
            <div class="color-square accent" title="{ts}Accent Color{/ts}"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>

  </div>
</div>

<!-- JavaScript for form interactions -->
<script type="text/javascript">
  {literal}
  CRM.$(function($) {
    // Show/hide custom colors based on color scheme selection
    $('#color_scheme').on('change', function() {
      var customRow = $('.crm-chartdashboard-configure-form-block-custom-colors');
      if ($(this).val() === 'custom') {
        customRow.show();
      } else {
        customRow.hide();
      }
      updateColorPreview();
    });

    // Update color preview when colors change
    $('input[type="color"]').on('change', updateColorPreview);
    $('#color_scheme').on('change', updateColorPreview);

    // Enable/disable chart options based on chart enable checkbox
    $('input[name^="enable_chart_"]').on('change', function() {
      var chartId = $(this).attr('name').replace('enable_chart_', '');
      var configBody = $(this).closest('.chart-config-item').find('.chart-config-body');

      if ($(this).is(':checked')) {
        configBody.find('select').prop('disabled', false).removeClass('disabled');
        configBody.removeClass('disabled');
      } else {
        configBody.find('select').prop('disabled', true).addClass('disabled');
        configBody.addClass('disabled');
      }
    });

    // Initialize state
    $('#color_scheme').trigger('change');
    $('input[name^="enable_chart_"]').trigger('change');

    // Color preview update function
    function updateColorPreview() {
      var scheme = $('#color_scheme').val();
      var colors = getColorScheme(scheme);

      $('.color-square.primary').css('background-color', colors.primary);
      $('.color-square.secondary').css('background-color', colors.secondary);
      $('.color-square.accent').css('background-color', colors.accent);
    }

    // Get colors for scheme
    function getColorScheme(scheme) {
      var schemes = {
        'default': { primary: '#667eea', secondary: '#764ba2', accent: '#f5576c' },
        'blue': { primary: '#4facfe', secondary: '#00f2fe', accent: '#2196f3' },
        'green': { primary: '#43e97b', secondary: '#38f9d7', accent: '#4caf50' },
        'purple': { primary: '#667eea', secondary: '#764ba2', accent: '#9c27b0' },
        'orange': { primary: '#ff9a9e', secondary: '#fecfef', accent: '#ff5722' },
        'custom': {
          primary: $('#primary_color').val() || '#667eea',
          secondary: $('#secondary_color').val() || '#764ba2',
          accent: $('#accent_color').val() || '#f5576c'
        }
      };

      return schemes[scheme] || schemes['default'];
    }

    // Auto-refresh interval dependency
    $('#enable_auto_refresh').on('change', function() {
      var refreshRow = $('.crm-chartdashboard-configure-form-block-refresh_interval');
      if ($(this).is(':checked')) {
        refreshRow.show();
        $('#refresh_interval').prop('disabled', false);
      } else {
        refreshRow.hide();
        $('#refresh_interval').prop('disabled', true);
      }
    }).trigger('change');

    // Caching dependency
    $('#enable_caching').on('change', function() {
      var cacheRow = $('.crm-chartdashboard-configure-form-block-cache_duration');
      if ($(this).is(':checked')) {
        cacheRow.show();
        $('#cache_duration').prop('disabled', false);
      } else {
        cacheRow.hide();
        $('#cache_duration').prop('disabled', true);
      }
    }).trigger('change');

    // Email alerts dependency
    $('#enable_alerts').on('change', function() {
      var alertRows = [
        '.crm-chartdashboard-configure-form-block-alert_email',
        '.crm-chartdashboard-configure-form-block-low_donation_threshold',
        '.crm-chartdashboard-configure-form-block-goal_achievement_threshold'
      ];

      if ($(this).is(':checked')) {
        $.each(alertRows, function(i, row) {
          $(row).show();
          $(row).find('input').prop('disabled', false);
        });
      } else {
        $.each(alertRows, function(i, row) {
          $(row).hide();
          $(row).find('input').prop('disabled', true);
        });
      }
    }).trigger('change');
  });
  {/literal}
</script>

<!-- CSS for configuration form -->
<style type="text/css">
  {literal}
  .chart-config-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-top: 15px;
  }

  .chart-config-item {
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    background: #f9f9f9;
  }

  .chart-config-header {
    background: #667eea;
    color: white;
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .chart-config-header h4 {
    margin: 0;
    font-size: 1.1em;
  }

  .chart-enable {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .chart-config-body {
    padding: 15px;
    transition: opacity 0.3s ease;
  }

  .chart-config-body.disabled {
    opacity: 0.5;
  }

  .chart-description {
    margin: 0 0 15px 0;
    color: #666;
    font-size: 0.9em;
  }

  .chart-config-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
  }

  .config-option label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
    color: #333;
  }

  .config-option select {
    width: 100%;
    padding: 6px 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
  }

  .config-option select.disabled {
    background: #f5f5f5;
    color: #999;
  }

  .custom-colors-table {
    width: 100%;
  }

  .custom-colors-table td {
    padding: 5px 10px;
    vertical-align: middle;
  }

  .custom-colors-table input[type="color"] {
    width: 60px;
    height: 30px;
    border: 1px solid #ccc;
    border-radius: 4px;
    cursor: pointer;
  }

  .color-preview {
    background: white;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 15px;
  }

  .preview-squares {
    display: flex;
    gap: 15px;
    margin-top: 10px;
  }

  .color-square {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    border: 2px solid #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    cursor: help;
    transition: transform 0.2s ease;
  }

  .color-square:hover {
    transform: scale(1.1);
  }

  .crm-accordion-wrapper {
    margin-bottom: 20px;
  }

  .crm-accordion-header {
    background: #f5f5f5;
    border: 1px solid #ddd;
    padding: 12px 15px;
    cursor: pointer;
    font-weight: 600;
    color: #333;
    border-radius: 6px 6px 0 0;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .crm-accordion-header:hover {
    background: #e9ecef;
  }

  .crm-accordion-header.active {
    background: #667eea;
    color: white;
  }

  .crm-accordion-body {
    border: 1px solid #ddd;
    border-top: none;
    padding: 20px;
    border-radius: 0 0 6px 6px;
    background: white;
  }

  .description {
    font-size: 0.85em;
    color: #666;
    margin-top: 5px;
    font-style: italic;
  }

  @media (max-width: 768px) {
    .chart-config-grid {
      grid-template-columns: 1fr;
    }

    .chart-config-options {
      grid-template-columns: 1fr;
    }

    .custom-colors-table {
      display: block;
    }

    .custom-colors-table tr {
      display: block;
      margin-bottom: 15px;
    }

    .custom-colors-table td {
      display: block;
      padding: 5px 0;
    }
  }
  {/literal}
</style>
