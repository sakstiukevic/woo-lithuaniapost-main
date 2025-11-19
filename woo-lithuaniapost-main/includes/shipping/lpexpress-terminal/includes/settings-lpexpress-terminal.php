<?php
/**
 * Settings for LPEXPRESS - Terminal
 *
 * @link       https://post.lt
 * @since      1.0.0
 *
 * @package    Woo_Lithuaniapost
 * @subpackage Woo_Lithuaniapost/includes/shipping
 */

defined( 'ABSPATH' ) || exit;

$settings = [
    'title'      => [
        'title'       => __( 'Method title', 'woocommerce' ),
        'type'        => 'text',
        'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
        'default'     => __( 'UNISEND', 'woo-lithuaniapost' ),
        'desc_tip'    => true,
    ],
    'plan'       => [
        'title'         => __( 'Shipping plan', 'woo-lithuaniapost' ),
        'type'          => 'select',
    ],
    'parcel_type'       => [
        'title'         => __( 'Parcel type', 'woo-lithuaniapost' ),
        'type'          => 'select',
    ],
    'delivery_time' => [
        'title'         => __( 'Delivery time', 'woo-lithuaniapost' ),
        'type'          => 'text',
        'description'   => __( 'Example: 1 - 4 d.d.', 'woo-lithuaniapost' ),
        'default'       => null,
        'desc_tip'      => true,
    ],
    'cost'       => [
        'title'             => __( 'Shipping rates type', 'woo-lithuaniapost' ),
        'type'          => 'select',
        'options'       => [
            'flat' => __( 'Flat Rate Shipping', 'woo-lithuaniapost' ),
            'weight'  => __( 'Weight Based Shipping', 'woo-lithuaniapost' ),
            'size'  => __( 'Size Based Shipping', 'woo-lithuaniapost' ),
        ],
    ],
    'fixed_cost' => [
        'type'              => 'fixed_cost',
    ],
    'weight_cost' => [
        'type'              => 'weight_cost',
    ],
    'size_cost' => [
        'type'              => 'size_cost',
    ],
    'free_shipping_cost' => [
        'title'             => __( 'Minimal order amount for free shipping', 'woo-lithuaniapost' ),
        'type'              => 'text',
        'default'           => null,
    ],
    'apply_free_shipping_before_discount' => [
        'title' => __( 'Coupon discount', 'woo-lithuaniapost' ),
        'label' => __( 'Apply minimal amount rule before coupon discount', 'woo-lithuaniapost' ),
        'type' => 'checkbox',
        'default' => 'no'
    ],
    'tax_status' => [
        'title' => __( 'Tax status', 'woocommerce' ),
        'type'          => 'select',
        'options'     => [
            'taxable'  => __( 'Taxable', 'woocommerce' ),
            'none'     => _x( 'None', 'Tax status', 'woocommerce' ),
        ],
    ]
];

$shipping_classes = WC()->shipping()->get_shipping_classes();

if ( ! empty( $shipping_classes ) ) {
    $settings['class_costs'] = array(
        'title'       => __( 'Shipping class costs', 'woocommerce' ),
        'type'        => 'title',
        'default'     => '',
        /* translators: %s: URL for link. */
        'description' => sprintf( __( 'These costs can optionally be added based on the <a href="%s">product shipping class</a>.', 'woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=shipping&section=classes' ) ),
    );
    foreach ( $shipping_classes as $shipping_class ) {
        if ( ! isset( $shipping_class->term_id ) ) {
            continue;
        }
        $settings[ 'class_cost_' . $shipping_class->term_id ] = array(
            /* translators: %s: shipping class name */
            'title'             => sprintf( __( '"%s" shipping class cost', 'woocommerce' ), esc_html( $shipping_class->name ) ),
            'type'              => 'text',
            'placeholder'       => __( 'N/A', 'woocommerce' ),
            'default'           => $this->get_option( 'class_cost_' . $shipping_class->slug ), // Before 2.5.0, we used slug here which caused issues with long setting names.
            'desc_tip'          => true,
            'sanitize_callback' => array( $this, 'sanitize_cost' ),
        );
    }

    $settings['no_class_cost'] = array(
        'title'             => __( 'No shipping class cost', 'woocommerce' ),
        'type'              => 'text',
        'placeholder'       => __( 'N/A', 'woocommerce' ),
        'default'           => '',
        'desc_tip'          => true,
        'sanitize_callback' => array( $this, 'sanitize_cost' ),
    );

    $settings['type'] = array(
        'title'   => __( 'Calculation type', 'woocommerce' ),
        'type'    => 'select',
        'class'   => 'wc-enhanced-select',
        'default' => 'class',
        'options' => array(
            'class' => __( 'Per class: Charge shipping for each shipping class individually', 'woocommerce' ),
            'order' => __( 'Per order: Charge shipping for the most expensive shipping class', 'woocommerce' ),
        ),
    );
}

return $settings;
