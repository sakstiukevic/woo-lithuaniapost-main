<?php


class Woo_Lithuaniapost_Table_Rates
{
    /**
     * @var string $_method_id
     */
    protected $_method_id;

    /**
     * Woo_Lithuaniapost_Table_Rates constructor.
     * @param string $method_id
     */
    public function __construct ( $method_id )
    {
        $this->_method_id = $method_id;
    }

    /**
     * Process table rates
     *
     * @param string $method_id
     * @param resource $handle
     * @since 1.0.0
     */
    public function process_table_rates ( $handle )
    {
        global $wpdb;

        // Skip first line
        fgets ( $handle );

        // Delete table rates associated with current method
        $wpdb->delete (
            $wpdb->woo_lithuaniapost_table_rates, [
                'method_id' => $this->_method_id
            ]
        );

        // Read data
        while ( ( $data = fgetcsv ( $handle ) ) !== false ) {
            $wpdb->insert (
                $wpdb->woo_lithuaniapost_table_rates, [
                    'method_id' => $this->_method_id,
                    'weight_to' => $data [ 0 ],
                    'price'     => $data [ 1 ]
                ]
            );
        }
    }

    /**
     * Calculate rates
     *
     * @param $packages
     * @param $cost
     * @since 1.0.0
     */
    public function calculate_table_rates ( $packages, &$cost )
    {
        global $wpdb;

        $current_weight = 0;
        $weights        = [];

        // Use table rates
        $rates = $wpdb->get_results (
            $wpdb->prepare ( "SELECT weight_to, price FROM {$wpdb->woo_lithuaniapost_table_rates}
                WHERE method_id = %s", $this->_method_id ), ARRAY_A
        );

        foreach ( $packages ['contents'] as $item => $value ) {
            $_product = $value [ 'data' ];
            $current_weight += $_product->get_weight () * $value [ 'quantity' ];
        }

        for ( $i = count ( $rates ) - 1; $i >= 0; $i-- ) {
            // Search for weight that fits
            if ( $rates [ $i ]['weight_to'] >= $current_weight  ) {
                $weights [ $i ] = $rates [ $i ][ 'weight_to' ];
            }
        }

        if ( ! empty ( $weights ) ) {
            // Result is the minimum weight index
            $result = $rates [ array_search ( min ( $weights ), $weights ) ];
            $cost = $result [ 'price' ];
        }
    }
}
