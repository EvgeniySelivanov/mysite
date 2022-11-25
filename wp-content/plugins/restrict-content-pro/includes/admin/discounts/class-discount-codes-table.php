<?php
/**
 * Discount Codes List Table
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2019, Restrict Content Pro
 * @license   GPL2+
 * @since     3.1
 */

namespace RCP\Admin;

use \RCP_Discount;

/**
 * Class Discount_Codes_Table
 *
 * @since   3.1
 * @package RCP\Admin
 */
class Discount_Codes_Table extends List_Table {

	/**
	 * Constructor.
	 *
	 * @since 3.1
	 * @see   WP_List_Table::__construct()
	 */
	public function __construct() {
		parent::__construct( [
			'singular' => 'Discount Code',
			'plural'   => 'Discount Codes',
			'ajax'     => false,
		] );

		$this->process_bulk_action();
		$this->get_counts();
	}

	/**
	 * Get the base URL for the discount codes list table.
	 *
	 * @since 3.1
	 * @return string Base URL.
	 */
	public function get_base_url() {

		$args = array(
			'page' => 'rcp-discounts'
		);

		$discounts_page = add_query_arg( $args, admin_url( 'admin.php' ) );

		return $discounts_page;

	}

	/**
	 * Retrieve the table columns.
	 *
	 * @since 3.1
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'                => '<input type="checkbox" />',
			'name'              => __( 'Name', 'rcp' ),
			'description'       => __( 'Description', 'rcp' ),
			'code'              => __( 'Code', 'rcp' ),
			'membership_levels' => __( 'Membership Level(s)', 'rcp' ),
			'amount'            => __( 'Amount', 'rcp' ),
			'type'              => __( 'Type', 'rcp' ),
			'status'            => __( 'Status', 'rcp' ),
			'use_count'         => __( 'Uses', 'rcp' ),
			'uses_left'         => __( 'Uses Left', 'rcp' ),
			'expiration'        => __( 'Expiration', 'rcp' ),
			'one_time'          => __( 'One Time', 'rcp' )
		);

		/*
		 * Backwards compatibility: add an "extra" column if someone is hooking into the old action to add
		 * their own column. Everything gets bundled into one column because this is the only way we can realistically
		 * do it.
		 */
		if ( has_action( 'rcp_discounts_page_table_header' ) ) {
			$columns['custom'] = __( 'Extra', 'rcp' );
		}

		/**
		 * Filters the table columns.
		 *
		 * @param array $columns
		 *
		 * @since 3.1
		 */
		$columns = apply_filters( 'rcp_discount_codes_list_table_columns', $columns );

		return $columns;
	}

	/**
	 * Retrieve the sortable columns.
	 *
	 * @since 3.1
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'name'       => array( 'name', false ),
			'code'       => array( 'code', false ),
			'use_count'  => array( 'use_count', false ),
			'expiration' => array( 'expiration', false )
		);
	}

	/**
	 * Gets the name of the primary column.
	 *
	 * @since 3.1
	 * @return string
	 */
	protected function get_primary_column_name() {
		return 'name';
	}

	/**
	 * This function renders any other columns in the list table.
	 *
	 * @param RCP_Discount $discount    Discount code object object.
	 * @param string       $column_name The name of the column
	 *
	 * @since 3.1
	 * @return string Column Name
	 */
	public function column_default( $discount, $column_name ) {

		$value = '';

		switch ( $column_name ) {

			case 'description' :
				$value = $discount->get_description();
				break;

			case 'code' :
				$value = esc_html( $discount->get_code() );
				break;

			case 'membership_levels' :
				$membership_levels = $discount->get_membership_level_ids();
				if ( is_array( $membership_levels ) && count( $membership_levels ) > 1 ) {
					$value = __( 'Multiple Levels', 'rcp' );
				} elseif ( is_array( $membership_levels ) && 1 === count( $membership_levels ) ) {
					$value = rcp_get_subscription_name( $membership_levels[0] );
				} else {
					$value = __( 'All Levels', 'rcp' );
				}
				break;

			case 'amount' :
				$value = rcp_discount_sign_filter( $discount->get_amount(), $discount->get_unit() );
				break;

			case 'type' :
				$value = '%' == $discount->get_unit() ? __( 'Percentage', 'rcp' ) : __( 'Flat', 'rcp' );
				break;

			case 'status' :
				if ( rcp_is_discount_not_expired( $discount->get_id() ) ) {
					$value = 'active' === $discount->get_status() ? __( 'active', 'rcp' ) : __( 'disabled', 'rcp' );
				} else {
					$value = __( 'expired', 'rcp' );
				}
				break;

			case 'use_count' :
				if ( $discount->get_max_uses() > 0 ) {
					$value = absint( $discount->get_use_count() ) . '/' . absint( $discount->get_max_uses() );
				} else {
					$value = absint( $discount->get_use_count() );
				}
				break;

			case 'uses_left' :
				$value = rcp_discount_has_uses_left( $discount->get_id() ) ? __( 'yes', 'rcp' ) : __( 'no', 'rcp' );
				break;

			case 'expiration' :
				$expiration = $discount->get_expiration();
				$value      = ! empty( $expiration ) ? date_i18n( 'Y-m-d H:i:s', strtotime( $expiration, current_time( 'timestamp' ) ) ) : __( 'none', 'rcp' );
				break;

			case 'one_time' :
				$value = $discount->is_one_time() ? __( 'yes', 'rcp' ) : __( 'no', 'rcp' );
				break;

		}

		/*
		 * Backwards compatibility: show content of custom columns from old action hook.
		 */
		if ( 'custom' == $column_name && has_action( 'rcp_discounts_page_table_column' ) ) {
			ob_start();
			do_action( 'rcp_discounts_page_table_column', $discount->get_id() );
			$column_content = ob_get_clean();

			$value = wp_strip_all_tags( $column_content );
		}

		/**
		 * Filters the column value.
		 *
		 * @param string $value    Column value.
		 * @param object $discount Discount code object.
		 *
		 * @since 3.1
		 */
		$value = apply_filters( 'rcp_discount_codes_list_table_column_' . $column_name, $value, $discount );

		return $value;

	}

	/**
	 * Render the checkbox column.
	 *
	 * @param object $discount
	 *
	 * @since 3.1
	 * @return string
	 */
	public function column_cb( $discount ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			'discount_id',
			$discount->id
		);
	}

	/**
	 * Render the "Name" column.
	 *
	 * @param RCP_Discount $discount
	 *
	 * @since 3.1
	 * @return string
	 */
	public function column_name( $discount ) {

		$edit_discount_url = add_query_arg( 'edit_discount', urlencode( $discount->get_id() ), $this->get_base_url() );

		// Edit discount.
		$actions = array(
			'edit' => '<a href="' . esc_url( $edit_discount_url ) . '">' . __( 'Edit', 'rcp' ) . '</a>',
		);

		if ( 'active' == $discount->get_status() ) {
			// Deactivate discount.
			$actions['deactivate'] = '<a href="' . esc_url( wp_nonce_url( add_query_arg( array(
					'rcp-action'  => 'deactivate_discount',
					'discount_id' => urlencode( $discount->get_id() )
				), $this->get_base_url() ), 'rcp-deactivate-discount' ) ) . '">' . __( 'Deactivate', 'rcp' ) . '</a>';
		} else {
			// Activate discount.
			$actions['activate'] = '<a href="' . esc_url( wp_nonce_url( add_query_arg( array(
					'rcp-action'  => 'activate_discount',
					'discount_id' => urlencode( $discount->get_id() )
				), $this->get_base_url() ), 'rcp-activate-discount' ) ) . '">' . __( 'Activate', 'rcp' ) . '</a>';
		}

		// Delete discount.
		$actions['delete'] = '<span class="trash"><a href="' . esc_url( wp_nonce_url( add_query_arg( array(
				'rcp-action'  => 'delete_discount_code',
				'discount_id' => urlencode( $discount->get_id() )
			), $this->get_base_url() ), 'rcp-delete-discount' ) ) . '" class="rcp_delete_discount">' . __( 'Delete', 'rcp' ) . '</a></span>';

		// Discount ID.
		$actions['discount_id'] = '<span class="id rcp-id-col">' . sprintf( __( 'ID: %d', 'rcp' ), $discount->get_id() ) . '</span>';

		/**
		 * Filters the row actions.
		 *
		 * @param array  $actions  Default actions.
		 * @param object $discount Discount object.
		 *
		 * @since 3.1
		 */
		$actions = apply_filters( 'rcp_discount_codes_list_table_row_actions', $actions, $discount );

		$final = '<strong><a class="row-title" href="' . esc_url( $edit_discount_url ) . '">' . esc_html( $discount->get_name() ) . '</a></strong>';

		if ( current_user_can( 'rcp_manage_discounts' ) ) {
			$final .= $this->row_actions( $actions );
		}

		return $final;

	}

	/**
	 * Message to be displayed when there are no discount codes.
	 *
	 * @since 3.1
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'No discount codes found.', 'rcp' );
	}

	/**
	 * Retrieve the bulk actions.
	 *
	 * @since 3.1
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'activate'   => __( 'Activate', 'rcp' ),
			'deactivate' => __( 'Deactivate', 'rcp' ),
			'delete'     => __( 'Permanently Delete', 'rcp' )
		);
	}

	/**
	 * Process bulk actions.
	 *
	 * @since 3.1
	 * @return void
	 */
	public function process_bulk_action() {

		// Bail if a nonce was not supplied.
		if ( ! isset( $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-discountcodes' ) ) {
			return;
		}

		$ids = wp_parse_id_list( (array) $this->get_request_var( 'discount_id', false ) );

		// Bail if no IDs
		if ( empty( $ids ) ) {
			return;
		}

		foreach ( $ids as $discount_id ) {
			switch ( $this->current_action() ) {
				case 'activate':
					rcp_update_discount( absint( $discount_id ), array( 'status' => 'active' ) );
					break;

				case 'deactivate':
					rcp_update_discount( absint( $discount_id ), array( 'status' => 'disabled' ) );
					break;

				case 'delete':
					rcp_delete_discount( absint( $discount_id ) );
					break;
			}
		}

		$this->show_admin_notice( $this->current_action(), count( $ids ) );

	}

	/**
	 * Show admin notice for bulk actions.
	 *
	 * @param string $action The action to show the notice for.
	 * @param int    $number Number of objects processed.
	 *
	 * @access private
	 * @since  3.1
	 * @return void
	 */
	private function show_admin_notice( $action, $number = 1 ) {

		$message = '';

		switch ( $action ) {
			case 'activate' :
				$message = _n( 'Discount code activated.', 'Discount codes activated.', $number, 'rcp' );
				break;

			case 'deactivate' :
				$message = _n( 'Discount code deactivated.', 'Discount codes deactivated.', $number, 'rcp' );
				break;

			case 'delete' :
				$message = _n( 'Discount code deleted.', 'Discount codes deleted.', $number, 'rcp' );
				break;
		}

		if ( empty( $message ) ) {
			return;
		}

		echo '<div class="updated"><p>' . $message . '</p></div>';

	}

	/**
	 * Retrieve the discount code counts.
	 *
	 * @since 3.1
	 * @return void
	 */
	public function get_counts() {
		$this->counts = array(
			'total'    => rcp_count_discounts(),
			'active'   => rcp_count_discounts( array( 'status' => 'active' ) ),
			'inactive' => rcp_count_discounts( array( 'status' => 'disabled' ) )
		);
	}

	/**
	 * Retrieve discount codes data.
	 *
	 * @param bool $count Whether or not to get discount code objects (false) or just count the total number (true).
	 *
	 * @since 3.1
	 * @return RCP_Discount[]|int
	 */
	public function discounts_data( $count = false ) {

		$args = array(
			'number'  => $this->per_page,
			'offset'  => $this->get_offset(),
			'status'  => $this->get_status(),
			'search'  => $this->get_search(),
			'orderby' => $this->get_request_var( 'orderby', 'date_modified' ),
			'order'   => strtoupper( $this->get_request_var( 'order', 'DESC' ) )
		);

		// Use `disabled` instead of `inactive`.
		if ( 'inactive' === $args['status'] ) {
			$args['status'] = 'disabled';
		}

		if ( $count ) {
			return rcp_count_discounts( $args );
		}

		return rcp_get_discounts( $args );
	}

	/**
	 * Setup the final data for the table.
	 *
	 * @since 3.1
	 * @return void
	 */
	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $this->discounts_data();

		$total = $this->discounts_data( true );

		// Setup pagination
		$this->set_pagination_args( array(
			'total_items' => $total,
			'per_page'    => $this->per_page,
			'total_pages' => ceil( $total / $this->per_page )
		) );
	}

}
