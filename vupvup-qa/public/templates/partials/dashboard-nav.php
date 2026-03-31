<?php defined( 'ABSPATH' ) || exit; ?>
<nav class="vupvup-dash-nav">
    <div class="vupvup-dash-nav-inner">
        <a href="<?php echo esc_url( home_url( 'dashboard/' ) ); ?>" class="vupvup-nav-logo">
            VupVup
        </a>
        <div class="vupvup-nav-links">
            <a href="<?php echo esc_url( home_url( 'dashboard/' ) ); ?>"
               class="vupvup-nav-link <?php echo strpos( $_SERVER['REQUEST_URI'] ?? '', '/event/' ) === false ? 'active' : ''; ?>">
                <?php esc_html_e( 'Events', 'vupvup-qa' ); ?>
            </a>
        </div>
        <div class="vupvup-nav-user">
            <span class="vupvup-nav-name">
                <?php echo esc_html( wp_get_current_user()->display_name ); ?>
            </span>
            <a href="<?php echo esc_url( wp_logout_url( home_url( 'vupvup/login/' ) ) ); ?>"
               class="vupvup-btn vupvup-btn-ghost vupvup-btn-sm">
                <?php esc_html_e( 'Log ud', 'vupvup-qa' ); ?>
            </a>
        </div>
    </div>
</nav>
