<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Access denied.' );

$pdb    = new SoulTether_Partner_DB();
$filter = sanitize_text_field( $_GET['status'] ?? '' );
$paged  = max( 1, intval( $_GET['paged'] ?? 1 ) );
$limit  = 25;
$offset = ( $paged - 1 ) * $limit;

$requests = $pdb->get_all( $filter, $limit, $offset );
$total    = $pdb->count( $filter );
$pages    = ceil( $total / $limit );

$statuses = [ '' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'denied' => 'Denied', 'dissolved' => 'Dissolved' ];
?>
<div class="wrap">
    <h1>💕 SoulTether — Partner Requests</h1>

    <div class="st-admin-stats">
        <?php foreach ( [ 'pending', 'approved', 'denied', 'dissolved' ] as $s ) : ?>
        <div class="st-admin-stat-box">
            <span class="st-admin-stat-count"><?php echo $pdb->count( $s ); ?></span>
            <span class="st-admin-stat-label"><?php echo ucfirst( $s ); ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="st-admin-filters">
        <?php foreach ( $statuses as $val => $label ) : ?>
        <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'soultether-admin', 'status' => $val ], admin_url( 'admin.php' ) ) ); ?>"
           class="button <?php echo $filter === $val ? 'button-primary' : ''; ?>">
            <?php echo esc_html( $label ); ?>
            <span class="count">(<?php echo $val ? $pdb->count( $val ) : $pdb->count(); ?>)</span>
        </a>
        <?php endforeach; ?>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th><th>Requester</th><th>Target</th><th>Message</th><th>Status</th><th>Requested</th><th>Updated</th>
            </tr>
        </thead>
        <tbody>
        <?php if ( empty( $requests ) ) : ?>
            <tr><td colspan="7">No records found.</td></tr>
        <?php else : ?>
            <?php foreach ( $requests as $r ) : ?>
            <tr>
                <td><?php echo (int) $r['id']; ?></td>
                <td><strong><?php st_e( $r['requester_name'] ); ?></strong><br>
                    <small style="font-family:monospace;color:#999;"><?php st_e( $r['requester_uuid'] ); ?></small></td>
                <td><strong><?php st_e( $r['target_name'] ); ?></strong><br>
                    <small style="font-family:monospace;color:#999;"><?php st_e( $r['target_uuid'] ); ?></small></td>
                <td><?php st_e( $r['request_message'] ?: '—' ); ?></td>
                <td><span class="st-status-badge st-status-<?php echo esc_attr( $r['status'] ); ?>"><?php st_e( ucfirst( $r['status'] ) ); ?></span></td>
                <td><?php echo esc_html( soultether_time_ago( $r['created_at'] ) ); ?></td>
                <td><?php echo esc_html( soultether_time_ago( $r['updated_at'] ) ); ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <?php if ( $pages > 1 ) : ?>
    <div class="tablenav bottom"><div class="tablenav-pages">
        <?php echo paginate_links([ 'base' => add_query_arg( 'paged', '%#%' ), 'format' => '', 'current' => $paged, 'total' => $pages ]); ?>
    </div></div>
    <?php endif; ?>

    <div style="margin-top:24px;background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 24px;">
        <h3>About SoulTether</h3>
        <p>SoulTether manages avatar partnerships for OpenSimulator grids. When a tether is approved, it writes to <code>userprofile.profilePartner</code> in the Robust database for both avatars.</p>
        <p><strong>Shortcode:</strong> <code>[soultether]</code></p>
    </div>
</div>

<style>
.st-admin-stats{display:flex;gap:16px;margin:16px 0;}
.st-admin-stat-box{background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 24px;text-align:center;}
.st-admin-stat-count{display:block;font-size:2em;font-weight:700;}
.st-admin-stat-label{font-size:0.85em;color:#666;text-transform:uppercase;}
.st-admin-filters{margin:12px 0 16px;display:flex;gap:8px;flex-wrap:wrap;}
.st-status-badge{padding:2px 8px;border-radius:12px;font-size:0.85em;font-weight:600;}
.st-status-pending{background:#fff3cd;color:#856404;}
.st-status-approved{background:#d1e7dd;color:#0a3622;}
.st-status-denied{background:#f8d7da;color:#58151c;}
.st-status-dissolved{background:#e2e3e5;color:#41464b;}
</style>
