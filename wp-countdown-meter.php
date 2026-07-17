<?php
/**
 * Plugin Name: WP Countdown Meter
 * Description: Adds a shortcode countdown timer with a progress meter. Supports multiple timers on the same page.
 * Version: 1.0.0
 * Author: Codex
 * License: GPL-2.0-or-later
 * Text Domain: wp-countdown-meter
 *
 * @package WP_Countdown_Meter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPCM_VERSION', '1.0.0' );
define( 'WPCM_PLUGIN_FILE', __FILE__ );
define( 'WPCM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Enqueues assets used by the shortcode.
 *
 * The files are intentionally small and loaded on frontend pages so the
 * shortcode markup is styled reliably even when post content renders after
 * wp_head().
 */
function wpcm_enqueue_assets() {
	wp_enqueue_style(
		'wpcm-countdown-meter',
		WPCM_PLUGIN_URL . 'assets/countdown-meter.css',
		array(),
		WPCM_VERSION
	);

	wp_enqueue_script(
		'wpcm-countdown-meter',
		WPCM_PLUGIN_URL . 'assets/countdown-meter.js',
		array(),
		WPCM_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'wpcm_enqueue_assets' );

/**
 * Parses a local datetime using the WordPress timezone setting.
 *
 * @param string $value Raw datetime string.
 * @return DateTimeImmutable|null
 */
function wpcm_parse_datetime( $value ) {
	$value = trim( (string) $value );

	if ( '' === $value ) {
		return null;
	}

	try {
		return new DateTimeImmutable( $value, wp_timezone() );
	} catch ( Exception $e ) {
		return null;
	}
}

/**
 * Calculates the current timer state.
 *
 * @param DateTimeImmutable $start  Start datetime.
 * @param DateTimeImmutable $target Target datetime.
 * @param DateTimeImmutable $now    Current datetime.
 * @return array{ended:bool,days:int,progress:int}
 */
function wpcm_calculate_state( DateTimeImmutable $start, DateTimeImmutable $target, DateTimeImmutable $now ) {
	$start_ts  = $start->getTimestamp();
	$target_ts = $target->getTimestamp();
	$now_ts    = $now->getTimestamp();

	if ( $now_ts >= $target_ts ) {
		return array(
			'ended'    => true,
			'days'     => 0,
			'progress' => 100,
		);
	}

	$remaining_seconds = max( 0, $target_ts - $now_ts );
	$days_remaining   = (int) ceil( $remaining_seconds / DAY_IN_SECONDS );
	$total_seconds    = max( 1, $target_ts - $start_ts );
	$elapsed_seconds  = min( max( 0, $now_ts - $start_ts ), $total_seconds );
	$progress         = (int) round( ( $elapsed_seconds / $total_seconds ) * 100 );

	return array(
		'ended'    => false,
		'days'     => $days_remaining,
		'progress' => min( 100, max( 0, $progress ) ),
	);
}

/**
 * Renders the countdown shortcode.
 *
 * Usage:
 * [countdown_timer start="2026-08-01 00:00" target="2026-08-31 18:00" label="remaining" red_under="3"]
 *
 * @param array<string,string> $atts Shortcode attributes.
 * @return string
 */
function wpcm_render_countdown_timer( $atts ) {
	$atts = shortcode_atts(
		array(
			'start'     => '',
			'target'    => '',
			'label'     => 'remaining',
			'red_under' => '',
			'end_text'  => '終了',
		),
		(array) $atts,
		'countdown_timer'
	);

	$start  = wpcm_parse_datetime( $atts['start'] );
	$target = wpcm_parse_datetime( $atts['target'] );

	if ( ! $start || ! $target ) {
		return '<p class="wpcm-error">' . esc_html__( 'カウントダウンタイマーの開始日時または終了日時が正しくありません。', 'wp-countdown-meter' ) . '</p>';
	}

	if ( $target <= $start ) {
		return '<p class="wpcm-error">' . esc_html__( 'カウントダウンタイマーの終了日時は開始日時より後にしてください。', 'wp-countdown-meter' ) . '</p>';
	}

	$label     = 'until' === $atts['label'] ? 'until' : 'remaining';
	$prefix    = 'until' === $label ? 'あと' : '残り';
	$red_under = is_numeric( $atts['red_under'] ) ? max( 0, (int) $atts['red_under'] ) : -1;
	$end_text  = sanitize_text_field( $atts['end_text'] );
	$end_text  = '' === $end_text ? '終了' : $end_text;
	$state     = wpcm_calculate_state( $start, $target, current_datetime() );
	$is_red    = ! $state['ended'] && $red_under >= 0 && $state['days'] <= $red_under;

	ob_start();
	?>
	<div
		class="wpcm-countdown"
		data-start="<?php echo esc_attr( $start->format( DateTimeInterface::ATOM ) ); ?>"
		data-target="<?php echo esc_attr( $target->format( DateTimeInterface::ATOM ) ); ?>"
		data-label="<?php echo esc_attr( $label ); ?>"
		data-red-under="<?php echo esc_attr( (string) $red_under ); ?>"
		data-end-text="<?php echo esc_attr( $end_text ); ?>"
	>
		<div class="wpcm-countdown__text" aria-live="polite">
			<?php if ( $state['ended'] ) : ?>
				<span class="wpcm-countdown__ended"><?php echo esc_html( $end_text ); ?></span>
			<?php else : ?>
				<span class="wpcm-countdown__prefix"><?php echo esc_html( $prefix ); ?></span><span class="wpcm-countdown__days<?php echo $is_red ? ' is-red' : ''; ?>"><?php echo esc_html( (string) $state['days'] ); ?></span><span class="wpcm-countdown__suffix">日</span>
			<?php endif; ?>
		</div>
		<div class="wpcm-countdown__meter" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( (string) $state['progress'] ); ?>">
			<span class="wpcm-countdown__bar" style="width: <?php echo esc_attr( (string) $state['progress'] ); ?>%;"></span>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'countdown_timer', 'wpcm_render_countdown_timer' );
