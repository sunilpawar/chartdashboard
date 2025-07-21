/**
 * Chart Dashboard JavaScript
 * Handles chart rendering, API calls, and dashboard configuration
 */

(function($, CRM) {
  'use strict';

  // Dashboard controller object
  var ChartDashboard = {

    // Configuration
    config: {
      refreshInterval: 300000, // 5 minutes
      animationDuration: 1000,
      maxRetries: 3,
      retryDelay: 2000
    },

    // State management
    state: {
      charts: new Map(),
      refreshTimers: new Map(),
      isConfigMode: false,
      globalTimeRange: null
    },

    // Initialize dashboard
    init: function() {
      console.log('Initializing Chart Dashboard...');

      // Check if required libraries are loaded
      if (typeof Chart === 'undefined') {
        console.error('Chart.js not loaded');
        this.showError('Chart.js library not loaded. Please refresh the page.');
        return;
      }

      // Set Chart.js defaults
      this.setupChartDefaults();

      // Bind events
      this.bindEvents();

      // Load initial dashboard
      this.loadDashboard();

      // Setup auto-refresh
      this.setupAutoRefresh();

      console.log('Chart Dashboard initialized successfully');
    },

    // Setup Chart.js default configuration
    setupChartDefaults: function() {
      Chart.defaults.responsive = true;
      Chart.defaults.maintainAspectRatio = false;
      Chart.defaults.animation.duration = this.config.animationDuration;
      Chart.defaults.plugins.legend.display = true;
      Chart.defaults.plugins.legend.position = 'top';
      Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0, 0, 0, 0.8)';
      Chart.defaults.plugins.tooltip.titleColor = '#fff';
      Chart.defaults.plugins.tooltip.bodyColor = '#fff';
      Chart.defaults.plugins.tooltip.borderColor = '#667eea';
      Chart.defaults.plugins.tooltip.borderWidth = 1;
    },

    // Bind event handlers
    bindEvents: function() {
      var self = this;

      // Configuration button
      $('#configure-dashboard').on('click', function() {
        self.openConfigModal();
      });

      // Refresh all button
      $('#refresh-all').on('click', function() {
        self.refreshAllCharts();
      });

      // Global time range selector
      $('#global-time-range').on('change', function() {
        var timeRange = $(this).val();
        self.applyGlobalTimeRange(timeRange);
      });

      // Modal events
      $('#config-modal .close, #config-modal [data-dismiss="modal"]').on('click', function() {
        self.closeConfigModal();
      });

      $('#save-config').on('click', function() {
        self.saveConfiguration();
      });

      // Chart template events
      $(document).on('click', '.chart-refresh', function() {
        var chartId = $(this).closest('.chart-container').data('chart-id');
        self.refreshChart(chartId);
      });

      $(document).on('change', '.chart-time-range', function() {
        var chartId = $(this).closest('.chart-container').data('chart-id');
        var timeRange = $(this).val();
        self.updateChartTimeRange(chartId, timeRange);
      });

      $(document).on('click', '.chart-remove', function() {
        var chartId = $(this).closest('.chart-container').data('chart-id');
        self.removeChart(chartId);
      });

      $(document).on('click', '.add-chart', function() {
        var chartItem = $(this).closest('.chart-item');
        var chartConfig = self.getChartConfigFromItem(chartItem);
        self.addChart(chartConfig);
      });

      // Retry buttons
      $(document).on('click', '#retry-load, .retry-chart', function() {
        var chartId = $(this).closest('.chart-container').data('chart-id');
        if (chartId) {
          self.refreshChart(chartId);
        } else {
          self.loadDashboard();
        }
      });

      // Modal backdrop click
      $('#config-modal').on('click', function(e) {
        if (e.target === this) {
          self.closeConfigModal();
        }
      });

      // Keyboard shortcuts
      $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && self.state.isConfigMode) {
          self.closeConfigModal();
        }
        if (e.ctrlKey && e.key === 'r') {
          e.preventDefault();
          self.refreshAllCharts();
        }
      });
    },

    // Load initial dashboard
    loadDashboard: function() {
      console.log('Loading dashboard...');
      this.showLoading(true);

      var dashboardConfig = CRM.vars.chartDashboard.dashboardConfig;
      var self = this;

      // Clear existing charts
      this.clearAllCharts();

      // Load dashboard summary
      this.loadDashboardSummary();

      // Load each configured chart
      if (dashboardConfig && dashboardConfig.charts) {
        dashboardConfig.charts.forEach(function(chartConfig, index) {
          setTimeout(function() {
            self.loadChart(chartConfig);
          }, index * 200); // Stagger loading
        });
      }

      this.showLoading(false);
    },

    // Load dashboard summary statistics
    loadDashboardSummary: function() {
      var self = this;

      // Load real-time donation data for summary
      this.makeAPICall('realtime_donations', '7days')
        .then(function(data) {
          if (data && data.summary) {
            self.updateSummaryCards(data.summary);
          }
        })
        .catch(function(error) {
          console.warn('Failed to load dashboard summary:', error);
        });
    },

    // Update summary cards
    updateSummaryCards: function(summary) {
      $('#total-donations').text(this.formatCurrency(summary.total_amount || 0));
      $('#total-donors').text(this.formatNumber(summary.total_count || 0));
      $('#avg-donation').text(this.formatCurrency(summary.avg_donation || 0));

      // Load campaign progress for the fourth card
      this.makeAPICall('campaign_progress', '')
        .then(function(campaigns) {
          var totalProgress = 0;
          var activeCount = 0;

          if (campaigns && campaigns.length > 0) {
            campaigns.forEach(function(campaign) {
              if (campaign.progress_percentage > 0) {
                totalProgress += campaign.progress_percentage;
                activeCount++;
              }
            });

            var avgProgress = activeCount > 0 ? totalProgress / activeCount : 0;
            $('#campaign-progress').text(Math.round(avgProgress) + '%');
          } else {
            $('#campaign-progress').text('--');
          }
        })
        .catch(function() {
          $('#campaign-progress').text('--');
        });
    },

    // Load individual chart
    loadChart: function(chartConfig) {
      console.log('Loading chart:', chartConfig.chart_id);

      var chartContainer = this.createChartContainer(chartConfig);
      $('#dashboard-grid').append(chartContainer);

      this.showChartLoading(chartConfig.chart_id, true);

      var self = this;
      this.makeAPICall(chartConfig.chart_id, chartConfig.time_range)
        .then(function(data) {
          self.renderChart(chartConfig, data);
          self.showChartLoading(chartConfig.chart_id, false);
        })
        .catch(function(error) {
          self.showChartError(chartConfig.chart_id, error.message || 'Failed to load chart data');
          self.showChartLoading(chartConfig.chart_id, false);
        });
    },

    // Create chart container HTML
    createChartContainer: function(chartConfig) {
      var template = $('#chart-template').clone();
      var availableCharts = CRM.vars.chartDashboard.availableCharts;
      var chartInfo = availableCharts[chartConfig.chart_id];

      template.attr('id', 'chart-' + chartConfig.chart_id)
        .addClass('chart-container')
        .addClass('size-' + (chartConfig.size || 'medium'))
        .data('chart-id', chartConfig.chart_id)
        .show();

      template.find('.chart-title').text(chartInfo ? chartInfo.title : chartConfig.chart_id);
      template.find('.chart-time-range').val(chartConfig.time_range || '7days');

      // Hide time range selector if not supported
      if (chartInfo && !chartInfo.supports_time_range) {
        template.find('.chart-time-range').hide();
      }

      return template;
    },

    // Render chart with data
    renderChart: function(chartConfig, data) {
      var chartId = chartConfig.chart_id;
      var chartType = chartConfig.chart_type || 'line';
      var canvas = $('#chart-' + chartId + ' .chart-canvas')[0];

      if (!canvas) {
        console.error('Canvas not found for chart:', chartId);
        return;
      }

      // Destroy existing chart if it exists
      if (this.state.charts.has(chartId)) {
        this.state.charts.get(chartId).destroy();
      }

      var chartData = this.prepareChartData(chartId, chartType, data);
      var chartOptions = this.getChartOptions(chartId, chartType);

      try {
        var chart = new Chart(canvas, {
          type: this.getChartJSType(chartType),
          data: chartData,
          options: chartOptions
        });

        this.state.charts.set(chartId, chart);

        // Update metadata
        this.updateChartMetadata(chartId, data);

        console.log('Chart rendered successfully:', chartId);
      } catch (error) {
        console.error('Error rendering chart:', chartId, error);
        this.showChartError(chartId, 'Failed to render chart: ' + error.message);
      }
    },

    // Prepare chart data based on chart type and data
    prepareChartData: function(chartId, chartType, rawData) {
      switch (chartId) {
        case 'realtime_donations':
          return this.prepareRealTimeDonationData(chartType, rawData);
        case 'recurring_vs_onetime':
          return this.prepareRecurringVsOneTimeData(chartType, rawData);
        case 'lapsed_donors':
          return this.prepareLapsedDonorData(chartType, rawData);
        case 'donor_retention':
          return this.prepareDonorRetentionData(chartType, rawData);
        case 'avg_gift_trend':
          return this.prepareAvgGiftTrendData(chartType, rawData);
        case 'campaign_progress':
          return this.prepareCampaignProgressData(chartType, rawData);
        case 'pledged_vs_actual':
          return this.preparePledgedVsActualData(chartType, rawData);
        case 'membership_revenue':
          return this.prepareMembershipRevenueData(chartType, rawData);
        default:
          return { labels: [], datasets: [] };
      }
    },

    // Prepare real-time donation data
    prepareRealTimeDonationData: function(chartType, data) {
      if (!data || !data.chartData) return { labels: [], datasets: [] };

      var labels = data.chartData.map(function(item) {
        return new Date(item.date).toLocaleDateString();
      });

      var amounts = data.chartData.map(function(item) {
        return item.amount;
      });

      var counts = data.chartData.map(function(item) {
        return item.count;
      });

      if (chartType === 'line') {
        return {
          labels: labels,
          datasets: [{
            label: 'Donation Amount ($)',
            data: amounts,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            fill: true,
            tension: 0.4
          }]
        };
      } else {
        return {
          labels: labels,
          datasets: [{
            label: 'Donation Amount ($)',
            data: amounts,
            backgroundColor: 'rgba(102, 126, 234, 0.8)',
            borderColor: '#667eea',
            borderWidth: 1
          }]
        };
      }
    },

    // Prepare recurring vs one-time data
    prepareRecurringVsOneTimeData: function(chartType, data) {
      if (!Array.isArray(data)) return { labels: [], datasets: [] };

      var labels = data.map(function(item) {
        return new Date(item.date).toLocaleDateString();
      });

      var recurringAmounts = data.map(function(item) {
        return item.recurring_amount || 0;
      });

      var oneTimeAmounts = data.map(function(item) {
        return item.one_time_amount || 0;
      });

      return {
        labels: labels,
        datasets: [{
          label: 'Recurring Donations',
          data: recurringAmounts,
          backgroundColor: 'rgba(102, 126, 234, 0.8)',
          borderColor: '#667eea',
          borderWidth: 1
        }, {
          label: 'One-Time Donations',
          data: oneTimeAmounts,
          backgroundColor: 'rgba(249, 93, 123, 0.8)',
          borderColor: '#f95d7b',
          borderWidth: 1
        }]
      };
    },

    // Prepare lapsed donor data
    prepareLapsedDonorData: function(chartType, data) {
      if (!Array.isArray(data)) return { labels: [], datasets: [] };

      var labels = data.map(function(item) {
        return 'Year ' + item.year;
      });

      var donorCounts = data.map(function(item) {
        return item.lapsed_donors;
      });

      var lostValues = data.map(function(item) {
        return item.lost_value;
      });

      if (chartType === 'pie' || chartType === 'doughnut') {
        return {
          labels: labels,
          datasets: [{
            data: donorCounts,
            backgroundColor: [
              'rgba(255, 99, 132, 0.8)',
              'rgba(54, 162, 235, 0.8)',
              'rgba(255, 205, 86, 0.8)',
              'rgba(75, 192, 192, 0.8)',
              'rgba(153, 102, 255, 0.8)'
            ]
          }]
        };
      } else {
        return {
          labels: labels,
          datasets: [{
            label: 'Lapsed Donors',
            data: donorCounts,
            backgroundColor: 'rgba(220, 53, 69, 0.8)',
            borderColor: '#dc3545',
            borderWidth: 1
          }]
        };
      }
    },

    // Prepare donor retention data
    prepareDonorRetentionData: function(chartType, data) {
      if (!Array.isArray(data)) return { labels: [], datasets: [] };

      var labels = data.map(function(item) {
        return 'Year ' + item.year;
      });

      var newDonors = data.map(function(item) {
        return item.new_donors;
      });

      var retainedDonors = data.map(function(item) {
        return item.retained_donors;
      });

      var retentionRates = data.map(function(item) {
        return item.retention_rate;
      });

      return {
        labels: labels,
        datasets: [{
          label: 'New Donors',
          data: newDonors,
          backgroundColor: 'rgba(40, 167, 69, 0.8)',
          borderColor: '#28a745',
          borderWidth: 1
        }, {
          label: 'Retained Donors',
          data: retainedDonors,
          backgroundColor: 'rgba(102, 126, 234, 0.8)',
          borderColor: '#667eea',
          borderWidth: 1
        }]
      };
    },

    // Prepare average gift trend data
    prepareAvgGiftTrendData: function(chartType, data) {
      if (!Array.isArray(data)) return { labels: [], datasets: [] };

      var labels = data.map(function(item) {
        return item.period;
      });

      var avgGifts = data.map(function(item) {
        return item.avg_gift_size;
      });

      return {
        labels: labels,
        datasets: [{
          label: 'Average Gift Size ($)',
          data: avgGifts,
          borderColor: '#667eea',
          backgroundColor: chartType === 'area' ? 'rgba(102, 126, 234, 0.3)' : 'rgba(102, 126, 234, 0.1)',
          fill: chartType === 'area',
          tension: 0.4
        }]
      };
    },

    // Prepare campaign progress data
    prepareCampaignProgressData: function(chartType, data) {
      if (!Array.isArray(data)) return { labels: [], datasets: [] };

      if (chartType === 'progress') {
        // Custom progress chart - will be handled separately
        return { campaigns: data };
      }

      var labels = data.map(function(item) {
        return item.campaign_name;
      });

      var raised = data.map(function(item) {
        return item.raised_amount;
      });

      var goals = data.map(function(item) {
        return item.goal_amount;
      });

      return {
        labels: labels,
        datasets: [{
          label: 'Amount Raised ($)',
          data: raised,
          backgroundColor: 'rgba(40, 167, 69, 0.8)',
          borderColor: '#28a745',
          borderWidth: 1
        }, {
          label: 'Goal Amount ($)',
          data: goals,
          backgroundColor: 'rgba(108, 117, 125, 0.8)',
          borderColor: '#6c757d',
          borderWidth: 1
        }]
      };
    },

    // Prepare pledged vs actual data
    preparePledgedVsActualData: function(chartType, data) {
      if (!Array.isArray(data)) return { labels: [], datasets: [] };

      var labels = data.map(function(item) {
        return item.period;
      });

      var pledged = data.map(function(item) {
        return item.pledged_amount;
      });

      var actual = data.map(function(item) {
        return item.actual_amount;
      });

      return {
        labels: labels,
        datasets: [{
          label: 'Pledged Amount ($)',
          data: pledged,
          backgroundColor: 'rgba(255, 193, 7, 0.8)',
          borderColor: '#ffc107',
          borderWidth: 1
        }, {
          label: 'Actual Amount ($)',
          data: actual,
          backgroundColor: 'rgba(40, 167, 69, 0.8)',
          borderColor: '#28a745',
          borderWidth: 1
        }]
      };
    },

    // Prepare membership revenue data
    prepareMembershipRevenueData: function(chartType, data) {
      if (!Array.isArray(data)) return { labels: [], datasets: [] };

      var labels = data.map(function(item) {
        return item.membership_type;
      });

      var revenues = data.map(function(item) {
        return item.revenue;
      });

      if (chartType === 'pie' || chartType === 'doughnut') {
        return {
          labels: labels,
          datasets: [{
            data: revenues,
            backgroundColor: [
              'rgba(255, 99, 132, 0.8)',
              'rgba(54, 162, 235, 0.8)',
              'rgba(255, 205, 86, 0.8)',
              'rgba(75, 192, 192, 0.8)',
              'rgba(153, 102, 255, 0.8)',
              'rgba(255, 159, 64, 0.8)'
            ]
          }]
        };
      } else {
        return {
          labels: labels,
          datasets: [{
            label: 'Revenue ($)',
            data: revenues,
            backgroundColor: 'rgba(102, 126, 234, 0.8)',
            borderColor: '#667eea',
            borderWidth: 1
          }]
        };
      }
    },

    // Get Chart.js chart type from our chart type
    getChartJSType: function(chartType) {
      var typeMap = {
        'line': 'line',
        'bar': 'bar',
        'stacked_bar': 'bar',
        'grouped_bar': 'bar',
        'horizontal_bar': 'bar',
        'pie': 'pie',
        'doughnut': 'doughnut',
        'area': 'line',
        'progress': 'bar',
        'funnel': 'bar'
      };

      return typeMap[chartType] || 'bar';
    },

    // Get chart options
    getChartOptions: function(chartId, chartType) {
      var baseOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: true,
            position: 'top'
          },
          tooltip: {
            mode: 'index',
            intersect: false,
            callbacks: {
              label: function(context) {
                var label = context.dataset.label || '';
                if (label) {
                  label += ': ';
                }

                // Format currency values
                if (label.includes('$') || label.includes('Amount') || label.includes('Revenue')) {
                  label += ChartDashboard.formatCurrency(context.parsed.y || context.parsed);
                } else {
                  label += ChartDashboard.formatNumber(context.parsed.y || context.parsed);
                }

                return label;
              }
            }
          }
        },
        scales: {}
      };

      // Chart type specific options
      switch (chartType) {
        case 'stacked_bar':
          baseOptions.scales.x = { stacked: true };
          baseOptions.scales.y = { stacked: true };
          break;

        case 'horizontal_bar':
          baseOptions.indexAxis = 'y';
          break;

        case 'area':
          baseOptions.fill = true;
          break;

        case 'pie':
        case 'doughnut':
          delete baseOptions.scales;
          break;
      }

      // Campaign progress gets special treatment
      if (chartId === 'campaign_progress' && chartType === 'progress') {
        return this.renderProgressChart;
      }

      return baseOptions;
    },

    // Render custom progress chart for campaigns
    renderProgressChart: function(chartConfig, data) {
      var chartId = chartConfig.chart_id;
      var container = $('#chart-' + chartId + ' .chart-content');

      if (!data.campaigns || !Array.isArray(data.campaigns)) {
        this.showChartError(chartId, 'No campaign data available');
        return;
      }

      var html = '<div class="progress-chart-container">';

      data.campaigns.forEach(function(campaign) {
        var percentage = Math.min(campaign.progress_percentage || 0, 100);
        var color = percentage >= 100 ? '#28a745' :
          percentage >= 75 ? '#ffc107' :
            percentage >= 50 ? '#fd7e14' : '#dc3545';

        html += '<div class="campaign-progress-item" style="margin-bottom: 20px;">';
        html += '<div style="display: flex; justify-content: space-between; margin-bottom: 5px;">';
        html += '<strong>' + campaign.campaign_name + '</strong>';
        html += '<span>' + ChartDashboard.formatCurrency(campaign.raised_amount) + ' / ' +
          ChartDashboard.formatCurrency(campaign.goal_amount) + '</span>';
        html += '</div>';
        html += '<div class="progress-bar">';
        html += '<div class="progress-fill" style="width: ' + percentage + '%; background: ' + color + ';">';
        html += '<span class="progress-text">' + Math.round(percentage) + '%</span>';
        html += '</div>';
        html += '</div>';
        html += '</div>';
      });

      html += '</div>';

      container.html(html);
    },

    // Make API call to get chart data
    makeAPICall: function(chartType, timeRange, retryCount) {
      retryCount = retryCount || 0;
      var self = this;

      var params = {
        entity: 'ChartData',
        action: 'Get',
        chart_type: chartType,
        time_range: timeRange || '7days',
        api_key: CRM.vars.api_key,
        key: CRM.vars.site_key
      };

      return new Promise(function(resolve, reject) {
        $.ajax({
          url: CRM.vars.chartDashboard.apiURL,
          type: 'POST',
          data: params,
          dataType: 'json',
          timeout: 30000,
          success: function(response) {
            if (response && response.is_error === 0) {
              resolve(response.values);
            } else {
              var error = response.error_message || 'Unknown API error';
              console.error('API Error:', error);
              reject(new Error(error));
            }
          },
          error: function(xhr, status, error) {
            console.error('AJAX Error:', status, error);

            if (retryCount < self.config.maxRetries) {
              console.log('Retrying API call in ' + self.config.retryDelay + 'ms...');
              setTimeout(function() {
                self.makeAPICall(chartType, timeRange, retryCount + 1)
                  .then(resolve)
                  .catch(reject);
              }, self.config.retryDelay);
            } else {
              reject(new Error('Failed to load data after ' + self.config.maxRetries + ' attempts'));
            }
          }
        });
      });
    },

    // Update chart metadata
    updateChartMetadata: function(chartId, data) {
      var container = $('#chart-' + chartId);
      var now = new Date().toLocaleTimeString();
      var pointCount = 0;

      if (Array.isArray(data)) {
        pointCount = data.length;
      } else if (data && data.chartData && Array.isArray(data.chartData)) {
        pointCount = data.chartData.length;
      } else if (data && data.campaigns && Array.isArray(data.campaigns)) {
        pointCount = data.campaigns.length;
      }

      container.find('.update-time').text(now);
      container.find('.point-count').text(pointCount);
    },

    // Show/hide loading indicator
    showLoading: function(show) {
      if (show) {
        $('#loading-indicator').show();
        $('#dashboard-grid').hide();
        $('#error-container').hide();
      } else {
        $('#loading-indicator').hide();
        $('#dashboard-grid').show();
      }
    },

    // Show/hide chart loading
    showChartLoading: function(chartId, show) {
      var container = $('#chart-' + chartId);
      container.find('.chart-loading').toggle(show);
      container.find('.chart-canvas').toggle(!show);
      container.find('.chart-error').hide();
    },

    // Show chart error
    showChartError: function(chartId, message) {
      var container = $('#chart-' + chartId);
      container.find('.chart-error').show();
      container.find('.chart-error .error-text').text(message);
      container.find('.chart-canvas').hide();
      container.find('.chart-loading').hide();
    },

    // Show general error
    showError: function(message) {
      $('#error-container').show();
      $('#error-message').text(message);
      $('#dashboard-grid').hide();
      $('#loading-indicator').hide();
    },

    // Refresh all charts
    refreshAllCharts: function() {
      console.log('Refreshing all charts...');
      var self = this;

      this.state.charts.forEach(function(chart, chartId) {
        self.refreshChart(chartId);
      });

      // Refresh dashboard summary
      this.loadDashboardSummary();

      CRM.status('success', 'All charts refreshed successfully');
    },

    // Refresh individual chart
    refreshChart: function(chartId) {
      console.log('Refreshing chart:', chartId);

      var container = $('#chart-' + chartId);
      var timeRange = container.find('.chart-time-range').val();
      var chartConfig = this.getChartConfigFromContainer(container);

      this.showChartLoading(chartId, true);

      var self = this;
      this.makeAPICall(chartId, timeRange)
        .then(function(data) {
          self.renderChart(chartConfig, data);
          self.showChartLoading(chartId, false);
        })
        .catch(function(error) {
          self.showChartError(chartId, error.message || 'Failed to refresh chart');
          self.showChartLoading(chartId, false);
        });
    },

    // Update chart time range
    updateChartTimeRange: function(chartId, timeRange) {
      console.log('Updating time range for chart:', chartId, 'to:', timeRange);

      var container = $('#chart-' + chartId);
      var chartConfig = this.getChartConfigFromContainer(container);
      chartConfig.time_range = timeRange;

      this.refreshChart(chartId);
    },

    // Apply global time range to all charts
    applyGlobalTimeRange: function(timeRange) {
      if (!timeRange) {
        this.state.globalTimeRange = null;
        return;
      }

      this.state.globalTimeRange = timeRange;

      var self = this;
      $('.chart-container').each(function() {
        var chartId = $(this).data('chart-id');
        var availableCharts = CRM.vars.chartDashboard.availableCharts;
        var chartInfo = availableCharts[chartId];

        // Only update charts that support time range
        if (chartInfo && chartInfo.supports_time_range) {
          $(this).find('.chart-time-range').val(timeRange);
          self.updateChartTimeRange(chartId, timeRange);
        }
      });

      CRM.status('success', 'Time range updated for all applicable charts');
    },

    // Setup auto-refresh
    setupAutoRefresh: function() {
      var self = this;

      setInterval(function() {
        console.log('Auto-refresh triggered');
        self.refreshAllCharts();
      }, this.config.refreshInterval);
    },

    // Configuration modal functions
    openConfigModal: function() {
      console.log('Opening configuration modal');
      this.state.isConfigMode = true;
      this.populateConfigModal();
      $('#config-modal').show().addClass('fade-in');
    },

    closeConfigModal: function() {
      console.log('Closing configuration modal');
      this.state.isConfigMode = false;
      $('#config-modal').hide().removeClass('fade-in');
    },

    populateConfigModal: function() {
      var self = this;
      var dashboardLayout = $('#dashboard-layout');
      dashboardLayout.empty();

      // Show current charts in layout section
      $('.chart-container').each(function() {
        var chartId = $(this).data('chart-id');
        var chartTitle = $(this).find('.chart-title').text();
        var timeRange = $(this).find('.chart-time-range').val();

        var item = $('<div class="sortable-chart-item" data-chart-id="' + chartId + '">');
        item.html(
          '<div>' +
          '<strong>' + chartTitle + '</strong>' +
          '<div>Time Range: ' + (timeRange || 'Default') + '</div>' +
          '</div>' +
          '<button class="btn btn-danger btn-sm remove-chart-config">' +
          '<i class="crm-i fa-times"></i>' +
          '</button>'
        );

        dashboardLayout.append(item);
      });

      // Bind remove chart from config
      $('.remove-chart-config').on('click', function() {
        var chartId = $(this).closest('.sortable-chart-item').data('chart-id');
        $(this).closest('.sortable-chart-item').remove();
      });
    },

    addChart: function(chartConfig) {
      console.log('Adding chart:', chartConfig);

      // Generate unique position
      chartConfig.position = $('.chart-container').length + 1;

      this.loadChart(chartConfig);
      this.closeConfigModal();

      CRM.status('success', 'Chart added successfully');
    },

    removeChart: function(chartId) {
      if (confirm('Are you sure you want to remove this chart?')) {
        console.log('Removing chart:', chartId);

        // Destroy chart instance
        if (this.state.charts.has(chartId)) {
          this.state.charts.get(chartId).destroy();
          this.state.charts.delete(chartId);
        }

        // Remove container
        $('#chart-' + chartId).remove();

        CRM.status('success', 'Chart removed successfully');
      }
    },

    saveConfiguration: function() {
      console.log('Saving dashboard configuration');

      var config = {
        layout: 'grid',
        charts: []
      };

      // Get current chart configuration
      $('.chart-container').each(function(index) {
        var chartId = $(this).data('chart-id');
        var timeRange = $(this).find('.chart-time-range').val();
        var size = $(this).hasClass('size-large') ? 'large' :
          $(this).hasClass('size-small') ? 'small' : 'medium';

        config.charts.push({
          chart_id: chartId,
          chart_type: 'line', // Default, should be stored per chart
          time_range: timeRange,
          position: index + 1,
          size: size
        });
      });

      // Save via API or AJAX
      $.ajax({
        url: CRM.vars.chartDashboard.saveConfigURL,
        type: 'POST',
        data: JSON.stringify(config),
        contentType: 'application/json',
        success: function(response) {
          if (response.success) {
            CRM.status('success', 'Dashboard configuration saved successfully');
            ChartDashboard.closeConfigModal();
          } else {
            CRM.status('error', 'Failed to save configuration: ' + (response.error || 'Unknown error'));
          }
        },
        error: function() {
          CRM.status('error', 'Failed to save configuration');
        }
      });
    },

    // Helper functions
    getChartConfigFromItem: function(chartItem) {
      var chartId = chartItem.data('chart-id');
      var chartType = chartItem.find('.chart-type-select').val();
      var timeRange = chartItem.find('.time-range-select').val();

      return {
        chart_id: chartId,
        chart_type: chartType,
        time_range: timeRange,
        size: 'medium'
      };
    },

    getChartConfigFromContainer: function(container) {
      var chartId = container.data('chart-id');
      var timeRange = container.find('.chart-time-range').val();
      var size = container.hasClass('size-large') ? 'large' :
        container.hasClass('size-small') ? 'small' : 'medium';

      return {
        chart_id: chartId,
        chart_type: 'line', // Default
        time_range: timeRange,
        size: size
      };
    },

    clearAllCharts: function() {
      // Destroy all chart instances
      this.state.charts.forEach(function(chart) {
        chart.destroy();
      });
      this.state.charts.clear();

      // Clear timers
      this.state.refreshTimers.forEach(function(timer) {
        clearTimeout(timer);
      });
      this.state.refreshTimers.clear();

      // Clear DOM
      $('#dashboard-grid').empty();
    },

    formatCurrency: function(amount) {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(amount || 0);
    },

    formatNumber: function(number) {
      return new Intl.NumberFormat('en-US').format(number || 0);
    }
  };

  // Initialize when document is ready
  $(document).ready(function() {
    // Check if we're on the dashboard page
    if ($('#chart-dashboard-container').length > 0) {
      ChartDashboard.init();
    }
  });

  // Expose to global scope for debugging
  window.ChartDashboard = ChartDashboard;

})(CRM.$, CRM);
