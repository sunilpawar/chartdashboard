# CiviCRM Chart Dashboard Extension

A comprehensive real-time donation analytics dashboard for CiviCRM with multiple chart types, time range filtering, and configurable display options.

## Features

### Chart Types Available

- **Real-Time Donation Dashboard**: Visual real-time display of donation totals, goals, and progress bars
- **Recurring vs One-Time Contributions**: Visual comparison of recurring vs. one-time donations over time
- **Lapsed Donor Value Analysis**: Charts showing donation drop-offs by year, cohort, or segment
- **Donor Retention Funnel**: Visualization of how many donors give again year over year
- **Average Gift Size Over Time**: Trend lines showing how average donation amounts evolve
- **Campaign-Specific Fundraising Progress**: Visual goal progress charts for active campaigns
- **Pledged vs Actual Income**: Bar charts comparing expected pledges and actual receipts
- **Membership Revenue Breakdown**: Revenue analysis by membership type and category

### Key Features

- **Real-time Data**: Automatic refresh of donation data with configurable intervals
- **Multiple Chart Types**: Line, bar, pie, doughnut, stacked bar, progress charts, and more
- **Time Range Filtering**: 24 hours, 2 days, 7 days, 1 month, 3 months, 6 months, 1 year
- **Configurable Dashboard**: Drag-and-drop interface to customize chart layout
- **Data Export**: Export chart data to CSV, Excel, PDF, and JSON formats
- **Performance Optimized**: Built-in caching system for improved load times
- **Email Alerts**: Configurable alerts for donation thresholds and campaign milestones
- **Responsive Design**: Works on desktop, tablet, and mobile devices
- **Role-based Access**: Granular permissions for different user types

## Requirements

- CiviCRM 5.39 or later
- PHP 7.4 or later
- MySQL 5.7 or later / MariaDB 10.2 or later
- Modern web browser with JavaScript enabled

## Installation

### Method 1: Extension Manager (Recommended)

1. Navigate to **Administer** > **System Settings** > **Extensions**
2. Click **Add New** tab
3. Upload the extension zip file or install from the directory
4. Click **Install**

### Method 2: Manual Installation

1. Download the extension files to your CiviCRM extensions directory
2. Navigate to **Administer** > **System Settings** > **Extensions**
3. Find "Chart Dashboard" in the list and click **Install**

### Post-Installation Setup

1. **Set Permissions**: Go to **Administer** > **Users and Permissions** > **Permissions (Access Control)**
  - Assign "view chart dashboard" permission to appropriate roles
  - Assign "configure chart dashboard" permission to administrators

2. **Configure Settings**: Visit **Contributions** > **Chart Dashboard** > **Configure** to:
  - Enable/disable specific charts
  - Set default time ranges
  - Configure auto-refresh intervals
  - Set up email alerts
  - Customize color schemes

3. **Access Dashboard**: Go to **Contributions** > **Chart Dashboard** to start using the analytics

## Usage

### Accessing the Dashboard

Navigate to **Contributions** > **Chart Dashboard** from the CiviCRM menu.

### Dashboard Controls

- **Configure Dashboard**: Add, remove, and arrange charts
- **Refresh All**: Manually refresh all chart data
- **Global Time Range**: Apply the same time range to all compatible charts
- **Individual Chart Controls**: Each chart has its own time range selector and refresh button

### Configuring Charts

1. Click **Configure Dashboard** to enter configuration mode
2. **Available Charts** section shows all chart types you can add
3. **Dashboard Layout** section shows your current charts
4. Drag and drop to reorder charts
5. Select chart types and time ranges for each chart
6. Click **Save Configuration** to apply changes

### Exporting Data

1. Click the export button on any chart (if enabled)
2. Choose format: CSV, Excel, PDF, or JSON
3. Select time range for the export
4. Data will download automatically

### Setting Up Alerts

1. Go to **Configure Dashboard**
2. Enable **Email Alerts**
3. Set email address for notifications
4. Configure thresholds:
  - Low donation alert threshold
  - Goal achievement percentage
5. Save settings

## Configuration Options

### Global Settings

- **Auto-refresh**: Enable automatic chart updates
- **Refresh Interval**: How often to refresh (1-60 minutes)
- **Default Time Range**: Default time range for new charts
- **Data Export**: Enable/disable export functionality

### Performance Settings

- **API Timeout**: Maximum time to wait for data (5-60 seconds)
- **Max Data Points**: Limit chart data points for performance
- **Caching**: Enable data caching for faster load times
- **Cache Duration**: How long to cache data (1-60 minutes)

### Chart Configuration

Each chart type can be individually:
- Enabled/disabled
- Assigned a default chart type (line, bar, pie, etc.)
- Given a default time range
- Configured for specific display options

### Color Schemes

Choose from predefined themes or create custom color schemes:
- Default (Blue/Purple gradient)
- Blue Theme
- Green Theme
- Purple Theme
- Orange Theme
- Custom (define your own colors)

## API Usage

The extension provides APIs for programmatic access:

### Get Chart Data

```php
$result = civicrm_api3('ChartData', 'Get', [
  'chart_type' => 'realtime_donations',
  'time_range' => '7days',
]);
```

### Get Available Charts

```php
$charts = civicrm_api3('ChartData', 'GetAvailableCharts');
```

### Warm Up Cache

```php
$result = civicrm_api3('ChartData', 'WarmupCache', [
  'chart_types' => 'realtime_donations,campaign_progress',
  'force_refresh' => TRUE,
]);
```

## Performance Optimization

### Database Indexes

The extension automatically creates optimized database indexes for:
- Contribution queries by date and status
- Campaign progress tracking
- Membership revenue analysis
- Pledge vs actual income comparison

### Caching Strategy

- **Database Cache**: Primary cache using custom table
- **Fallback Cache**: CiviCRM's built-in cache system
- **Automatic Cleanup**: Expired cache entries are automatically removed
- **Cache Warmup**: Background job to pre-populate frequently accessed data

### Best Practices

1. **Enable Caching**: Always enable caching in production
2. **Reasonable Refresh Intervals**: Don't set auto-refresh too aggressively
3. **Limit Data Points**: Use max data points setting for large datasets
4. **Time Range Selection**: Use appropriate time ranges for your needs

## Troubleshooting

### Common Issues

**Charts Not Loading**
- Check JavaScript console for errors
- Verify Chart.js library is loaded
- Ensure proper permissions are set

**Slow Performance**
- Enable caching
- Reduce max data points
- Increase API timeout
- Check database indexes

**Export Not Working**
- Verify export is enabled in settings
- Check file permissions
- Ensure required PHP extensions are installed

**Email Alerts Not Sending**
- Verify SMTP settings in CiviCRM
- Check alert email address
- Review CiviCRM logs for email errors

### Debug Mode

Enable debug logging by adding to your wp-config.php or civicrm.settings.php:

```php
define('CIVICRM_DEBUG_LOG_QUERY', 1);
```

Check logs in your CiviCRM log directory for detailed error information.

### Getting Help

1. **Documentation**: Check this README and inline help text
2. **CiviCRM Forums**: Post questions in the CiviCRM community forums
3. **Issue Tracker**: Report bugs and feature requests on the project repository
4. **Professional Support**: Contact CiviCRM professionals for custom development

## Data Privacy and Security

### Data Handling

- All data remains within your CiviCRM database
- No external services or APIs are used
- Cache data is stored locally
- Export files contain only the data you choose to export

### Security Considerations

- Access controlled by CiviCRM's permission system
- All database queries use parameterized statements
- Input validation on all user-provided data
- CSRF protection on configuration forms

### GDPR Compliance

- No personal data is stored beyond standard CiviCRM data
- All exports respect existing CiviCRM privacy settings
- Cache data automatically expires
- Uninstallation removes all extension data

## Customization

### Adding Custom Chart Types

1. Extend `CRM_Chartdashboard_BAO_DashboardData` class
2. Add new method for your chart data
3. Update API to handle new chart type
4. Add frontend JavaScript for rendering

### Custom Color Schemes

1. Go to Configure Dashboard
2. Select "Custom" color scheme
3. Use color picker to set primary, secondary, and accent colors
4. Colors will be applied across all charts

### Advanced Configuration

For advanced customization, you can modify:
- CSS files in `css/` directory
- JavaScript files in `js/` directory
- PHP classes in `CRM/Chartdashboard/` directory
- Database queries in BAO classes

## Contributing

We welcome contributions! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

### Development Setup

1. Clone the repository to your CiviCRM extensions directory
2. Install development dependencies: `composer install --dev`
3. Enable the extension in CiviCRM
4. Make your changes
5. Test thoroughly

## License

This extension is licensed under the [AGPL-3.0](https://www.gnu.org/licenses/agpl-3.0.en.html) license.

## Support

- **Community Support**: CiviCRM forums and mailing lists
- **Documentation**: This README and inline help
- **Professional Support**: Available from CiviCRM solution providers

## Changelog

### Version 1.0.0
- Initial release
- All core chart types implemented
- Configuration interface
- Export functionality
- Caching system
- Email alerts
- Responsive design

## Acknowledgments

- Chart.js library for beautiful charts
- CiviCRM community for feedback and testing
- All contributors who helped make this extension possible

---

For more information about CiviCRM, visit [civicrm.org](https://civicrm.org).
