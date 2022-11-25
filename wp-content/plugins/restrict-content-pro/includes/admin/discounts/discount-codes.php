<?php
/**
 * Discount Codes Page
 *
 * @package     Restrict Content Pro
 * @subpackage  Admin/Discount Codes
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Render the discounts table
 *
 * @return void
 */
function rcp_discounts_page() {
	include_once RCP_PLUGIN_DIR . 'includes/admin/discounts/class-discount-codes-table.php';

	$table_class = new \RCP\Admin\Discount_Codes_Table();
	$table_class->prepare_items();
	?>
	<div class="wrap">
		<?php if( isset( $_GET['edit_discount'] ) ) :
			include('edit-discount.php');
		else : ?>
			<h1><?php _e( 'Discount Codes', 'rcp' ); ?></h1>

			<form id="rcp-discount-codes-filter" method="GET" action="<?php echo esc_url( add_query_arg( 'page', 'rcp-discounts', admin_url( 'admin.php' ) ) ); ?>">
				<input type="hidden" name="page" value="rcp-discounts"/>
				<?php
				$table_class->views();
				$table_class->search_box( __( 'Search discount codes', 'rcp' ), 'rcp-membership-levels' );
				$table_class->display();
				?>
			</form>

			<?php do_action( 'rcp_discounts_below_table' ); ?>
			<?php if( current_user_can( 'rcp_manage_discounts' ) ) : ?>
				<h2><?php _e( 'Add New Discount', 'rcp' ); ?></h2>
				<form id="rcp-discounts" action="" method="POST">
					<table class="form-table">
						<tbody>
							<tr class="form-field">
								<th scope="row" valign="top">
									<label for="rcp-name"><?php _e( 'Name', 'rcp' ); ?></label>
								</th>
								<td>
									<input name="name" id="rcp-name" type="text" value=""/>
									<p class="description"><?php _e( 'The name of this discount', 'rcp' ); ?></p>
								</td>
							</tr>
							<tr class="form-field">
								<th scope="row" valign="top">
									<label for="rcp-description"><?php _e( 'Description', 'rcp' ); ?></label>
								</th>
								<td>
									<textarea name="description" id="rcp-description"></textarea>
									<p class="description"><?php _e( 'The description of this discount code', 'rcp' ); ?></p>
								</td>
							</tr>
							<tr class="form-field">
								<th scope="row" valign="top">
									<label for="rcp-code"><?php _e( 'Code', 'rcp' ); ?></label>
								</th>
								<td>
									<input type="text" id="rcp-code" name="code" value=""/>
									<p class="description"><?php _e( 'Enter a code for this discount, such as 10PERCENT. Excluding special characters', 'rcp' ); ?></p>
								</td>
							</tr>
							<tr class="form-field">
								<th scope="row" valign="top">
									<label for="rcp-unit"><?php _e( 'Type', 'rcp' ); ?></label>
								</th>
								<td>
									<select name="unit" id="rcp-duration-unit">
										<option value="%"><?php _e( 'Percentage', 'rcp' ); ?></option>
										<option value="flat"><?php _e( 'Flat amount', 'rcp' ); ?></option>
									</select>
									<p class="description"><?php _e( 'The kind of discount to apply for this discount.', 'rcp' ); ?></p>
								</td>
							</tr>
							<tr class="form-field">
								<th scope="row" valign="top">
									<label for="rcp-amount"><?php _e( 'Amount', 'rcp' ); ?></label>
								</th>
								<td>
									<input type="text" id="rcp-amount" name="amount" value=""/>
									<p class="description"><?php _e( 'The amount of this discount code.', 'rcp' ); ?></p>
								</td>
							</tr>
							<tr class="form-field">
								<th scope="row" valign="top">
									<label for="rcp-discount-one-time"><?php _e( 'One Time', 'rcp' ); ?></label>
								</th>
								<td>
									<input type="checkbox" value="1" name="one_time" id="rcp-discount-one-time"/>
									<span class="description"><?php _e( 'Check this to make this discount only apply to the first payment in a membership. Note one-time discounts cannot be used in conjunction with free trials. When this option is not enabled, the discount code will apply to all payments in a membership instead of just the initial payment.', 'rcp' ); ?></span>
								</td>
							</tr>
							<tr class="form-field">
								<th scope="row" valign="top">
									<label for="rcp-subscription"><?php _e( 'Membership Levels', 'rcp' ); ?></label>
								</th>
								<td>
									<?php
									$levels = rcp_get_membership_levels( array( 'number' => 999 ) );
									if( $levels ) {
										foreach ( $levels as $level ) : ?>
											<input type="checkbox" id="rcp-membership-levels-<?php echo esc_attr( $level->get_id() ); ?>" name="membership_levels[]" value="<?php echo esc_attr( $level->id ) ?>">
											<label for="rcp-membership-levels-<?php echo esc_attr( $level->get_id() ); ?>"><?php echo esc_html( $level->get_name() ); ?></label>
											<br>
										<?php
										endforeach;
										?>
										<p class="description"><?php _e( 'The membership levels this discount code can be used for. Leave blank for all levels.', 'rcp' ); ?></p>
										<?php
									} else {
										echo '<p class="description">' . __( 'No membership levels created yet. This discount will be available to use with all future membership levels.', 'rcp' ) . '</p>';
									}
									?>
								</td>
							</tr>
							<tr class="form-field">
								<th scope="row" valign="top">
									<label for="rcp-expiration"><?php _e( 'Expiration date', 'rcp' ); ?></label>
								</th>
								<td>
									<input name="expiration" id="rcp-expiration" type="text" class="rcp-datetimepicker"/>
									<p class="description"><?php _e( 'Enter the expiration date for this discount code in the format of yyyy-mm-dd hh:mm:ss. For no expiration, leave blank', 'rcp' ); ?></p>
								</td>
							</tr>
							<tr class="form-field">
								<th scope="row" valign="top">
									<label for="rcp-max-uses"><?php _e( 'Max Uses', 'rcp' ); ?></label>
								</th>
								<td>
									<input type="text" id="rcp-max-uses" name="max" value=""/>
									<p class="description"><?php _e( 'The maximum number of times this discount can be used. Leave blank for unlimited.', 'rcp' ); ?></p>
								</td>
							</tr>
							<?php do_action( 'rcp_add_discount_form' ); ?>
						</tbody>
					</table>
					<p class="submit">
						<input type="hidden" name="rcp-action" value="add-discount"/>
						<input type="submit" value="<?php _e( 'Add Discount Code', 'rcp' ); ?>" class="button-primary"/>
					</p>
					<?php wp_nonce_field( 'rcp_add_discount_nonce', 'rcp_add_discount_nonce' ); ?>
				</form>
			<?php endif; ?>
		<?php endif; ?>
	</div><!--end wrap-->

	<?php
}
