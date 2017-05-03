<?php

/**
 * Create a select element under billing details containing all shipping post titles.
 * Once the user selects a predefined shipping destination, the script defined in the
 * tc_checkout_update_shipping_address() function updates all shipping fields.
 * @depends tc_checkout_update_shipping_address()
 */
add_filter( 'woocommerce_checkout_fields' , 'tc_display_default_shipping_locations' );
function tc_display_default_shipping_locations( $fields ) {

	// Capture add all shipping post titles.
	$shipping_count = 1;
	query_posts( 'post_type=shipping_address' );
	if (have_posts()) {
		$shipping_address_titles['default'] = 'Select or clear...';
		while (have_posts()) {
			the_post();
			$shipping_address_titles[get_the_id()] = get_the_title();
		}
	}

	// Create a select element under billing with all shipping post titles.
	$fields['billing']['pick_up_locations'] = array(
		'type'		=> 'select',
		'options'	=> $shipping_address_titles,
		'label'		=> __('Donate by selecting one of our predefined shipping locations', 'woocommerce'),
		'class'		=> array('form-row-wide'),
		'clear'		=> true
    );

	return $fields;
}

/**
 * Generate a script added to the head which updates shipping fields when one of the
 * default shipping destinations are chosen.
 */
add_action( 'wp_head', 'tc_checkout_update_shipping_address', 10);
function tc_checkout_update_shipping_address() {

	$shipping_addresses[] = '';

	// Capture then JSONify all shipping details
	query_posts( 'post_type=shipping_address' );
	if (have_posts()) {
		while (have_posts()) {
			the_post();

			$post_id = get_the_id();

			// Store shipping addresses in an array referenced by post id.
			foreach([
				'first_name',
				'last_name',
				'company',
				'address',
				'address_extra',
				'town_city',
				'region',
				'postcode',
				'order_notes'
			] as $field) {
				$shipping_addresses[$post_id][$field] = get_post_meta($post_id, $field);
			}
		}

		$shipping_addresses = json_encode($shipping_addresses);
	}
	wp_reset_query();
	?>

	<script type="text/javascript">

		jQuery((function($){
			/*
			 * Set all shipping fields to either what has been passed or an empty string.
			 */
			function updateShippingFields (
				firstName,
				lastName,
				company,
				address,
				addressExtra,
				townCity,
				region,
				postcode,
				orderNotes) {

				var addr = {
					'input#shipping_first_name'	: firstName,
					'input#shipping_last_name'	: lastName,
					'input#shipping_company'	: company,
					'input#shipping_address_1'	: address,
					'input#shipping_address_2'	: addressExtra,
					'input#shipping_city'		: townCity,
					'select#shipping_state'		: region,
					'input#shipping_postcode'	: postcode,
					'textarea#order_comments'	: orderNotes
				};

				for (var selector in addr) {
					$(selector).val(
						addr[selector] != 'undefined' ?
						addr[selector] :
						''
					);
				}
			}

			/*
			 * Cycle through all shipping fields and either enable or disable.
			 * @isDisabled true to enable and false to display shipping fields access.
			 */
			function toggleShippingFieldAccess (is_disabled) {
				[
					$(".shipping_address select[id^='shipping_']"),
					$(".shipping_address input[id^='shipping_']"),
					$("textarea[id='order_comments']"),
				]
				.forEach(function(obj) {
					obj.prop("disabled", !is_disabled);
				});
			}

			/*
			 * Update shipping fields when pickup locations upddated.
			 */
	        $('select#pick_up_locations').live('change', function() {

				var shipping_addresses 	= <?php echo $shipping_addresses; ?>;
	        	var location 			= $('select#pick_up_locations').attr('value');
				var addr 				= shipping_addresses[location];

	        	if (location != 'default' && location != 'undefined') {
	        		// update fields to preset shipping fields
	        		updateShippingFields(
	        			addr.first_name,
	        			addr.last_name,
	        			addr.company,
	        			addr.address,
	        			addr.address_extra,
	        			addr.town_city,
	        			addr.region,
	        			addr.postcode,
	        			addr.order_notes
        			);
        			toggleShippingFieldAccess(false);
	        	} else {
	        		// Reset shipping fields to blank
	        		updateShippingFields();
        			toggleShippingFieldAccess(true);
	        	}
	        });
		})(jQuery));
    </script>
    <?php
}