<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="st-wrap">
    <div class="st-card st-auth-card">
        <span class="st-icon-lg">🔒</span>
        <h2>Please Log In</h2>
        <p>You must be logged in to manage your soul tether.</p>
        <a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="st-btn st-btn-primary">Log In</a>
    </div>
</div>
