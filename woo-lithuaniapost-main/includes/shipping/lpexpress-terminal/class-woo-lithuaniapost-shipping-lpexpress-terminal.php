<?php
require_once __DIR__ . '/includes/settings-plan-cost.php';

class Woo_Lithuaniapost_Shipping_Lpexpress_Terminal extends WC_Shipping_Method
{
    const ID = 'woo_lithuaniapost_lpexpress_terminal';

    public $delivery_method;
    public $cost;
    public $delivery_time;
    public $free_shipping_cost;
    public $type;
    public $plan;
    public $parcel_type;
    public $apply_free_shipping_before_discount;

    /**
     * @var Woo_Lithuaniapost_Table_Rates $_table_rates
     */
    protected $_table_rates;

    private $plan_cost;

    /**
     * Constructor.
     *
     * @since 1.0.0
     * @param int $instance_id Shipping method instance ID.
     */
    public function __construct ( $instance_id = 0 )
    {
        $this->id                 = self::ID;
        $this->instance_id        = absint( $instance_id );
        $this->method_title       = __( 'UNISEND', 'woo-lithuaniapost' );
        $this->method_description = __( 'All post services to home, post office or parcel terminal.', 'woo-lithuaniapost' );
        $this->supports           = [
            'shipping-zones',
            'instance-settings',
        ];
        $this->init ();
        $this->_table_rates = new Woo_Lithuaniapost_Table_Rates ( $this->id );
        $this->plan_cost = new Settings_Plan_Cost();

        add_action( 'woocommerce_update_options_shipping_' . $this->id, [ $this, 'process_admin_options' ] );
    }

    /**
     * Init user set variables.
     *
     * @since 1.0.0
     */
    public function init ()
    {
        $this->instance_form_fields = include __DIR__ . '/includes/settings-lpexpress-terminal.php';
        $this->title                = $this->get_option ( 'title' );
        $this->delivery_method      = $this->get_option ( 'delivery_method' );
        $this->cost                 = $this->get_option ( 'cost' );
        $this->delivery_time        = $this->get_option ( 'delivery_time' );
        $this->tax_status           = $this->get_option ( 'tax_status' );
        $this->free_shipping_cost   = $this->get_option ( 'free_shipping_cost' );
        $this->type                 = $this->get_option( 'type', 'class' );
        $this->plan                 = $this->get_option( 'plan' );
        $this->parcel_type          = $this->get_option( 'parcel_type' );

        // Free shipping before discount
        $this->apply_free_shipping_before_discount = $this->get_option ( 'apply_free_shipping_before_discount' );
    }

    /**
     * Allow this method only in lithuania
     *
     * @since 1.0.0
     * @param array $package
     * @return bool
     */
    public function is_available ( $package )
    {
        if (!$this->plan || !$this->parcel_type) {
            return false;
        }
        $postal_code = $package['destination']['postcode'];
        $country_code = $package['destination']['country'];

        $total_weight = 0;

        foreach ($package['contents'] as $content) {
            $quantity = $content['quantity']; // get quantity
            $product = $content['data']; // get the WC_Product_Simple object
            $product_weight = $product->get_weight(); // get the product weight
            if ($product_weight) {
                // Add the line item weight to the total weight calculation
                $total_weight += floatval($product_weight * $quantity);
            }
        }

        $total_weight_in_g = apply_filters('woo_lithuaniapost_admin_util_convert_to_grams', $total_weight);

        $payment_method = $_REQUEST['payment_method'] ?? null;

        $size = apply_filters('woo_lithuaniapost_size_service_resolve_shipping_size', $this->plan, $package);

        //fill request params
        $params['planCode'] = $this->plan;
        $params['parcelType'] = $this->parcel_type;
        $params['size'] = $size;
        $params['weight'] = intval($total_weight_in_g) ?: 1;
        $params['receiverCountryCode'] = $country_code;
        $params['receiverPostalCode'] = $postal_code;
        $params['includeErrors'] = true;
        if ($payment_method == 'cod') {
            $params['services'] = 'cod:1';
        }
        $cache_key = implode("_", $params);
        $cached_result = get_transient($cache_key);
        if ($cached_result !== false) {
            return $cached_result['available'];
        }
        $available = apply_filters('woo_lithuaniapost_api_is_shipping_available', $params);
        $cache_data['available'] = $available;
        set_transient($cache_key, $cache_data, 60 * 5);
        return $available;
    }

    /**
     * Evaluate a cost from a sum/string.
     *
     * @param  string $sum Sum of shipping.
     * @param  array  $args Args, must contain `cost` and `qty` keys. Having `array()` as default is for back compat reasons.
     * @return string
     */
    protected function evaluate_cost ( $sum, $args = array() ) {
        // Add warning for subclasses.
        if ( ! is_array( $args ) || ! array_key_exists( 'qty', $args ) || ! array_key_exists( 'cost', $args ) ) {
            wc_doing_it_wrong( __FUNCTION__, '$args must contain `cost` and `qty` keys.', '4.0.1' );
        }

        include_once WC ()->plugin_path() . '/includes/libraries/class-wc-eval-math.php';

        // Allow 3rd parties to process shipping cost arguments.
        $args           = apply_filters( 'woocommerce_evaluate_shipping_cost_args', $args, $sum, $this );
        $locale         = localeconv();
        $decimals       = array( wc_get_price_decimal_separator(), $locale['decimal_point'], $locale['mon_decimal_point'], ',' );
        $this->fee_cost = $args['cost'];

        // Expand shortcodes.
        add_shortcode( 'fee', array( $this, 'fee' ) );

        $sum = do_shortcode(
            str_replace(
                array(
                    '[qty]',
                    '[cost]',
                ),
                array(
                    $args['qty'],
                    $args['cost'],
                ),
                $sum
            )
        );

        remove_shortcode( 'fee', array( $this, 'fee' ) );

        // Remove whitespace from string.
        $sum = preg_replace( '/\s+/', '', $sum );

        // Remove locale from string.
        $sum = str_replace( $decimals, '.', $sum );

        // Trim invalid start/end characters.
        $sum = rtrim( ltrim( $sum, "\t\n\r\0\x0B+*/" ), "\t\n\r\0\x0B+-*/" );

        // Do the math.
        return $sum ? WC_Eval_Math::evaluate( $sum ) : 0;
    }

    /**
     * Finds and returns shipping classes and the products with said class.
     *
     * @param mixed $package Package of items from cart.
     * @return array
     */
    public function find_shipping_classes ( $package ) {
        $found_shipping_classes = array ();

        foreach ( $package['contents'] as $item_id => $values ) {
            if ( $values['data']->needs_shipping () ) {
                $found_class = $values['data']->get_shipping_class ();

                if ( ! isset( $found_shipping_classes[ $found_class ] ) ) {
                    $found_shipping_classes[ $found_class ] = array();
                }

                $found_shipping_classes[ $found_class ][ $item_id ] = $values;
            }
        }

        return $found_shipping_classes;
    }

    /**
     * Calculate the shipping costs.
     *
     * @param array $packages
     * @return bool
     * @since 1.0.0
     */
    public function calculate_shipping ( $packages = [] )
    {
        // Disable if any product is not allowed
        foreach ( WC ()->cart->get_cart () as $cart ) {
            $product_id         = $cart [ 'product_id' ];
            $disable_shipping   = get_post_meta ( $product_id, 'woo_lithuaniapost_allowed_terminal', true );
            $disable_shipping_to_parcel_locker   = get_post_meta ( $product_id, 'woo_lithuaniapost_allowed_terminal_parcel_locker', true );

            if ( $disable_shipping === 'yes' || ($disable_shipping_to_parcel_locker === 'yes' && $this->plan === 'TERMINAL') ) {
                $product = wc_get_product($product_id);
                if ($product && !$product->is_virtual('yes')) {
                    return false;
                }
            }
        }

        $rate = [
            'id'       => $this->get_rate_id (),
            'label'    => $this->title,
            'cost'     => 0,
            'taxes' => ''
        ];

        // Add shipping class costs.
        $shipping_classes = WC ()->shipping ()->get_shipping_classes ();

        if ( ! empty( $shipping_classes ) ) {
            $found_shipping_classes = $this->find_shipping_classes ( $packages );
            $highest_class_cost     = 0;

            foreach ( $found_shipping_classes as $shipping_class => $products ) {
                // Also handles BW compatibility when slugs were used instead of ids.
                $shipping_class_term = get_term_by( 'slug', $shipping_class, 'product_shipping_class' );
                $class_cost_string   = $shipping_class_term && $shipping_class_term->term_id ? $this->get_option( 'class_cost_' . $shipping_class_term->term_id, $this->get_option( 'class_cost_' . $shipping_class, '' ) ) : $this->get_option( 'no_class_cost', '' );

                if ( '' === $class_cost_string ) {
                    continue;
                }

                $class_cost = $this->evaluate_cost(
                    $class_cost_string,
                    array(
                        'qty'  => array_sum( wp_list_pluck( $products, 'quantity' ) ),
                        'cost' => array_sum( wp_list_pluck( $products, 'line_total' ) ),
                    )
                );

                if ( 'class' === $this->type ) {
                    $rate [ 'cost' ] += $class_cost;
                } else {
                    $highest_class_cost = $class_cost > $highest_class_cost ? $class_cost : $highest_class_cost;
                }
            }

            if ( 'order' === $this->type && $highest_class_cost ) {
                $rate [ 'cost' ] += $highest_class_cost;
            }
        }

        $free_shipping = false;
        // Free shipping from minimal amount
        if ( $this->free_shipping_cost ) {
            // Price with discount
            $cart_subtotal = WC ()->cart->get_subtotal () - WC ()->cart->get_discount_total ();

            // Price plus discount
            if ( $this->apply_free_shipping_before_discount == 'yes' ) {
                $cart_subtotal += WC ()->cart->get_discount_total ();
            }

            if ( $this->free_shipping_cost <= $cart_subtotal ) {
                $free_shipping = true;
            }
        }
        if ($free_shipping === true) {
            $rate ['cost'] = 0;
        } else {
            $calculated_cost = str_replace(',', '.', $this->plan_cost->calculate_shipping($this, $packages));
            if ($calculated_cost === false || !is_numeric($calculated_cost)) {
                return null;
            } else {
                $rate['cost'] = $rate ['cost'] + str_replace(',', '.', $calculated_cost);
            }
        }

        $this->add_rate ( $rate );
    }

    /**
     * Instance options with table rate inputs
     *
     * @since 1.0.0
     */
    public function instance_options ()
    {
        $this->instance_form_fields = $this->plan_cost->init_settings($this);

        ?>
            <table class="form-table">
                <?php $this->generate_settings_html ( $this->instance_form_fields); ?>
            </table>
        <?php
    }

    /**
     * Admin options
     */
    public function admin_options ()
    {
        $this->instance_options();
    }

    /**
     * Admin options HTML
     *
     * @return false|string
     * @since 1.0.0
     */
    public function get_admin_options_html ()
    {
        ob_start();
        $this->instance_options ();
        return ob_get_clean();
    }

    /**
     * Save table rates here and process other options
     *
     * @return bool
     * @since 1.0.0
     */
    public function process_admin_options ()
    {
        $this->instance_form_fields = $this->plan_cost->process_settings($this);

        return parent::process_admin_options ();
    }

    /**
     * Sanitize the cost field.
     *
     * @since 1.0.0
     * @param string $value Unsanitized value.
     * @return string
     */
    public function sanitize_cost ( $value )
    {
        $value = is_null( $value ) ? '' : $value;
        $value = wp_kses_post( trim( wp_unslash( $value ) ) );
        $value = str_replace( [ get_woocommerce_currency_symbol(), html_entity_decode( get_woocommerce_currency_symbol() ) ],
            '', $value );

        return $value;
    }
}
