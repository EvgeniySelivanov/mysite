<?php
/**
 * Discount Actions
 *
 * @package     restrict-content-pro
 * @subpackage  Admin/Discount Actions
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.9
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Add a new discount code
 *
 * @since 2.9
 * @return void
 */
function rcp_process_add_discount() {

	if ( ! wp_verify_nonce( $_POST['rcp_add_discount_nonce'], 'rcp_add_discount_nonce' ) ) {
		wp_die( __( 'Nonce verification failed.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! current_user_can( 'rcp_manage_discounts' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	// Setup data
	$data = array(
		'name'                 => sanitize_text_field( wp_unslash( $_POST['name'] ) ),
		'description'          => wp_kses_post( wp_unslash( $_POST['description'] ) ),
		'amount'               => sanitize_text_field( $_POST['amount'] ),
		'unit'                 => isset( $_POST['unit'] ) && $_POST['unit'] == '%' ? '%' : 'flat',
		'code'                 => sanitize_text_field( wp_unslash( $_POST['code'] ) ),
		'status'               => 'active',
		'expiration'           => sanitize_text_field( $_POST['expiration'] ),
		'max_uses'             => absint( $_POST['max'] ),
		'membership_level_ids' => ( ! empty( $_POST['membership_levels'] ) && is_array( $_POST['membership_levels'] ) ) ? array_map( 'absint', $_POST['membership_levels'] ) : array(),
		'one_time'             => ! empty( $_POST['one_time'] ) ? 1 : 0
	);

	$add = rcp_add_discount( $data );

	if ( is_wp_error( $add ) ) {
		rcp_log( sprintf( 'Error creating new discount code: %s', $add->get_error_message() ), true );
		$error_code = ( 'discount' === substr( $add->get_error_code(), 0, 8 ) ) ? $add->get_error_code() : 'discount_' . $add->get_error_code();
		$url = add_query_arg( array(
			'rcp_message'   => urlencode( $error_code ),
			'discount_code' => urlencode( strtolower( $data['code'] ) )
		), admin_url( 'admin.php?page=rcp-discounts' ) );
	} elseif ( $add ) {
		rcp_log( sprintf( 'Successfully added discount #%d.', $add ) );
		$url = admin_url( 'admin.php?page=rcp-discounts&rcp_message=discount_added' );
	} else {
		rcp_log( 'Error inserting new discount code into the database.', true );
		$url = admin_url( 'admin.php?page=rcp-discounts&rcp_message=discount_not_added' );
	}

	wp_safe_redirect( $url );
	exit;

}
add_action( 'rcp_action_add-discount', 'rcp_process_add_discount' );

/**
 * Edit an existing discount code
 *
 * @since 2.9
 * @return void
 */
function rcp_process_edit_discount() {

	if ( ! wp_verify_nonce( $_POST['rcp_edit_discount_nonce'], 'rcp_edit_discount_nonce' ) ) {
		wp_die( __( 'Nonce verification failed.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! current_user_can( 'rcp_manage_discounts' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	// Setup data
	$data = array(
		'name'                 => sanitize_text_field( wp_unslash( $_POST['name'] ) ),
		'description'          => wp_kses_post( wp_unslash( $_POST['description'] ) ),
		'amount'               => sanitize_text_field( $_POST['amount'] ),
		'unit'                 => isset( $_POST['unit'] ) && $_POST['unit'] == '%' ? '%' : 'flat',
		'code'                 => sanitize_text_field( wp_unslash( $_POST['code'] ) ),
		'status'               => 'active' == $_POST['status'] ? 'active' : 'disabled',
		'expiration'           => sanitize_text_field( $_POST['expiration'] ),
		'max_uses'             => absint( $_POST['max'] ),
		'membership_level_ids' => ( ! empty( $_POST['membership_levels'] ) && is_array( $_POST['membership_levels'] ) ) ? array_map( 'absint', $_POST['membership_levels'] ) : array(),
		'one_time'             => ! empty( $_POST['one_time'] ) ? 1 : 0,
	);

	$update = rcp_update_discount( absint( $_POST['discount_id'] ), $data );

	if ( is_wp_error( $update ) ) {
		rcp_log( sprintf( 'Error updating discount code: %s', $update->get_error_message() ), true );
		wp_die( $update );
	}

	if ( $update ) {
		rcp_log( sprintf( 'Successfully edited discount #%d.', $_POST['discount_id'] ) );
		$url = admin_url( 'admin.php?page=rcp-discounts&discount-updated=1' );
	} else {
		rcp_log( sprintf( 'Error editing discount #%d.', $_POST['discount_id'] ), true );
		$url = admin_url( 'admin.php?page=rcp-discounts&discount-updated=0' );
	}

	wp_safe_redirect( $url );
	exit;

}
add_action( 'rcp_action_edit-discount', 'rcp_process_edit_discount' );

/**
 * Delete a discount code
 *
 * @since 2.9
 * @return void
 */
function rcp_process_delete_discount() {

	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'rcp-delete-discount' ) ) {
		wp_die( __( 'Nonce verification failed.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! current_user_can( 'rcp_manage_discounts' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! isset( $_GET['discount_id'] ) ) {
		wp_die( __( 'Please select a discount.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$discount_id = absint( $_GET['discount_id'] );

	rcp_delete_discount( $discount_id );

	rcp_log( sprintf( 'Deleted discount #%d.', $discount_id ) );

	wp_safe_redirect( add_query_arg( 'rcp_message', 'discount_deleted', 'admin.php?page=rcp-discounts' ) );
	exit;

}
add_action( 'rcp_action_delete_discount_code', 'rcp_process_delete_discount' );

/**
 * Activate a discount code
 *
 * @since 2.9
 * @return void
 */
function rcp_process_activate_discount() {

	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'rcp-activate-discount' ) ) {
		wp_die( __( 'Nonce verification failed.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! current_user_can( 'rcp_manage_discounts' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! isset( $_GET['discount_id'] ) ) {
		wp_die( __( 'Please select a discount.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$discount_id = absint( $_GET['discount_id'] );

	rcp_update_discount( $discount_id, array(
		'status' => 'active'
	) );

	rcp_log( sprintf( 'Successfully activated discount #%d.', $discount_id ) );

	wp_safe_redirect( add_query_arg( 'rcp_message', 'discount_activated', 'admin.php?page=rcp-discounts' ) );
	exit;

}
add_action( 'rcp_action_activate_discount', 'rcp_process_activate_discount' );

/**
 * Deactivate a discount code
 *
 * @since 2.9
 * @return void
 */
function rcp_process_deactivate_discount() {

	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'rcp-deactivate-discount' ) ) {
		wp_die( __( 'Nonce verification failed.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! current_user_can( 'rcp_manage_discounts' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! isset( $_GET['discount_id'] ) ) {
		wp_die( __( 'Please select a discount.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$discount_id = absint( $_GET['discount_id'] );

	rcp_update_discount( $discount_id, array(
		'status' => 'disabled'
	) );

	rcp_log( sprintf( 'Successfully deactivated discount #%d.', $discount_id ) );

	wp_safe_redirect( add_query_arg( 'rcp_message', 'discount_deactivated', 'admin.php?page=rcp-discounts' ) );
	exit;

}
add_action( 'rcp_action_deactivate_discount', 'rcp_process_deactivate_discount' );
