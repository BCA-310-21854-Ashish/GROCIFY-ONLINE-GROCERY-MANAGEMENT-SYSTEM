# Daily PDF Report Generation - Setup Guide

## Feature Overview

Admins can now generate professional PDF reports for any date directly from the admin panel. Reports include:

✅ **Summary Statistics**
- Total Sales (₹)
- Total Orders Count
- Average Order Value

✅ **Top 10 Products**
- Product name, quantity sold, revenue

✅ **Sales by Category**
- Category breakdown with total revenue

✅ **Detailed Daily Orders**
- Order ID, Customer Name, Amount, Payment Method, Item Count, Status

## Quick Start

### 1. Download TCPDF Library

Option A: **Manual Download**
1. Go to https://tcpdf.org/download.php
2. Download the latest version
3. Extract the ZIP file
4. Create folder: `lib/tcpdf/` in your Grocify root directory
5. Copy all TCPDF files into `lib/tcpdf/`

Option B: **Using Composer** (if available)
```bash
composer require tecnickcom/tcpdf
```

### 2. Verify Installation

1. Go to Admin Panel → Reports & Analytics
2. Check if you see "✅ PDF generation ready" message
3. If not, verify that `lib/tcpdf/tcpdf.php` exists

### 3. Generate Your First Report

1. In Admin Panel → Reports section, scroll to "📄 Generate Daily PDF Report"
2. **Quick Options:**
   - Click "📄 Today's Report" for instant today's PDF
   - Or select a date and click "📥 Generate Report"
3. The PDF will download automatically as: `Grocify_Report_YYYY-MM-DD.pdf`

## File Structure

```
grocify/
├── config/
│   └── report_helper.php        (NEW - Report generation functions)
├── admin/
│   ├── reports.php              (MODIFIED - Added PDF UI)
│   └── generate_pdf_report.php  (NEW - PDF download handler)
└── lib/
    └── tcpdf/                   (NEW - TCPDF library files)
        ├── tcpdf.php            (Main library file)
        ├── config/
        ├── fonts/
        └── include/
```

## Functions Reference

### `getDailyReportData($date = null)`
Retrieves all report data for a specific date.

**Parameters:**
- `$date` - Date string in YYYY-MM-DD format (default: today)

**Returns:**
```php
[
    'date' => '2024-01-15',
    'total_sales' => 15450.50,
    'total_orders' => 23,
    'avg_order_value' => 671.76,
    'top_products' => [...],
    'categories' => [...],
    'orders' => [...]
]
```

### `generatePDFReport($reportData)`
Generates a professional PDF from report data.

**Parameters:**
- `$reportData` - Array from `getDailyReportData()`

**Returns:**
- PDF content (string) or `false` on error

### `isTCPDFAvailable()`
Checks if TCPDF library is installed.

**Returns:**
- `true` if TCPDF is available, `false` otherwise

## PDF Features

- **Professional Layout**: Header with date and company info
- **Multi-page Support**: Automatically adds new pages if needed
- **Color Coded**: Summary sections use blue headers with white text
- **Alternating Row Colors**: Tables have alternating row colors for readability
- **Currency Formatting**: All amounts displayed in Indian Rupees (₹)
- **Date Formatting**: Dates displayed in readable format (e.g., "January 15, 2024")

## Report Data Included

### Summary Statistics Section
| Metric | Description |
|--------|-------------|
| Total Sales | Sum of all order amounts for the day |
| Total Orders | Count of all orders placed that day |
| Average Order Value | Average amount per order |

### Top Products Table
- **Limit**: Top 10 best-selling products
- **Columns**: Product Name, Quantity Sold, Total Revenue

### Sales by Category Table
- **Group By**: Product category
- **Columns**: Category Name, Quantity Sold, Total Revenue
- **Sort**: By revenue (highest first)

### Daily Orders Table
- **All Orders**: Every order placed on that date
- **Columns**: Order ID, Customer Name, Total Amount, Payment Method, Item Count, Status
- **Sort**: By order date (newest first)

## Troubleshooting

### Issue: "TCPDF library not found"

**Solution:**
1. Verify `lib/tcpdf/tcpdf.php` exists
2. Check folder permissions (should be readable)
3. Make sure TCPDF version 6.4+ is installed
4. Restart web server

### Issue: PDF generated but no data appears

**Check:**
1. Date range has orders in database
2. Orders status is not "Cancelled"
3. All products linked correctly to orders

### Issue: Date picker not working

**Solution:**
1. Use format: `YYYY-MM-DD` (e.g., 2024-01-15)
2. Cannot select future dates
3. Clear browser cache if date picker appears blank

## Admin Usage Examples

### Generate Today's Report
```
Admin Panel → Reports & Analytics → Click "📄 Today's Report"
```

### Generate Report for Specific Date
```
Admin Panel → Reports & Analytics → 
Select Date → Click "📥 Generate Report"
```

### Check PDF Download Progress
- Reports generate instantly (usually < 2 seconds)
- File downloads as `Grocify_Report_YYYY-MM-DD.pdf`
- Browser may show download notification

## API Access

### Generate Report Programmatically

```php
<?php
require_once 'config/report_helper.php';
require_once 'config/db.php';

// Get report data
$reportData = getDailyReportData('2024-01-15');

// Generate PDF
$pdfContent = generatePDFReport($reportData);

// Save or output
if ($pdfContent) {
    file_put_contents('report.pdf', $pdfContent);
    // Or: header('Content-Type: application/pdf');
    // echo $pdfContent;
}
?>
```

## Security Notes

- ✅ Only admins can generate reports (checked in `generate_pdf_report.php`)
- ✅ Date validation prevents invalid queries
- ✅ SQL injection protection via parameterized queries
- ✅ Session validation ensures authorized access

## Performance

- **Report Generation Time**: < 2 seconds for typical data
- **PDF Size**: ~50-100 KB depending on data
- **Memory Usage**: ~5-10 MB per report
- **Database Queries**: 4-5 queries per report

## Future Enhancements

- Email reports automatically to admin
- Schedule daily report generation at specific time
- Export data as CSV/Excel
- Add charts/graphs to PDF
- Monthly and yearly report summaries
- Comparison reports (month-over-month, year-over-year)

## Support

For issues or feature requests:
1. Check the troubleshooting section above
2. Verify TCPDF installation
3. Review error logs in `sms_debug.log` or PHP error logs
4. Contact system administrator

---

**Last Updated**: 2024
**Feature Version**: 1.0
**Compatible with**: Grocify 1.0+
