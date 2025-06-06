# Laporan Module (Report Module) - Installation and Usage Guide

This document provides instructions for installing and using the new Laporan (Reporting) module in the Inventory System.

## Features

The Laporan module provides the following features:

1. **Laporan Barang Masuk (Incoming Goods Reports)**
   - Create reports for incoming goods
   - Select multiple incoming transactions to include in a report
   - View detailed reports
   - Print reports

2. **Laporan Barang Keluar (Outgoing Goods Reports)**
   - Create reports for outgoing goods
   - Select multiple outgoing transactions to include in a report
   - View detailed reports
   - Print reports

## Installation

Follow these steps to install the Laporan module in your Inventory System:

1. **Database Setup**
   - Import the required tables by running the SQL script `create_report_tables.sql`
   - You can do this via phpMyAdmin or the MySQL command line:
     ```
     mysql -u username -p inventori_db < create_report_tables.sql
     ```

2. **File Installation**
   - All necessary files are already included in the system:
     - `laporan_masuk.php` - Main page for incoming goods reports
     - `tambah_laporan_masuk.php` - Create incoming goods reports
     - `view_laporan_masuk.php` - View incoming goods report details
     - `cetak_laporan_masuk.php` - Print incoming goods reports
     - `laporan_keluar.php` - Main page for outgoing goods reports
     - `tambah_laporan_keluar.php` - Create outgoing goods reports
     - `view_laporan_keluar.php` - View outgoing goods report details
     - `cetak_laporan_keluar.php` - Print outgoing goods reports

## Usage

### Creating Incoming Goods Reports

1. Navigate to "Laporan Barang → Laporan Barang Masuk" in the sidebar
2. Click the "Buat Laporan Baru" (Create New Report) button
3. Select the date range to filter incoming goods transactions
4. Check the transactions you want to include in the report
5. Click "Simpan Laporan" (Save Report) to create the report

### Viewing and Printing Incoming Goods Reports

1. Navigate to "Laporan Barang → Laporan Barang Masuk" in the sidebar
2. Find the report you want to view and click the eye icon
3. To print the report, click the printer icon or the "Cetak Laporan" button

### Creating Outgoing Goods Reports

1. Navigate to "Laporan Barang → Laporan Barang Keluar" in the sidebar
2. Click the "Buat Laporan Baru" (Create New Report) button
3. Select the date range to filter outgoing goods transactions
4. Check the transactions you want to include in the report
5. Click "Simpan Laporan" (Save Report) to create the report

### Viewing and Printing Outgoing Goods Reports

1. Navigate to "Laporan Barang → Laporan Barang Keluar" in the sidebar
2. Find the report you want to view and click the eye icon
3. To print the report, click the printer icon or the "Cetak Laporan" button

## Troubleshooting

If you encounter any issues with the Laporan module, check the following:

1. **Database Connection**
   - Ensure your database connection is properly configured in `config/database.php`

2. **File Permissions**
   - Make sure all PHP files have the correct read/execute permissions

3. **Session Management**
   - Ensure you are logged in with an account that has permission to access reports

4. **Database Tables**
   - Verify that the report tables were properly created in your database

## Support

If you need further assistance, please contact the system administrator or the developer who implemented this module. 