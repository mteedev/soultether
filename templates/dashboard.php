<?php
/**
 * SoulTether — Dashboard template
 * Rendered by [soultether] shortcode.
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="st-wrap">

    <div id="st-message" class="st-message" style="display:none;"></div>

    <?php /* ── TETHER STATUS ── */ ?>
    <div class="st-card st-status-card">
        <h2 class="st-card-title">
            <span class="st-icon">💑</span> Your Soul Tether
        </h2>

        <?php if ( $partner_uuid && $partner_name ) : ?>
            <div class="st-tethered">
                <div class="st-heart">❤️</div>
                <p class="st-partner-name">
                    Your soul is tethered to <strong><?php st_e( $partner_name ); ?></strong>
                </p>
                <p class="st-note">Your partnership is visible on your in-world profile.</p>
                <button
                    class="st-btn st-btn-danger st-untether-btn"
                    data-confirm="Are you sure you want to release your soul tether with <?php echo esc_attr( $partner_name ); ?>? This cannot be undone."
                >
                    💔 Untether
                </button>
            </div>

        <?php else : ?>
            <div class="st-untethered">
                <p>Your soul is not currently tethered to anyone.</p>
                <?php if ( empty( $outgoing ) ) : ?>
                    <p class="st-hint">Select a friend below to send a tether request.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php /* ── INCOMING REQUESTS ── */ ?>
    <?php if ( ! empty( $incoming ) ) : ?>
    <div class="st-card st-incoming-card">
        <h2 class="st-card-title">
            <span class="st-icon">📬</span>
            Incoming Tether Request<?php echo count( $incoming ) > 1 ? 's' : ''; ?>
            <span class="st-badge"><?php echo count( $incoming ); ?></span>
        </h2>

        <?php foreach ( $incoming as $req ) : ?>
        <div class="st-request-item" data-request-id="<?php echo (int) $req['id']; ?>">
            <div class="st-request-info">
                <strong><?php st_e( $req['requester_name'] ); ?></strong>
                <span class="st-time"> · <?php echo soultether_time_ago( $req['created_at'] ); ?></span>
                <?php if ( $req['request_message'] ) : ?>
                    <p class="st-request-message">"<?php st_e( $req['request_message'] ); ?>"</p>
                <?php endif; ?>
            </div>
            <div class="st-request-actions">
                <button class="st-btn st-btn-success st-respond-btn"
                    data-request-id="<?php echo (int) $req['id']; ?>"
                    data-action-type="approved">
                    ✅ Accept
                </button>
                <button class="st-btn st-btn-secondary st-respond-btn"
                    data-request-id="<?php echo (int) $req['id']; ?>"
                    data-action-type="denied">
                    ❌ Decline
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php /* ── OUTGOING PENDING REQUESTS ── */ ?>
    <?php if ( ! empty( $outgoing ) ) : ?>
    <div class="st-card st-outgoing-card">
        <h2 class="st-card-title">
            <span class="st-icon">📤</span> Sent Requests Awaiting Response
        </h2>
        <?php foreach ( $outgoing as $req ) : ?>
        <div class="st-request-item" data-request-id="<?php echo (int) $req['id']; ?>">
            <div class="st-request-info">
                <strong><?php st_e( $req['target_name'] ); ?></strong>
                <span class="st-badge st-badge-pending">Pending</span>
                <span class="st-time"> · Sent <?php echo soultether_time_ago( $req['created_at'] ); ?></span>
                <?php if ( $req['request_message'] ) : ?>
                    <p class="st-request-message">"<?php st_e( $req['request_message'] ); ?>"</p>
                <?php endif; ?>
            </div>
            <div class="st-request-actions">
                <button class="st-btn st-btn-secondary st-withdraw-btn"
                    data-request-id="<?php echo (int) $req['id']; ?>"
                    data-target-name="<?php echo esc_attr( $req['target_name'] ); ?>">
                    ↩️ Withdraw
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php /* ── SEND REQUEST ── */ ?>
    <?php if ( ! $partner_uuid && empty( $outgoing ) ) : ?>
    <div class="st-card st-send-card">
        <h2 class="st-card-title">
            <span class="st-icon">💌</span> Send a Tether Request
        </h2>

        <?php if ( empty( $friends ) ) : ?>
            <p>You don't have any friends on the grid yet. Add friends in-world first, then return here to send a tether request.</p>
        <?php else : ?>
        <div class="st-send-form">
            <div class="st-field">
                <label for="st-friend-select">Select a friend:</label>
                <select id="st-friend-select" class="st-select">
                    <option value="">— Choose a friend —</option>
                    <?php foreach ( $friends as $friend ) : ?>
                    <option value="<?php echo esc_attr( $friend['uuid'] ); ?>">
                        <?php st_e( $friend['name'] ); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="st-field">
                <label for="st-message">Add a message <span class="st-optional">(optional)</span>:</label>
                <textarea id="st-message" class="st-textarea" maxlength="255"
                    placeholder="Say something from the soul..." rows="3"></textarea>
            </div>
            <button id="st-send-btn" class="st-btn st-btn-primary">
                💌 Send Tether Request
            </button>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>
