<?php
/**
 * Plugin Name: SoulTether
 * Plugin URI:  https://github.com/mteedev/soultether
 * Description: SoulTether — OpenSim Partner System for WordPress. Allows grid residents to send, approve, decline, withdraw, and untether partnerships, syncing directly with the OpenSimulator Robust database.
 * Version:     1.0.1
 * Author:      Gundahar Bravin
 * Author URI:  https://nerdypappy.com
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * SoulTether is a WordPress plugin for OpenSimulator grids.
 * It requires w4os (WordPress for OpenSimulator) and access
 * to your grid's Robust MySQL database.
 */

/*
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    GNU General Public License for more details.
*/

if ( ! defined( 'ABSPATH' ) ) exit;

// ─── Constants ────────────────────────────────────────────────────────────────
define( 'SOULTETHER_VERSION',    '1.0.1' );
define( 'SOULTETHER_PATH',       plugin_dir_path( __FILE__ ) );
define( 'SOULTETHER_URL',        plugin_dir_url( __FILE__ ) );
define( 'SOULTETHER_TABLE',      'soultether_requests' );

// ─── Robust DB Connection ─────────────────────────────────────────────────────
// Configure these to match your grid's Robust database credentials.
// It is recommended to move these to wp-config.php for security:
//   define( 'SOULTETHER_ROBUST_PASS', 'your_password_here' );
define( 'SOULTETHER_ROBUST_HOST',   '127.0.0.1' );          // or use unix_socket below
define( 'SOULTETHER_ROBUST_SOCKET', '/run/mysqld/mysqld.sock' ); // set to '' to use HOST/PORT
define( 'SOULTETHER_ROBUST_DB',     'robust' );              // your Robust database name
define( 'SOULTETHER_ROBUST_USER',   'osadmin' );             // your Robust DB user
define( 'SOULTETHER_ROBUST_PASS',   'YOUR_ROBUST_DB_PASSWORD_HERE' ); // <-- set this

// ─── Includes ─────────────────────────────────────────────────────────────────
require_once SOULTETHER_PATH . 'includes/class-robust-db.php';
require_once SOULTETHER_PATH . 'includes/class-partner-db.php';
require_once SOULTETHER_PATH . 'includes/class-partner-core.php';
require_once SOULTETHER_PATH . 'includes/helpers.php';

// ─── Activation ───────────────────────────────────────────────────────────────
register_activation_hook( __FILE__, 'soultether_activate' );
function soultether_activate() {
    soultether_create_table();
    update_option( 'soultether_db_version', SOULTETHER_VERSION );
}

// ─── Table creation fallback on init ──────────────────────────────────────────
add_action( 'init', 'soultether_maybe_create_table' );
function soultether_maybe_create_table() {
    if ( get_option( 'soultether_db_version' ) !== SOULTETHER_VERSION ) {
        soultether_create_table();
        update_option( 'soultether_db_version', SOULTETHER_VERSION );
    }
}

function soultether_create_table() {
    global $wpdb;
    $table   = $wpdb->prefix . SOULTETHER_TABLE;
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
        requester_uuid   VARCHAR(36)  NOT NULL,
        requester_name   VARCHAR(100) NOT NULL,
        target_uuid      VARCHAR(36)  NOT NULL,
        target_name      VARCHAR(100) NOT NULL,
        request_message  TEXT         NOT NULL DEFAULT '',
        status           ENUM('pending','approved','denied','dissolved') NOT NULL DEFAULT 'pending',
        created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_requester (requester_uuid),
        KEY idx_target    (target_uuid),
        KEY idx_status    (status)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

// ─── Shortcode ────────────────────────────────────────────────────────────────
add_shortcode( 'soultether', 'soultether_shortcode' );
function soultether_shortcode( $atts ) {
    ob_start();
    $core = new SoulTether_Core();
    $core->render_dashboard();
    return ob_get_clean();
}

// ─── Enqueue assets ───────────────────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', 'soultether_enqueue' );
function soultether_enqueue() {
    wp_enqueue_style(
        'soultether-css',
        SOULTETHER_URL . 'assets/soultether.css',
        [],
        SOULTETHER_VERSION
    );
    wp_enqueue_script(
        'soultether-js',
        SOULTETHER_URL . 'assets/soultether.js',
        [ 'jquery' ],
        SOULTETHER_VERSION,
        true
    );
    wp_localize_script( 'soultether-js', 'soulTether', [
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'soultether_nonce' ),
    ]);
}

// ─── Admin menu ───────────────────────────────────────────────────────────────
add_action( 'admin_menu', 'soultether_admin_menu' );
function soultether_admin_menu() {
    add_menu_page(
        'SoulTether',
        'SoulTether',
        'manage_options',
        'soultether-admin',
        'soultether_admin_page',
        'dashicons-heart',
        30
    );
}
function soultether_admin_page() {
    require SOULTETHER_PATH . 'templates/admin-panel.php';
}

// ─── AJAX handlers ────────────────────────────────────────────────────────────
add_action( 'wp_ajax_soultether_send_request',    'soultether_ajax_send_request' );
add_action( 'wp_ajax_soultether_respond_request', 'soultether_ajax_respond_request' );
add_action( 'wp_ajax_soultether_withdraw',        'soultether_ajax_withdraw' );
add_action( 'wp_ajax_soultether_untether',        'soultether_ajax_untether' );

function soultether_ajax_send_request() {
    check_ajax_referer( 'soultether_nonce', 'nonce' );
    $core = new SoulTether_Core();
    wp_send_json( $core->send_request(
        sanitize_text_field( $_POST['target_uuid'] ?? '' ),
        sanitize_textarea_field( $_POST['message'] ?? '' )
    ));
}

function soultether_ajax_respond_request() {
    check_ajax_referer( 'soultether_nonce', 'nonce' );
    $core = new SoulTether_Core();
    wp_send_json( $core->respond_request(
        intval( $_POST['request_id'] ?? 0 ),
        sanitize_text_field( $_POST['action_type'] ?? '' )
    ));
}

function soultether_ajax_withdraw() {
    check_ajax_referer( 'soultether_nonce', 'nonce' );
    $core = new SoulTether_Core();
    wp_send_json( $core->withdraw_request(
        intval( $_POST['request_id'] ?? 0 )
    ));
}

function soultether_ajax_untether() {
    check_ajax_referer( 'soultether_nonce', 'nonce' );
    $core = new SoulTether_Core();
    wp_send_json( $core->dissolve_partnership() );
}
