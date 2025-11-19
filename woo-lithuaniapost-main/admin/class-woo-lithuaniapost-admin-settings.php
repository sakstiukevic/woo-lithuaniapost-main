<?php

defined('ABSPATH') || exit;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://post.lt
 * @since      1.0.0
 *
 * @package    Woo_Lithuaniapost
 * @subpackage Woo_Lithuaniapost/admin
 */

require_once plugin_dir_path(dirname(__FILE__)) . 'admin/api/class-woo-lithuaniapost-admin-courier-api.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'admin/api/class-woo-lithuaniapost-admin-address-api.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'admin/api/class-woo-lithuaniapost-admin-sticker-api.php';

/**
 * The admin-specific settings functionality of the plugin.
 *
 * Defines the WooCommerce settings
 *
 * @package    Woo_Lithuaniapost
 * @subpackage Woo_Lithuaniapost/admin
 * @author     AB "Lietuvos Paštas" <info@post.lt>
 */
class Woo_Lithuaniapost_Admin_Settings
{
    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    private $courier_html;

    private $courier_api;

    private $address_api;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct ( $plugin_name, $version )
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->courier_api = new Woo_Lithuaniapost_Admin_Courier_Api($plugin_name, $version);
        $this->address_api = new Woo_Lithuaniapost_Admin_Address_Api($plugin_name, $version);
    }

    /**
     * Create WooCommerce settings section
     *
     * @param array $sections
     * @return array
     * @since 1.0.0
     */
    public function create_settings_section ( $sections )
    {
        $sections [ 'lpsettings' ] = __( 'UNISEND', 'woo-lithuaniapost' );
        return $sections;
    }

    /**
     * Add settings to section
     *
     * @param array $settings
     * @param string $current_section
     * @return array
     * @since 1.0.0
     */
    public function add_settings_section ( $settings, $current_section )
    {
        /**
         * Check if current section is lpsettings
         */
        if ( $current_section == 'lpsettings' ) {
            $lpsettings = [
                [
                    'name' => __( 'UNISEND Shipping Settings', 'woo-lithuaniapost' ),
                    'type' => 'title',
                    'desc' => __( 'The following options are used to configure UNISEND shipping', 'woo-lithuaniapost' ),
                    'id' => 'lpsettings_authorization'
                ],
                /**
                 * Authorization settings
                 */
                // Title
                [
                    'name' => __( 'Authorization', 'woo-lithuaniapost' ),
                    'type' => 'title',
                    'id' => 'lpsettings_authorization'
                ],
                // Username
                [
                    'name' => __( 'Username', 'woo-lithuaniapost' ),
                    'type' => 'text',
                    'id' => 'lpsettings_api_username',
                    'custom_attributes' => [ 'required' => 'required' ]
                ],
                // Password
                [
                    'name' => __( 'Password', 'woo-lithuaniapost' ),
                    'type' => 'password',
                    'id' => 'lpsettings_api_password',
                    'custom_attributes' => [ 'required' => 'required' ]
                ],
                // Section end
                [
                    'type' => 'sectionend',
                    'id' => 'lpsettings_authorization'
                ]
            ];

            // Reveal sender info when credentials created
            if ( get_option ( 'woo_lithuaniapost_module_active' ) ) {
                $sender_address_response = $this->address_api->get_sender_address();
                array_push ($lpsettings,
                    /**
                     * Sender inforamtion
                     */
                    // Title
                    [
                        'name' => __( 'Sender information', 'woo-lithuaniapost' ),
                        'type' => 'title',
                        'id' => 'lpsettings_sender'
                    ],
                    // Name
                    [
                        'name' => __( 'Name', 'woo-lithuaniapost' ),
                        'type' => 'text',
                        'desc_tip' => __( 'Maximum length: 100 characters', 'woo-lithuaniapost'  ),
                        'id' => 'lpsettings_sender_name',
                        'custom_attributes' => ['required' => 'required', 'maxlength' => 100],
                        'value' => @$sender_address_response->name
                    ],
                    // Company name
                    [
                        'name' => __('Company name', 'woo-lithuaniapost'),
                        'type' => 'text',
                        'desc_tip' => __('Maximum length: 100 characters', 'woo-lithuaniapost'),
                        'id' => 'lpsettings_sender_company_name',
                        'custom_attributes' => ['maxlength' => 100],
                        'value' => $sender_address_response->companyName ?? null
                    ],
                    // Phone
                    [
                        'name' => __( 'Phone', 'woo-lithuaniapost' ),
                        'type' => 'text',
                        'desc_tip' => __( 'Format: 370XXXXXXXX', 'woo-lithuaniapost'  ),
                        'id' => 'lpsettings_sender_phone',
                        'custom_attributes' => ['required' => 'required'],
                        'value' => @$sender_address_response->contacts->phone
                    ],
                    // Email
                    [
                        'name' => __( 'Email', 'woo-lithuaniapost' ),
                        'type' => 'email',
                        'desc_tip' => __( 'Maximum length: 128 characters', 'woo-lithuaniapost'  ),
                        'id' => 'lpsettings_sender_email',
                        'custom_attributes' => ['required' => 'required', 'maxlength' => 128],
                        'value' => @$sender_address_response->contacts->email
                    ],
                    // Country
                    [
                        'name' => __( 'Country', 'woo-lithuaniapost' ),
                        'type' => 'select',
                        'options' => [@$sender_address_response->address->countryCode => @$sender_address_response->address->countryCode],
                        'id' => 'lpsettings_sender_country',
                        'custom_attributes' => ['required' => 'required'],
                        'value' => @$sender_address_response->address->countryCode
                    ],
                    // City
                    [
                        'name' => __( 'City', 'woo-lithuaniapost' ),
                        'type' => 'text',
                        'desc_tip' => __( 'Maximum length: 20 characters', 'woo-lithuaniapost'  ),
                        'id' => 'lpsettings_sender_city',
                        'custom_attributes' => ['required' => 'required', 'maxlength' => 20],
                        'value' => @$sender_address_response->address->locality
                    ],
                    // Street
                    [
                        'name' => __( 'Street', 'woo-lithuaniapost' ),
                        'type' => 'text',
                        'desc_tip' => __( 'Maximum length: 50 characters', 'woo-lithuaniapost'  ),
                        'id' => 'lpsettings_sender_street',
                        'custom_attributes' => ['required' => 'required', 'maxlength' => 50],
                        'value' => @$sender_address_response->address->street
                    ],
                    // Building Number
                    [
                        'name' => __( 'Building Number', 'woo-lithuaniapost' ),
                        'type' => 'text',
                        'desc_tip' => __( 'Maximum length: 20 characters', 'woo-lithuaniapost'  ),
                        'id' => 'lpsettings_sender_building',
                        'custom_attributes' => ['required' => 'required', 'maxlength' => 20],
                        'value' => $sender_address_response->address->building ?? null
                    ],
                    // Apartment Number
                    [
                        'name' => __( 'Apartment Number', 'woo-lithuaniapost' ),
                        'type' => 'text',
                        'desc_tip' => __( 'Maximum length: 20 characters', 'woo-lithuaniapost'  ),
                        'id' => 'lpsettings_sender_apartment',
                        'custom_attributes' => ['maxlength' => 20],
                        'value' => $sender_address_response->address->flat ?? null
                    ],
                    // Post Code
                    [
                        'name' => __( 'Post Code', 'woo-lithuaniapost' ),
                        'type' => 'text',
                        'id' => 'lpsettings_sender_postcode',
                        'custom_attributes' => ['required' => 'required'],
                        'value' => @$sender_address_response->address->postalCode
                    ],
                    // Pickup address checkbox
                    [
                        'name' => __( 'Pickup address', 'woo-lithuaniapost' ),
                        'type' => 'checkbox',
                        'id' => 'lpsettings_use_pickup_address',
                        'default' => 'no'
                    ],
                    // Section end
                    [
                        'type' => 'sectionend',
                        'id' => 'lpsettings_sender'
                    ]);
                    $pickup_address_response = $this->address_api->get_pickup_address();
                    if (!$pickup_address_response) {
                        $pickup_address_response = $sender_address_response;
                    }
                    array_push ($lpsettings,
                    /**
                     * Pickup information
                     */
                    // Title
                    [
                        'name' => __( 'Pickup information', 'woo-lithuaniapost' ),
                        'type' => 'title',
                        'id' => 'lpsettings_pickup'
                    ],
                    // Name
                    [
                        'name' => __( 'Name', 'woo-lithuaniapost' ),
                        'type' => 'text',
                        'desc_tip' => __( 'Maximum length: 100 characters', 'woo-lithuaniapost'  ),
                        'id' => 'lpsettings_pickup_name',
                        'custom_attributes' => ['required' => 'required', 'maxlength' => 100],
                        'value' => @$pickup_address_response->name
                    ],
                    // Company name
                    [
                        'name' => __('Company name', 'woo-lithuaniapost'),
                        'type' => 'text',
                        'desc_tip' => __('Maximum length: 100 characters', 'woo-lithuaniapost'),
                        'id' => 'lpsettings_pickup_company_name',
                        'custom_attributes' => ['maxlength' => 100],
                        'value' => $pickup_address_response->companyName ?? null
                    ],
                    // Phone
                    [
                        'name' => __( 'Phone', 'woo-lithuaniapost' ),
                        'type' => 'text',
                        'desc_tip' => __( 'Format: 370XXXXXXXX', 'woo-lithuaniapost'  ),
                        'id' => 'lpsettings_pickup_phone',
                        'custom_attributes' => ['required' => 'required'],
                        'value' => @$pickup_address_response->contacts->phone
                    ],
                    // Email
                    [
                        'name' => __( 'Email', 'woo-lithuaniapost' ),
                        'type' => 'email',
                        'desc_tip' => __( 'Maximum length: 128 characters', 'woo-lithuaniapost'  ),
                        'id' => 'lpsettings_pickup_email',
                        'custom_attributes' => ['required' => 'required', 'maxlength' => 128],
                        'value' => @$pickup_address_response->contacts->email
                    ],
                    // Country
                    [
                        'name' => __( 'Country', 'woo-lithuaniapost' ),
                        'type' => 'select',
                        'options' => [@$pickup_address_response->address->countryCode => @$pickup_address_response->address->countryCode],
                        'id' => 'lpsettings_pickup_country',
                        'custom_attributes' => ['required' => 'required'],
                        'value' => @$pickup_address_response->address->countryCode
                    ],
                    // City
                    [
                        'name' => __( 'City', 'woo-lithuaniapost' ),
                        'type' => 'text',
                        'desc_tip' => __( 'Maximum length: 20 characters', 'woo-lithuaniapost'  ),
                        'id' => 'lpsettings_pickup_city',
                        'custom_attributes' => ['required' => 'required', 'maxlength' => 20],
                        'value' => @$pickup_address_response->address->locality
                    ],
                    // Street
                    [
                        'name' => __( 'Street', 'woo-lithuaniapost' ),
                        'type' => 'text',
                        'desc_tip' => __( 'Maximum length: 50 characters', 'woo-lithuaniapost'  ),
                        'id' => 'lpsettings_pickup_street',
                        'custom_attributes' => ['required' => 'required', 'maxlength' => 50],
                        'value' => @$pickup_address_response->address->street
                    ],
                    // Building Number
                    [
                        'name' => __( 'Building Number', 'woo-lithuaniapost' ),
                        'type' => 'text',
                        'desc_tip' => __( 'Maximum length: 20 characters', 'woo-lithuaniapost'  ),
                        'id' => 'lpsettings_pickup_building',
                        'custom_attributes' => ['required' => 'required', 'maxlength' => 20],
                        'value' => $pickup_address_response->address->building ?? null
                    ],
                    // Apartment Number
                    [
                        'name' => __( 'Apartment Number', 'woo-lithuaniapost' ),
                        'type' => 'text',
                        'desc_tip' => __( 'Maximum length: 20 characters', 'woo-lithuaniapost'  ),
                        'id' => 'lpsettings_pickup_apartment',
                        'custom_attributes' => ['maxlength' => 20],
                        'value' => $pickup_address_response->address->flat ?? null
                    ],
                    // Post Code
                    [
                        'name' => __( 'Post Code', 'woo-lithuaniapost' ),
                        'type' => 'text',
                        'id' => 'lpsettings_pickup_postcode',
                        'custom_attributes' => ['required' => 'required'],
                        'value' => @$pickup_address_response->address->postalCode
                    ],
                    // Section end
                    [
                        'type' => 'sectionend',
                        'id' => 'lpsettings_pickup'
                    ],
                    /**
                     * Dimension settings
                     */
                    // Title
                    [
                        'name' => __('Dimension settings', 'woo-lithuaniapost'),
                        'type' => 'title',
                        'id' => 'lpsettings_dimension'
                    ],
                    // Height
                    [
                        'name' => __('Height (cm)', 'woo-lithuaniapost'),
                        'type' => 'number',
                        'desc_tip' => __('Default product height', 'woo-lithuaniapost'),
                        'id' => 'lpsettings_dimension_height',
                        'default' => 10,
                        'custom_attributes' => array(
                            'step' => 0.001,
                            'min' => 0.01
                        )
                    ],
                    // Width
                    [
                        'name' => __('Width (cm)', 'woo-lithuaniapost'),
                        'type' => 'number',
                        'desc_tip' => __('Default product width', 'woo-lithuaniapost'),
                        'id' => 'lpsettings_dimension_width',
                        'default' => 10,
                        'custom_attributes' => array(
                            'step' => 0.001,
                            'min' => 0.01
                        )
                    ],
                    // Length
                    [
                        'name' => __('Length (cm)', 'woo-lithuaniapost'),
                        'type' => 'number',
                        'desc_tip' => __('Default product length', 'woo-lithuaniapost'),
                        'id' => 'lpsettings_dimension_length',
                        'default' => 10,
                        'custom_attributes' => array(
                            'step' => 0.001,
                            'min' => 0.01
                        )
                    ],
                    // Section end
                    [
                        'type' => 'sectionend',
                        'id' => 'lpsettings_pickup'
                    ],
                    /**
                     * Other settings
                     */
                    // Title
                    [
                        'name' => __( 'Other Settings', 'woo-lithuaniapost' ),
                        'type' => 'title',
                        'id' => 'lpsettings_other'
                    ],
                    [
                        'name' => __( 'Validate shipping address', 'woo-lithuaniapost' ),
                        'type' => 'checkbox',
                        'id' => 'lpsettings_validate_shipping_before_checkout',
                        'default' => 'yes'
                    ],
                    [
                        'name' => __("Change status to 'Completed' when", 'woo-lithuaniapost'),
                        'type' => 'select',
                        'options' => [
                            '' => __('Never', 'woo-lithuaniapost'),
                            LpOrderStatus::$SHIPPING_INITIATED->to_order_status() => _x('Shipment Created', 'Order status', 'woo-lithuaniapost'),
                            LpOrderStatus::$ON_THE_WAY->to_order_status() => _x('On the way', 'Order status', 'woo-lithuaniapost'),
                            LpOrderStatus::$PARCEL_DELIVERED->to_order_status() => _x('Delivered', 'Order status', 'woo-lithuaniapost'),
                        ],
                        'id' => 'lpsettings_event_to_change_status_to_completed',
                        'default' => 'yes'
                    ],
                    [
                        'name' => __("Sent email with order tracking link when", 'woo-lithuaniapost'),
                        'type' => 'select',
                        'options' => [
                            '' => __('Never', 'woo-lithuaniapost'),
                            LpOrderStatus::$SHIPPING_INITIATED->to_order_status() => _x('Shipment Created', 'Order status', 'woo-lithuaniapost'),
                            LpOrderStatus::$ON_THE_WAY->to_order_status() => _x('On the way', 'Order status', 'woo-lithuaniapost'),
                        ],
                        'id' => 'lpsettings_event_to_send_tracking_email',
                        'default' => LpOrderStatus::$ON_THE_WAY->to_order_status(),
                        'desc' => __( 'To include tracking link into already existing WooCommerce email templates add {unisend_tracking_number} text into template content.', 'woo-lithuaniapost' )
                    ],
                    [
                        'name' => __( 'Label Format', 'woo-lithuaniapost' ),
                        'type' => 'select',
                        'options' => [
                            Woo_Lithuaniapost_Admin_Sticker_Api::LAYOUT_10X15  => Woo_Lithuaniapost_Admin_Sticker_Api::LAYOUT_10X15,
                            Woo_Lithuaniapost_Admin_Sticker_Api::LAYOUT_A4 => Woo_Lithuaniapost_Admin_Sticker_Api::LAYOUT_A4,
                            Woo_Lithuaniapost_Admin_Sticker_Api::LAYOUT_MAX    => Woo_Lithuaniapost_Admin_Sticker_Api::LAYOUT_MAX
                        ],
                        'id' => 'lpsettings_other_label_format'
                    ],
                    [
                        'name' => __( 'Label Orientation', 'woo-lithuaniapost' ),
                        'type' => 'select',
                        'options' => [
                            Woo_Lithuaniapost_Admin_Sticker_Api::ORIENTATION_LANDSCAPE  => Woo_Lithuaniapost_Admin_Sticker_Api::ORIENTATION_LANDSCAPE,
                            Woo_Lithuaniapost_Admin_Sticker_Api::ORIENTATION_PORTRAIT => Woo_Lithuaniapost_Admin_Sticker_Api::ORIENTATION_PORTRAIT
                        ],
                        'id' => 'lpsettings_other_label_orientation'
                    ]
                );
                if ($this->courier_api->is_call_required() === true) {
                    array_push($lpsettings,
                        [
                            'type' => 'call_courier_time_picker',
                            'id' => 'lpsettings_courier_call_hours',
                            'sub_type' => 'hours'
                        ],
                        [
                            'type' => 'call_courier_time_picker',
                            'id' => 'lpsettings_courier_call_days',
                            'sub_type' => 'days'
                        ],
                        [
                            'type' => 'call_courier_time_picker',
                            'sub_type' => 'wrapper'
                        ]);
                }
                $lpsettings[] = [
                    'type' => 'sectionend',
                    'id' => 'lpsettings_other'
                ];
            }
            return $lpsettings;
        }

        /**
         * Return default settings
         */
        return $settings;
    }

    /**
     * Get settings options
     *
     * @param $option
     * @param string $prefix
     * @return mixed
     * @since 1.0.0
     */
    public static function get_option ( $option, $prefix = 'lpsettings_' )
    {
        return get_option ( $prefix . $option );
    }

    public static function add_option ( $option, $value, $prefix = 'lpsettings_' )
    {
        return add_option ( $prefix . $option, $value );
    }

    public static function update_option ( $option, $value, $prefix = 'lpsettings_' )
    {
        return update_option ( $prefix . $option, $value );
    }

    public static function delete_option ( $option, $prefix = 'lpsettings_' )
    {
        return delete_option( $prefix . $option );
    }

    /**
     * Get formatted country list for options
     *
     * @param bool $code - if code needed instead of id
     * @return array
     * @since 1.0.0
     */
    public static function get_country_list ()
    {
        return WC()->countries->countries;
    }

    private function apply_generate_select_hours_html($value)
    {
        $this->courier_html .= $this->generate_select_hours_html($value);
    }

    private function apply_generate_select_days_html($value)
    {
        $this->courier_html .= $this->generate_select_days_html($value);
    }

    private function generate_select_days_html($value)
    {
        ob_start();
        ?>
        <input id="lpsettings_courier_call_days" name="lpsettings_courier_call_days"
               value="<?php echo $value['value'] == '-' ? null : esc_attr($value['value']); ?>" , style="display:none;">
        <div class="lpsettings-multiselect" id="lpsettings_call_courier_days_select" multiple="multiple"
             data-target="multi-0">
            <div class="lpsettings-title lpsettings-noselect">
                <span class="lpsettings-text">-</span>
                <span class="lpsettings-close-icon">&times;</span>
                <span class="lpsettings-expand-icon">&plus;</span>
            </div>
            <div class="lpsettings-container">
                <option value="<?php echo __('Monday', 'woo-lithuaniapost') ?>"><?php echo __('Monday', 'woo-lithuaniapost') ?></option>
                <option value="<?php echo __('Tuesday', 'woo-lithuaniapost') ?>"><?php echo __('Tuesday', 'woo-lithuaniapost') ?></option>
                <option value="<?php echo __('Wednesday', 'woo-lithuaniapost') ?>"><?php echo __('Wednesday', 'woo-lithuaniapost') ?></option>
                <option value="<?php echo __('Thursday', 'woo-lithuaniapost') ?>"><?php echo __('Thursday', 'woo-lithuaniapost') ?></option>
                <option value="<?php echo __('Friday', 'woo-lithuaniapost') ?>"><?php echo __('Friday', 'woo-lithuaniapost') ?></option>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_select_hours_options_html($value)
    {
        $selectedValue = $value['value'];
        ob_start();
        for ($i = 7; $i <= 14; $i++) {
            for ($j = 0; $j <= 45; $j += 15) {
                $hour = ($i > 9 ? $i : '0' . $i) . ':' . ($j > 9 ? $j : '0' . $j);
                if ($hour == $selectedValue) {
                    echo "<option selected>" . $hour . "</option>";
                } else {
                    echo "<option>" . $hour . "</option>";
                }
            }
        }
        return ob_get_clean();
    }

    private function generate_select_hours_html($value)
    {
        ob_start();
        ?>
        <select id="lpsettings_courier_call_hours" name="lpsettings_courier_call_hours">
            <?php echo $this->generate_select_hours_options_html($value) ?>
        </select>
        <?php
        return ob_get_clean();
    }

    public function generate_call_courier_time_picker_html($value) {
        $subType = $value['sub_type'];
        if ($subType == 'hours') {
            self::apply_generate_select_hours_html($value);
        } else if ($subType == 'days') {
            self::apply_generate_select_days_html($value);
        } else if ($subType == 'wrapper') {
            ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr($value['id']); ?>"><?php echo __('Call courier automatically', 'woo-lithuaniapost') ?></label>
                </th>
                <td class="forminp forminp-select">
                    <?php echo $this->courier_html; ?>
                </td>
            </tr>
            <?php
        }
	}

    public function plugin_action_links($links)
    {
        $action_links = array(
            'settings' => '<a href="' . admin_url('admin.php?page=wc-settings&tab=shipping&section=lpsettings') . '" aria-label="' . esc_attr__('UNISEND settings', 'woo-lithuaniapost') . '">' . esc_html__('Settings', 'woo-lithuaniapost') . '</a>',
        );
        return array_merge($action_links, $links);
    }
}
