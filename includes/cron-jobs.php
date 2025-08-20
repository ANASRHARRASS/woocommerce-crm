<?php
// This file manages scheduled tasks for the plugin.

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Schedule a cron job for lead capture from social media.
 */
function wccrm_schedule_lead_capture() {
    if ( ! wp_next_scheduled( 'wccrm_capture_leads_event' ) ) {
        wp_schedule_event( time(), 'hourly', 'wccrm_capture_leads_event' );
    }
}
add_action( 'wp', 'wccrm_schedule_lead_capture' );

/**
 * Capture leads from social media platforms.
 */
function wccrm_capture_leads() {
    // Logic to capture leads from TikTok, Facebook, and Instagram.
    // This could involve calling the respective sync functions.
    // Example: TikTokSync::syncLeads();
}
add_action( 'wccrm_capture_leads_event', 'wccrm_capture_leads' );

/**
 * Schedule a cron job for order tracking updates.
 */
function wccrm_schedule_order_tracking() {
    if ( ! wp_next_scheduled( 'wccrm_order_tracking_event' ) ) {
        wp_schedule_event( time(), 'twicedaily', 'wccrm_order_tracking_event' );
    }
}
add_action( 'wp', 'wccrm_schedule_order_tracking' );

/**
 * Update order tracking information.
 */
function wccrm_update_order_tracking() {
    // Logic to update order tracking information.
    // This could involve calling the OrderManager::updateTracking() method.
}
add_action( 'wccrm_order_tracking_event', 'wccrm_update_order_tracking' );

/**
 * Clear scheduled events on plugin deactivation.
 */
function wccrm_clear_scheduled_events() {
    $timestamp = wp_next_scheduled( 'wccrm_capture_leads_event' );
    wp_unschedule_event( $timestamp, 'wccrm_capture_leads_event' );

    $timestamp = wp_next_scheduled( 'wccrm_order_tracking_event' );
    wp_unschedule_event( $timestamp, 'wccrm_order_tracking_event' );
}
register_deactivation_hook( __FILE__, 'wccrm_clear_scheduled_events' );
?>