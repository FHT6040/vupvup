<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// Drop custom tables.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}vupvup_questions" );

// Remove plugin options.
delete_option( 'vupvup_qa_db_version' );
delete_option( 'vupvup_qa_settings' );

// Remove all event meta.
$wpdb->delete( $wpdb->postmeta, [ 'meta_key' => '_vupvup_event_token' ] );
$wpdb->delete( $wpdb->postmeta, [ 'meta_key' => '_vupvup_event_qr_path' ] );
$wpdb->delete( $wpdb->postmeta, [ 'meta_key' => '_vupvup_event_status' ] );
$wpdb->delete( $wpdb->postmeta, [ 'meta_key' => '_vupvup_event_end_time' ] );
$wpdb->delete( $wpdb->postmeta, [ 'meta_key' => '_vupvup_event_guest_allowed' ] );
$wpdb->delete( $wpdb->postmeta, [ 'meta_key' => '_vupvup_event_speakers' ] );

// Remove roles.
remove_role( 'event_facilitator' );
remove_role( 'event_participant' );
