<?php
/**
 * Abstract class for all Views.
 *
 * @since 1.0.0
 *
 * @package MemberDash
 * @subpackage View
 */

/**
 * Abstract class for all Views.
 *
 * All views will extend or inherit from the MS_View class.
 * Methods of this class will prepare and output views.
 *
 * @since 1.0.0
 */
class MS_View extends MS_Hooker {

	/**
	 * The storage of all data associated with this render.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * Flag is set to true while in Simulation mode.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	protected static $is_simulating = false;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data The data what has to be associated with this render.
	 */
	public function __construct( $data = array() ) {
		static $Simulate = null;

		$this->data = $data;

		/**
		 * Actions to execute when constructing the parent View.
		 *
		 * @since 1.0.0
		 * @param object $this The MS_View object.
		 */
		do_action( 'ms_view_construct', $this );

		if ( null === $Simulate && MS_Model_Simulate::can_simulate() ) {
			$Simulate            = MS_Factory::load( 'MS_Model_Simulate' );
			self::$is_simulating = $Simulate->is_simulating();
		}

		$this->run_action( 'wp_enqueue_scripts', 'enqueue_scripts' );
	}

	/**
	 * Displays a note while simulation mode is enabled.
	 *
	 * @since 1.0.0
	 */
	protected function check_simulation() {
		if ( self::$is_simulating ) :
			?>
		<div class="error below-h2">
			<p>
				<strong><?php esc_html_e( 'You are in Simulation mode!', 'memberdash' ); ?></strong>
			</p>
			<p>
				<?php esc_html_e( 'Content displayed here might be altered because of simulated restrictions.', 'memberdash' ); ?><br />
				<?php
				printf(
					wp_kses_post( __( 'We recommend to %1$sExit Simulation%2$s before making any changes!', 'memberdash' ) ),
					'<a href="' . esc_url( MS_Controller_Adminbar::get_simulation_exit_url() ) . '">',
					'</a>'
				);
				?>
			</p>
			<p>
				<em><?php esc_html_e( 'This page is only available to Administrators - you can always see it, even during Simulation.', 'memberdash' ); ?></em>
			</p>
		</div>
			<?php
		endif;
	}

	/**
	 * Displays a warning if network-wide protection is enabled for a large
	 * network.
	 *
	 * @since 1.0.0
	 */
	protected function check_network() {
		if ( MS_Plugin::is_network_wide() && wp_is_large_network() ) :
			?>
			<div class="error below-h2">
			<p>
				<strong><?php esc_html_e( 'Warning!', 'memberdash' ); ?></strong>
			</p>
			<p>
				<?php esc_html_e( 'This network has a large number of sites. Some features of network protection might be slow or unavailable.', 'memberdash' ); ?>
			</p>
			</div>
			<?php
		endif;
	}

	/**
	 * Builds template and outputs it to the browser or returns it as string.
	 *
	 * @since 1.0.0
	 *
	 * @return string|void
	 */
	public function to_html() {
		// This function is implemented different in each child class.
		return apply_filters( 'ms_view_to_html', '' );
	}

	/**
	 * Output the rendered template to the browser.
	 *
	 * @since 1.0.0
	 */
	public function render() {
		$html = $this->to_html();

		echo apply_filters( //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'ms_view_render',
			$html,
			$this
		);
	}

	public function enqueue_scripts() {

	}

	/**
	 * Set the data for this render.
	 *
	 * @since 1.0.3
	 *
	 * @param array<mixed> $data The data to be associated with this render.
	 *
	 * @return void
	 */
	public function set_data( array $data ): void {
		$this->data = $data;
	}
}
