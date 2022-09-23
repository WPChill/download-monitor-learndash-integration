<?php
class DLM_AMM_Learndash {

	const VERSION = '1.0.1';
	
	/**
	 * Holds the class object.
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * Constructor
	 */
	public function __construct() {

		// Load plugin text domain
		load_plugin_textdomain( 'dlm-learndash-integration', false, dirname( plugin_basename( DLM_AAM_LD_FILE ) ) . '/languages/' );

		if( 'ok' !== $this->core_exists() && $this->is_dlm_admin_page() ){

			add_action( 'admin_notices', array( $this, 'display_notice_core_missing' ), 8 );
			
		}else{

			add_filter( 'dlm_aam_group', array( $this, 'add_groups' ), 15, 1 );
			add_filter( 'dlm_aam_group_value_learndash', array( $this, 'learndash_group_value' ), 15 );
			add_filter( 'dlm_aam_restriction', array( $this, 'restrictions' ), 15, 1 );
			add_filter( 'dlm_aam_rest_variables', array( $this, 'rest_variables' ), 15, 1 );
			add_filter( 'dlm_aam_rule_learndash_applies', array( $this, 'learndash_rule' ), 15, 2 );
		}
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The DLM_AMM_Learndash object.
	 *
	 * @since 1.0.0
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_AMM_Learndash ) ) {
			self::$instance = new DLM_AMM_Learndash();
		}

		return self::$instance;

	}

	/**
	 * Add LearnDash to the list of rules
	 *
	 * @param [type] $groups
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function add_groups( $groups ) {
	
		$groups[] = array(
			'key'        => 'learndash',
			'name'       => esc_html__( 'LearnDash', 'dlm-learndash-integration' ),
			'conditions' => array(
				'includes' => array(
					'restriction' => array( 'null', 'amount', 'global_amount', 'daily_amount', 'monthly_amount', 'daily_global_amount', 'monthly_global_amount', 'date' ),
				),
			),
			'field_type' => 'select',
		);

		return $groups;
	}

	/**
	 * Add LearnDash groups to group values
	 *
	 * @param object $return
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function learndash_group_value( $return ) {

		// Get leardash groups.
		if ( ! function_exists( 'learndash_get_groups' ) ) {
			return '-';
		}

		// LearnDash groups.
		$groups[] = array(
			'key'  => 'null',
			'name' => esc_html__( 'None', 'dlm-learndash-integration' ),
		);

		$learndash_groups = learndash_get_groups();

		// check, loop & add to $roles.
		if ( count( $learndash_groups ) > 0 ) {
			foreach ( $learndash_groups as $group ) {
				$groups[] = array(
					'key'  => $group->ID,
					'name' => $group->post_title,
				);
			}
		}

		return wp_send_json( $groups );
	}

	/**
	 * Add LearnDash to restrictions
	 *
	 * @param array $restrictions
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function restrictions( $restrictions ) {

		foreach ( $restrictions as $key => $restriction ) {
			if ( isset( $restriction['conditions']['includes']['group'] ) ) {
				$restrictions[ $key ]['conditions']['includes']['group'][] = 'learndash';
			}
		}

		 return $restrictions;
	}

	/**
	 * Add LearnDash to rest variables
	 *
	 * @param [type] $rest_variables
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function rest_variables( $rest_variables ) {

        $vars['str_learndash'] = esc_html__( 'LearnDash', 'dlm-learndash-integration' );

		if ( ! function_exists( 'learndash_get_groups' ) ) {
			$rest_variables['learndash_groups'] = '0';
			return $rest_variables;
		}

		// LearnDash groups.
		$groups = array();
		// Get leardash groups.
		$learndash_groups = learndash_get_groups();

		// check, loop & add to $roles.
		if ( count( $learndash_groups ) > 0 ) {
			foreach ( $learndash_groups as $group ) {
				$groups[] = array(
					'key'  => $group->ID,
					'name' => $group->post_title,
				);
			}
		}

		$rest_variables['learndash_groups'] = json_encode( $groups );

		return $rest_variables;
	}

	/**
	 * Add rule for LearnDash
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function learndash_rule( $applies, $rule ) {

		if ( ! function_exists( 'learndash_get_groups' ) || ! function_exists( 'learndash_is_user_in_group' ) || ! function_exists( 'learndash_get_groups_administrators' ) ) {
			return $applies;
		}

		$current_user = wp_get_current_user();
		$group_id     = (int)$rule->get_group_value();

		if ( ( $current_user instanceof WP_User ) && 0 !== $current_user->ID ) {
			// Check if user ID is either in the group users or group leaders
			if ( learndash_is_user_in_group( (int) $current_user->ID, $group_id ) || in_array( $current_user, learndash_get_groups_administrators( $group_id ) ) ) {
				$applies = true;
			}
		}

		return $applies;
	}

	/**
	 * Check if Download Monitor & Download Monitor Advanced Access Manager are installed and active.
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function core_exists() {

		$missing = array();

		// check for Download Monitor
		if( !defined( 'DLM_VERSION' ) ){
			$missing[] = 'missing_dlm';
		}

		// check for DLM Advanced Access Manager
		if( ! class_exists( 'DLM_Advanced_Access_Manager' ) ){
			$missing[] = 'missing_aam';
		}
		
		// check for Restrict Membership Pro
		if ( ! defined( 'LEARNDASH_VERSION' ) ){
			$missing[] = 'missing_lrd';
		}

		if ( 3 == count( $missing ) ) {
			  return 'missing_all';
		}

		if ( 2 == count( $missing ) ) {
			if ( ! array_diff( array( 'missing_dlm', 'missing_aam' ), $missing ) ) {
				return 'missing_dlm_amm';
			}
			if ( ! array_diff( array( 'missing_dlm', 'missing_lrd' ), $missing ) ) {
				return 'missing_dlm_lrd';
			}
			if ( ! array_diff( array( 'missing_aam', 'missing_lrd' ), $missing ) ) {
				return 'missing_amm_lrd';
			}
		}

		if ( 1 == count( $missing ) ) {
			return $missing[0];
		}

		return 'ok';
	}

	/**
	 * Core notice
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function display_notice_core_missing() {

		$dlm_link = '<a href="https://wordpress.org/plugins/download-monitor/" target="_blank"><strong>' . __( 'Download Monitor', 'dlm-learndash-integration' ) . '</strong></a>';
		$lrd_link = '<a href="https://www.learndash.com/" target="_blank"><strong>' . __( 'LearnDash', 'dlm-learndash-integration' ) . '</strong></a>';
		$aam_link = '<a href="https://www.download-monitor.com/extensions/advanced-access-manager/?utm_source=download-monitor&utm_medium=learndash-integration&utm_campaign=upsell" target="_blank"><strong>' . __( 'Download Monitor - Advanced Access Manager', 'dlm-learndash-integration' ) . '</strong></a>';

		$core_exists = $this->core_exists();
		$notice_messages = array(
			'missing_dlm' 		=> sprintf( __( 'Download Monitor & LearnDash integration requires %s in order to work.', 'dlm-learndash-integration' ), $dlm_link ),
			'missing_aam'		=> sprintf( __( 'Download Monitor & LearnDash integration requires %s addon in order to work.', 'dlm-learndash-integration' ), $aam_link ),
			'missing_lrd' 		=> sprintf( __( 'Download Monitor & LearnDash integration requires %s in order to work.', 'dlm-learndash-integration' ), $lrd_link ),
			'missing_dlm_amm' 	=> sprintf( __( 'Download Monitor & LearnDash integration requires %s & %s addon in order to work.', 'dlm-learndash-integration' ), $dlm_link, $aam_link ),
			'missing_dlm_lrd' 	=> sprintf( __( 'Download Monitor & LearnDash integration requires %s & %s plugin in order to work.', 'dlm-learndash-integration' ), $dlm_link, $lrd_link ),
			'missing_amm_lrd' 	=> sprintf( __( 'Download Monitor & LearnDash integration requires %s addon & %s plugin in order to work.', 'dlm-learndash-integration' ), $aam_link, $lrd_link ),
			'missing_all' 		=> sprintf( __( 'Download Monitor & LearnDash integration requires %s & %s addon & %s plugin in order to work.', 'dlm-learndash-integration' ), $dlm_link, $aam_link, $lrd_link ),
		);
		$class = 'notice notice-error';
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), wp_kses_post( $notice_messages[ $core_exists ] ) ); 


	}

	/**
	 * Check if we are on a dlm page
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function is_dlm_admin_page() {
		global $pagenow;

		if( 'plugins.php' === $pagenow || ( isset( $_GET['post_type'] ) && 'dlm_download' === $_GET['post_type'] ) ){
			return true;
		}

		return false;
	}
}