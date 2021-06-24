<?php
/**
 * Plugin Name: WooCommerce Min Max Quantities
 * Plugin URI:  https://pluginever.com/plugins/woocommerce-min-max-quantities-pro/
 * Description: The plugin allows you to Set minimum and maximum allowable product quantities and price per product and order.
 * Version:     1.0.9
 * Author:      pluginever
 * Author URI:  https://www.pluginever.com
 * Donate link: https://www.pluginever.com
 * License:     GPLv2+
 * Text Domain: wc-minmax-quantities
 * Domain Path: /i18n/languages/
 * Tested up to: 5.4
 * WC requires at least: 3.0.0
 * WC tested up to: 4.0.1
 */

/**
 * Copyright (c) 2019 pluginever (email : support@pluginever.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Main initiation class
 *
 * @since 1.0.0
 */

/**
 * Main WC_MINMAX Class.
 *
 * @class WC_MINMAX
 */
final class WC_MINMAX {
	/**
	 * The single instance of the class.
	 *
	 * @since 1.0.0
	 *
	 * @var WC_MINMAX
	 */
	protected static $instance = null;

	/**
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $plugin_name = 'WooCommerce Min Max Quantities';
	/**
	 * WC_MINMAX version.
	 *
	 * @var string
	 */
	public $version = '1.0.9';

	/**
	 * admin notices
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $notices = array();

	/**
	 * Main WC_MINMAX Instance.
	 *
	 * Ensures only one instance of WC_MINMAX is loaded or can be loaded.
	 *
	 * @return WC_MINMAX - Main instance.
	 *
	 * @static
	 * @since 1.0.0
	 *
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->setup();
		}

		return self::$instance;
	}

	/**
	 * EverProjects Constructor.
	 */
	public function setup() {
		$this->define_constants();
		add_action( 'woocommerce_loaded', array( $this, 'init_plugin' ) );
		add_action( 'admin_notices', array( $this, 'woocommerce_admin_notices' ) );
		add_action( 'init', array( $this, 'localization_setup' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ), 15 );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
	}

	/**
	 * Define EverProjects Constants.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 *
	 */
	private function define_constants() {
		define( 'WC_MINMAX_VERSION', $this->version );
		define( 'WC_MINMAX_FILE', __FILE__ );
		define( 'WC_MINMAX_PATH', dirname( WC_MINMAX_FILE ) );
		define( 'WC_MINMAX_INCLUDES', WC_MINMAX_PATH . '/includes' );
		define( 'WC_MINMAX_URL', plugins_url( '', WC_MINMAX_INCLUDES ) );
		define( 'WC_MINMAX_ASSETS_URL', WC_MINMAX_URL . '/assets' );
		define( 'WC_MINMAX_TEMPLATES_DIR', WC_MINMAX_PATH . '/templates' );
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	public function includes() {
		if ( ! $this->is_wc_installed() ) {

			return;
		}
		//core includes
		include_once WC_MINMAX_INCLUDES . '/core-functions.php';
		include_once WC_MINMAX_INCLUDES . '/class-install.php';
		include_once WC_MINMAX_INCLUDES . '/class-upgrades.php';

		//admin includes
		if ( $this->is_request( 'admin' ) ) {
			//require_once WC_MINMAX_INCLUDES . '/admin/class-settings-api.php';
			require_once WC_MINMAX_INCLUDES . '/admin/class-settings.php';
			require_once WC_MINMAX_INCLUDES . '/admin/class-wc-minmax-quantites-admin-settings.php';
			require_once WC_MINMAX_INCLUDES . '/admin/metabox-functions.php';
		}

		//admin
		if ( ! $this->is_pro_installed() ) {
			require_once( WC_MINMAX_INCLUDES . '/admin/class-promotion.php' );
		}

		do_action( 'wc_minmax_quantities_loaded' );
	}

	/**
	 * What type of request is this?
	 *
	 * @param string $type admin, ajax, cron or frontend.
	 *
	 * @return string
	 */
	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) && ! defined( 'REST_REQUEST' );
		}
	}
	/**
	 * Initialize plugin for localization
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	public function localization_setup() {
		load_plugin_textdomain( 'wc-minmax-quantities', false, plugin_basename( dirname( __FILE__ ) ) . '/i18n/languages' );
	}

	/**
	 * Adds an admin notice to be displayed.
	 *
	 * @param string $class the notice class
	 * @param string $message the notice message body
	 *
	 * @since 1.0.0
	 *
	 */
	public function add_notice( $class, $message ) {

		$notices = get_option( sanitize_key( $this->plugin_name ), [] );
		if ( is_string( $message ) && is_string( $class ) && ! wp_list_filter( $notices, array( 'message' => $message ) ) ) {

			$notices[] = array(
				'message' => $message,
				'class'   => $class
			);

			update_option( sanitize_key( $this->plugin_name ), $notices );
		}

	}

	/**
	 * Displays any admin notices added
	 *
	 * @since 1.0.0
	 *
	 * @internal
	 */
	public function admin_notices() {
		$notices = (array) array_merge( $this->notices, get_option( sanitize_key( $this->plugin_name ), [] ) );
		foreach ( $notices as $notice_key => $notice ) :
			?>
			<div class="notice notice-<?php echo sanitize_html_class( $notice['class'] ); ?>">
				<p><?php echo wp_kses( $notice['message'], array(
						'a'      => array( 'href' => array() ),
						'strong' => array()
					) ); ?></p>
			</div>
			<?php
			update_option( sanitize_key( $this->plugin_name ), [] );
		endforeach;
	}

	/**
	 * Determines if the woocommerce installed.
	 *
	 * @return bool
	 * @since 1.0.0
	 *
	 */
	public function is_wc_installed() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		return is_plugin_active( 'woocommerce/woocommerce.php' ) == true;
	}

	/**
	 * Adds notices if the wocoomerce is not activated
	 *
	 * @since 1.0.6
	 * @internal
	 *
	 */
	public function woocommerce_admin_notices() {
		if ( false === is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			$message = sprintf( __( '<strong>WooCommerce Min Max Quantities</strong> requires <strong>WooCommerce</strong> installed and activated. Please Install %s WooCommerce. %s', 'wc-serial-numbers' ),
			'<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">', '</a>' );
			echo sprintf( '<div class="notice notice-error"><p>%s</p></div>', $message );

		}
	}

	/**
	 * Determines if the pro version installed.
	 *
	 * @return bool
	 * @since 1.0.0
	 *
	 */
	public static function is_pro_installed() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		return is_plugin_active( 'wc-min-max-quantities-pro/wc-minmax-quantities-pro.php' ) == true;
	}

	/**
	 * Plugin action links
	 *
	 * @param array $links
	 *
	 * @return array
	 * @since 1.0.0
	 *
	 */
	public function plugin_action_links( $links ) {
		$links[] = '<a href="' . admin_url( 'admin.php?page=wc-minmax-quantities' ) . '">' . __( 'Settings', 'wc-minmax-quantities' ) . '</a>';
		if ( ! self::is_pro_installed() ) {
			$links['Upgrade'] = '<a target="_blank" href="https://pluginever.com/plugins/woocommerce-min-max-quantities-pro/" title="' . esc_attr( __( 'Upgrade To Pro', 'wc-minmax-quantities' ) ) . '" style="color:red;font-weight:bold;">' . __( 'Upgrade To Pro', 'wc-minmax-quantities' ) . '</a>';
		}

		return $links;
	}

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 * @since 1.0.0
	 *
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 * @since 1.0.0
	 *
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Plugin base path name getter.
	 *
	 * @return string
	 * @since 1.1.0
	 */
	public function plugin_basename() {
		return plugin_basename( __FILE__ );
	}

	/**
	 * Get the template path.
	 *
	 * @return string
	 */
	public function template_path() {
		return WC_MINMAX_TEMPLATES_DIR;
	}

	public function init_plugin() {
		$this->includes();
	}

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @access protected
	 * @return void
	 */

	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wc-minmax-quantities' ), '1.0.0' );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @access protected
	 * @return void
	 */

	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wc-minmax-quantities' ), '1.0.0' );
	}

	/**
	 * Add plugin docs links in plugin row links
	 *
	 * @param mixed $links Links
	 * @param mixed $file File
	 *
	 * @return array
	 * @since 1.0.10
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( plugin_basename( __FILE__ ) === $file ) {

			$row_meta = array(
				'docs' => '<a href="' . esc_url( apply_filters( 'wc_min_max_quantities_docs_url', 'https://pluginever.com/docs/min-max-quantities-for-woocommerce/' ) ) . '" aria-label="' . esc_attr__( 'View documentation', 'wc-minmax-quantities' ) . '">' . esc_html__( 'Docs', 'wc-minmax-quantities' ) . '</a>',
			);

			return array_merge( $links, $row_meta );
		}

		return $links;
	}

}

function wc_minmax_quantities() {
	return WC_MINMAX::instance();
}

wc_minmax_quantities();

