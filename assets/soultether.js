/* ==========================================================================
   SoulTether — soultether.js
   OpenSim Partner System for WordPress
   soulTether.ajaxurl and soulTether.nonce are localized by PHP.
   ========================================================================== */

jQuery(document).ready(function ($) {
    'use strict';

    // ── Flash message ────────────────────────────────────────────────────────
    function showMessage(message, type) {
        var $msg = $('#st-message');
        $msg.removeClass('success error').addClass(type).html(message).show();
        $('html, body').animate({ scrollTop: $msg.offset().top - 80 }, 300);
        if (type === 'success') {
            setTimeout(function () { $msg.fadeOut(400); }, 8000);
        }
    }

    // ── Loading state ────────────────────────────────────────────────────────
    function setLoading($btn, loading) {
        if (loading) {
            $btn.data('original-text', $btn.html());
            $btn.html('<span class="st-spinner"></span> Please wait…').prop('disabled', true);
        } else {
            $btn.html($btn.data('original-text')).prop('disabled', false);
        }
    }

    // ── Send tether request ──────────────────────────────────────────────────
    $(document).on('click', '#st-send-btn', function () {
        var $btn       = $(this);
        var targetUuid = $('#st-friend-select').val();
        var message    = $('#st-message').val().trim();

        if (!targetUuid) {
            showMessage('Please select a friend to send a request to.', 'error');
            return;
        }

        setLoading($btn, true);

        $.post(soulTether.ajaxurl, {
            action:      'soultether_send_request',
            nonce:       soulTether.nonce,
            target_uuid: targetUuid,
            message:     message,
        }, function (response) {
            setLoading($btn, false);
            if (response.success) {
                showMessage(response.message, 'success');
                $('.st-send-card').slideUp(300);
            } else {
                showMessage(response.message, 'error');
            }
        }).fail(function () {
            setLoading($btn, false);
            showMessage('A network error occurred. Please try again.', 'error');
        });
    });

    // ── Respond (accept or decline) ──────────────────────────────────────────
    $(document).on('click', '.st-respond-btn', function () {
        var $btn       = $(this);
        var requestId  = $btn.data('request-id');
        var actionType = $btn.data('action-type');
        var $item      = $btn.closest('.st-request-item');

        setLoading($btn, true);

        $.post(soulTether.ajaxurl, {
            action:      'soultether_respond_request',
            nonce:       soulTether.nonce,
            request_id:  requestId,
            action_type: actionType,
        }, function (response) {
            setLoading($btn, false);
            if (response.success) {
                showMessage(response.message, 'success');
                $item.slideUp(300, function () {
                    $(this).remove();
                    if ($('.st-incoming-card .st-request-item').length === 0) {
                        $('.st-incoming-card').slideUp(300);
                    }
                });
                if (actionType === 'approved') {
                    setTimeout(function () { location.reload(); }, 1800);
                }
            } else {
                showMessage(response.message, 'error');
            }
        }).fail(function () {
            setLoading($btn, false);
            showMessage('A network error occurred. Please try again.', 'error');
        });
    });

    // ── Withdraw outgoing request ────────────────────────────────────────────
    $(document).on('click', '.st-withdraw-btn', function () {
        var $btn       = $(this);
        var requestId  = $btn.data('request-id');
        var targetName = $btn.data('target-name');
        var $item      = $btn.closest('.st-request-item');

        if (!window.confirm('Withdraw your tether request to ' + targetName + '?')) return;

        setLoading($btn, true);

        $.post(soulTether.ajaxurl, {
            action:     'soultether_withdraw',
            nonce:      soulTether.nonce,
            request_id: requestId,
        }, function (response) {
            setLoading($btn, false);
            if (response.success) {
                showMessage(response.message, 'success');
                $item.slideUp(300, function () {
                    $(this).remove();
                    if ($('.st-outgoing-card .st-request-item').length === 0) {
                        $('.st-outgoing-card').slideUp(300);
                        $('.st-send-card').slideDown(300);
                    }
                });
            } else {
                showMessage(response.message, 'error');
            }
        }).fail(function () {
            setLoading($btn, false);
            showMessage('A network error occurred. Please try again.', 'error');
        });
    });

    // ── Untether ─────────────────────────────────────────────────────────────
    $(document).on('click', '.st-untether-btn', function () {
        var $btn    = $(this);
        var confirm = $btn.data('confirm');

        if (!window.confirm(confirm)) return;

        setLoading($btn, true);

        $.post(soulTether.ajaxurl, {
            action: 'soultether_untether',
            nonce:  soulTether.nonce,
        }, function (response) {
            setLoading($btn, false);
            if (response.success) {
                showMessage(response.message, 'success');
                setTimeout(function () { location.reload(); }, 1800);
            } else {
                showMessage(response.message, 'error');
            }
        }).fail(function () {
            setLoading($btn, false);
            showMessage('A network error occurred. Please try again.', 'error');
        });
    });

});
