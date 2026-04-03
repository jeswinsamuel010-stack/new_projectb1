<?php
/**
 * TCPDF Library Setup Instructions
 *
 * This file contains instructions for setting up TCPDF library.
 * TCPDF is not included due to licensing and size considerations.
 *
 * SETUP STEPS:
 * ============
 *
 * 1. Download TCPDF from: https://sourceforge.net/projects/tcpdf/files/
 *    Or: https://github.com/tecnickcom/TCPDF
 *
 * 2. Extract the downloaded zip file
 *
 * 3. Copy the 'tcpdf' folder to: E:\xampp\htdocs\new_projectb\lib\
 *
 * 4. After setup, the structure should be:
 *    lib/
 *    ├── tcpdf/
 *    │   ├── tcpdf.php
 *    │   ├── config/
 *    │   ├── fonts/
 *    │   └── ... (other tcpdf files)
 *    └── tcpdf_setup.php (this file)
 *
 * ALTERNATIVE - Using Composer:
 * ==============================
 * If you have Composer installed, run:
 *   composer require tecnickcom/tcpdf
 *
 * Then use: require_once 'vendor/autoload.php';
 *
 * PDF GENERATION WILL NOT WORK UNTIL TCPDF IS SET UP!
 */

define('TCPDF_DIR', __DIR__ . '/tcpdf');
define('TCPDF_AVAILABLE', file_exists(TCPDF_DIR . '/tcpdf.php'));

if (!TCPDF_AVAILABLE) {
    error_log('WARNING: TCPDF library not found. PDF generation will not work.');
}