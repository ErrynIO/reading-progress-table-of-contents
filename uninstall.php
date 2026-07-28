<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/*
 * ====================================================================
 *  MARF AND THE MILDLY INCONVENIENT APOCALYPSE
 *  Part xi of xi
 * ====================================================================
 *  Marf looked towards the road beyond the village. In the distance,
 *  a black tower had appeared beneath a swirling red sky. Another
 *  message materialised.
 *
 *    [ Main Quest Unlocked: Prevent the End of the World ]
 *
 *    Marf sighed. "After breakfast."
 *    Erryn's wings dimmed slightly. "For once, we agree."
 *
 *    --- fin, more or less ---
 *
 * ====================================================================
 */

delete_option( 'rptoc_settings' );
