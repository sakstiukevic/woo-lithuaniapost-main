<?php


class Woo_Lithuaniapost_Country_Rates
{
    /**
     * @var string $_method_id
     */
    protected $_method_id;

    /**
     * Woo_Lithuaniapost_Country_Rates constructor.
     * @param string $method_id
     */
    public function __construct ( $method_id )
    {
        $this->_method_id = $method_id;
    }

    /**
     * Process country rates
     *
     * @param resource $handle
     * @since 1.0.0
     */
    public function process_country_rates ( $handle )
    {
        global $wpdb;

        // Skip first line
        fgets ( $handle );

        // Delete table rates associated with current method
        $wpdb->delete (
            $wpdb->woo_lithuaniapost_country_rates, [
                'method_id' => $this->_method_id
            ]
        );

        // Read data
        while ( ( $data = fgetcsv ( $handle ) ) !== false ) {
            $wpdb->insert (
                $wpdb->woo_lithuaniapost_country_rates, [
                    'method_id' => $this->_method_id,
                    'country' => $data [ 0 ],
                    'price'     => $data [ 1 ]
                ]
            );
        }
    }

    /**
     * Process country rates
     *
     * @param resource $handle
     * @since 1.0.0
     */
    public function process_country_rates_weight ( $handle )
    {
        global $wpdb;

        // Skip first line
        fgets ( $handle );

        // Delete table rates associated with current method
        $wpdb->delete (
            $wpdb->woo_lithuaniapost_country_weight_rates, [
                'method_id' => $this->_method_id
            ]
        );

        // Read data
        while ( ( $data = fgetcsv ( $handle ) ) !== false ) {
            $wpdb->insert (
                $wpdb->woo_lithuaniapost_country_weight_rates, [
                    'method_id' => $this->_method_id,
                    'country' => $data [ 0 ],
                    'weight' => $data [ 1 ],
                    'price'     => $data [ 2 ]
                ]
            );
        }
    }

    /**
     * Calculate country price
     *
     * @param $cost
     * @since 1.0.0
     */
    public function calculate_country_rates ( &$cost )
    {
        global $wpdb;

        // Selected destination of customer
        $selected_destination   = WC ()->customer->get_shipping_country ();

        $price = $wpdb->get_var (
            $wpdb->prepare ( "SELECT price FROM {$wpdb->woo_lithuaniapost_country_rates}
                    WHERE method_id = %s AND country = %s", $this->_method_id, $selected_destination )
        );

        $cost = $price;
    }

    /**
     * Calculate country weight rates
     *
     * @param array $packages
     * @param string $country
     * @param double $cost
     * @since 3.1.3
     */
    public function calculate_country_weight_rates ( $packages, $country, &$cost )
    {
        global $wpdb;

        $current_weight = 0;
        $weights        = [];

        // Use table rates
        $rates = $wpdb->get_results (
            $wpdb->prepare ( "SELECT country, weight, price FROM {$wpdb->woo_lithuaniapost_country_weight_rates}
                WHERE method_id = %s AND country = %s", $this->_method_id, $country ), ARRAY_A
        );

        foreach ( $packages ['contents'] as $item => $value ) {
            $_product = $value [ 'data' ];
            $current_weight += $_product->get_weight () * $value [ 'quantity' ];
        }

        for ( $i = count ( $rates ) - 1; $i >= 0; $i-- ) {
            // Search for weight that fits
            if ( $rates [ $i ]['weight'] >= $current_weight  ) {
                $weights [ $i ] = $rates [ $i ][ 'weight' ];
            }
        }

        if ( ! empty ( $weights ) ) {
            // Result is the minimum weight index
            $result = $rates [ array_search ( min ( $weights ), $weights ) ];
            $cost = $result [ 'price' ];
        }
    }
}
