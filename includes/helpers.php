<?php
/**
 * SoulTether — Template helpers
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function soultether_time_ago( string $datetime ): string {
    $now  = new DateTime();
    $then = new DateTime( $datetime );
    $diff = $now->diff( $then );

    if ( $diff->days === 0 ) {
        if ( $diff->h === 0 ) {
            return $diff->i <= 1 ? 'just now' : "{$diff->i} minutes ago";
        }
        return $diff->h === 1 ? '1 hour ago' : "{$diff->h} hours ago";
    }
    if ( $diff->days === 1 ) return 'yesterday';
    if ( $diff->days < 7  ) return "{$diff->days} days ago";
    if ( $diff->days < 30 ) {
        $weeks = round( $diff->days / 7 );
        return $weeks === 1 ? '1 week ago' : "{$weeks} weeks ago";
    }
    return $then->format( 'M j, Y' );
}

function st_e( $value ): void {
    echo esc_html( $value );
}
