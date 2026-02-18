<?php
/**
 * Plugin Name: WCPOS Polylang Integration
 * Description: Polylang language filtering for WCPOS, including fast-sync route coverage and per-store language support in WCPOS Pro.
 * Version: 0.1.0
 * Author: kilbot
 * Requires Plugins: woocommerce, polylang
 * Text Domain: wcpos-polylang
 */

namespace WCPOS\Polylang;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VERSION = '0.1.0';

require_once __DIR__ . '/includes/class-plugin.php';

Plugin::instance();
