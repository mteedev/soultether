<?php
/**
 * SoulTether_Core
 * Business logic — orchestrates partner operations between
 * the WP/w4os user, SoulTether_Partner_DB, and SoulTether_Robust_DB.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class SoulTether_Core {

    private SoulTether_Partner_DB $pdb;
    private string $null_uuid = '00000000-0000-0000-0000-000000000000';

    public function __construct() {
        $this->pdb = new SoulTether_Partner_DB();
    }

    // ─── Current user helpers ─────────────────────────────────────────────────

    public function get_current_uuid(): ?string {
        if ( ! is_user_logged_in() ) return null;
        $uuid = get_user_meta( get_current_user_id(), 'w4os_uuid', true );
        return ( $uuid && $uuid !== $this->null_uuid ) ? sanitize_text_field( $uuid ) : null;
    }

    public function get_current_name(): string {
        $uid  = get_current_user_id();
        $name = get_user_meta( $uid, 'w4os_avatarname', true );
        if ( ! $name ) {
            $first = get_user_meta( $uid, 'w4os_firstname', true );
            $last  = get_user_meta( $uid, 'w4os_lastname',  true );
            $name  = trim( "$first $last" );
        }
        return $name ?: wp_get_current_user()->display_name;
    }

    // ─── Send a partner request ───────────────────────────────────────────────

    public function send_request( string $target_uuid, string $message = '' ): array {
        $my_uuid = $this->get_current_uuid();
        if ( ! $my_uuid ) {
            return $this->error( 'You must be logged in with a linked avatar to send a partner request.' );
        }

        if ( $my_uuid === $target_uuid ) {
            return $this->error( 'You cannot send a partner request to yourself.' );
        }

        $target_name = SoulTether_Robust_DB::get_avatar_name( $target_uuid );
        if ( ! $target_name ) {
            return $this->error( 'That avatar could not be found on the grid.' );
        }

        $existing_partner = SoulTether_Robust_DB::get_partner_uuid( $my_uuid );
        if ( $existing_partner ) {
            return $this->error( 'You already have an active partner. Untether first before sending a new request.' );
        }

        $target_existing = SoulTether_Robust_DB::get_partner_uuid( $target_uuid );
        if ( $target_existing ) {
            return $this->error( "{$target_name} already has an active partner." );
        }

        if ( $this->pdb->pending_exists( $my_uuid, $target_uuid ) ) {
            return $this->error( 'A pending request already exists between you and ' . $target_name . '.' );
        }

        $my_name = $this->get_current_name();
        $id = $this->pdb->create_request( $my_uuid, $my_name, $target_uuid, $target_name, $message );
        if ( ! $id ) {
            return $this->error( 'Failed to save the partner request. Please try again.' );
        }

        $this->notify_request( $target_uuid, $target_name, $my_name, $message );

        return $this->success( "Partner request sent to {$target_name}! They will need to log in and approve it." );
    }

    // ─── Withdraw an outgoing request ─────────────────────────────────────────

    public function withdraw_request( int $request_id ): array {
        $my_uuid = $this->get_current_uuid();
        if ( ! $my_uuid ) {
            return $this->error( 'You must be logged in with a linked avatar.' );
        }

        $request = $this->pdb->get_request( $request_id );
        if ( ! $request ) {
            return $this->error( 'Request not found.' );
        }

        if ( $request['requester_uuid'] !== $my_uuid ) {
            return $this->error( 'You can only withdraw requests that you sent.' );
        }

        if ( $request['status'] !== 'pending' ) {
            return $this->error( 'This request is no longer pending and cannot be withdrawn.' );
        }

        $this->pdb->update_status( $request_id, 'denied' );

        return $this->success( "Partner request to {$request['target_name']} has been withdrawn." );
    }

    // ─── Respond to a request (approve or deny) ───────────────────────────────

    public function respond_request( int $request_id, string $action ): array {
        $my_uuid = $this->get_current_uuid();
        if ( ! $my_uuid ) {
            return $this->error( 'You must be logged in with a linked avatar.' );
        }

        if ( ! in_array( $action, [ 'approved', 'denied' ], true ) ) {
            return $this->error( 'Invalid action.' );
        }

        $request = $this->pdb->get_request( $request_id );
        if ( ! $request ) {
            return $this->error( 'Request not found.' );
        }

        if ( $request['target_uuid'] !== $my_uuid ) {
            return $this->error( 'You are not the recipient of this request.' );
        }

        if ( $request['status'] !== 'pending' ) {
            return $this->error( 'This request is no longer pending.' );
        }

        if ( $action === 'denied' ) {
            $this->pdb->update_status( $request_id, 'denied' );
            return $this->success( 'Partner request declined.' );
        }

        // APPROVE — write to Robust DB both sides
        $requester_uuid = $request['requester_uuid'];
        $target_uuid    = $my_uuid;

        $ok1 = SoulTether_Robust_DB::set_partner_uuid( $requester_uuid, $target_uuid );
        $ok2 = SoulTether_Robust_DB::set_partner_uuid( $target_uuid, $requester_uuid );

        if ( ! $ok1 || ! $ok2 ) {
            if ( $ok1 ) SoulTether_Robust_DB::set_partner_uuid( $requester_uuid, $this->null_uuid );
            if ( $ok2 ) SoulTether_Robust_DB::set_partner_uuid( $target_uuid,    $this->null_uuid );
            return $this->error( 'Failed to write partnership to the grid database. Please try again or contact an administrator.' );
        }

        $this->pdb->update_status( $request_id, 'approved' );

        return $this->success( "You are now partnered with {$request['requester_name']}! Your partnership will appear on your in-world profile." );
    }

    // ─── Untether (dissolve) a partnership ────────────────────────────────────

    public function dissolve_partnership(): array {
        $my_uuid = $this->get_current_uuid();
        if ( ! $my_uuid ) {
            return $this->error( 'You must be logged in with a linked avatar.' );
        }

        $partner_uuid = SoulTether_Robust_DB::get_partner_uuid( $my_uuid );
        if ( ! $partner_uuid ) {
            return $this->error( 'You do not currently have an active partner.' );
        }

        $partner_name = SoulTether_Robust_DB::get_avatar_name( $partner_uuid ) ?? 'your partner';

        $ok1 = SoulTether_Robust_DB::set_partner_uuid( $my_uuid,      $this->null_uuid );
        $ok2 = SoulTether_Robust_DB::set_partner_uuid( $partner_uuid, $this->null_uuid );

        if ( ! $ok1 || ! $ok2 ) {
            return $this->error( 'Failed to untether in the grid database. Please contact an administrator.' );
        }

        $this->pdb->dissolve_all( $my_uuid, $partner_uuid );

        return $this->success( "Your soul tether with {$partner_name} has been released." );
    }

    // ─── Render dashboard (shortcode) ─────────────────────────────────────────

    public function render_dashboard(): void {
        $my_uuid = $this->get_current_uuid();

        if ( ! is_user_logged_in() ) {
            require SOULTETHER_PATH . 'templates/not-logged-in.php';
            return;
        }

        if ( ! $my_uuid ) {
            require SOULTETHER_PATH . 'templates/no-avatar.php';
            return;
        }

        $my_name      = $this->get_current_name();
        $partner_uuid = SoulTether_Robust_DB::get_partner_uuid( $my_uuid );
        $partner_name = $partner_uuid ? SoulTether_Robust_DB::get_avatar_name( $partner_uuid ) : null;
        $incoming     = $this->pdb->get_incoming_pending( $my_uuid );
        $outgoing     = $this->pdb->get_outgoing_pending( $my_uuid );
        $friends      = SoulTether_Robust_DB::get_friends( $my_uuid );

        require SOULTETHER_PATH . 'templates/dashboard.php';
    }

    // ─── Email notification ───────────────────────────────────────────────────

    private function notify_request( string $target_uuid, string $target_name, string $requester_name, string $message ): void {
        $users = get_users([
            'meta_key'   => 'w4os_uuid',
            'meta_value' => $target_uuid,
            'number'     => 1,
        ]);

        if ( empty( $users ) ) return;

        $target_email = $users[0]->user_email;
        $site_name    = get_bloginfo( 'name' );
        $partner_url  = home_url( '/' );

        $subject = "[{$site_name}] Partner Request from {$requester_name}";
        $body    = "Hello {$target_name},\n\n";
        $body   .= "{$requester_name} has sent you a SoulTether partner request on {$site_name}.\n\n";
        if ( $message ) {
            $body .= "Message: {$message}\n\n";
        }
        $body .= "Log in to approve or decline:\n{$partner_url}\n\n";
        $body .= "– The {$site_name} Team";

        wp_mail( $target_email, $subject, $body );
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function success( string $message ): array {
        return [ 'success' => true, 'message' => $message ];
    }

    private function error( string $message ): array {
        return [ 'success' => false, 'message' => $message ];
    }
}
