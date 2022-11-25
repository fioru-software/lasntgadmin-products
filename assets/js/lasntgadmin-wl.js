jQuery(document).ready(function ($) {
    let confirmed = 0;
    $('.lasntgadmin-wl-btn').on('click', () => {
        const _this = $('.lasntgadmin-wl-btn');
        const email_field = $('#lasntgadmin-guest-email');
        const info_div = $('.lasntgadmin-wl-info');
        const product_id = _this.attr('data-id');
        
        if(email_field.length && email_field.val() == ''){
            info_div.html(`<div class="woocommerce-error">Email is required.</div>`);
            return;
        }
        $.ajax({
            url: lasntgadmin_ws_localize.adminurl,
            method: 'POST',
            security: lasntgadmin_ws_localize.wl_nonce,
            data: {
                action: 'lasntgadmin_wl',
                product_id: product_id,
                security: lasntgadmin_ws_localize.wl_nonce,
                confirmed: confirmed,
                email: email_field.length ? email_field.val() : ''
            },
            beforeSend: function () {
                console.log('before_send')
                _this.prop('disabled', true);
            },
            success: function (resp) {
                
                _this.prop('disabled', false);
                if (resp.status == 1) {
                    info_div.html(`<div class="woocommerce-info">${resp.msg}</div>`);

                    _this.html('Remove Waiting list');
                    return;
                }
                //removed from waiting list.
                else if(resp.status == 2) {
                    info_div.html(`<div class="woocommerce-info">${resp.msg}.</div>`);
                    
                    _this.html('Join Waiting list');
                    return;
                }
                // if guest and already added.
                else if(resp.status == -3) {
                    info_div.html(`<div class="woocommerce-info">${resp.msg}.</div>`);
                    
                    _this.html('Remove Waiting list');
                    confirmed = 1;
                    return;
                }
                info_div.html(`<div class="woocommerce-error">${resp.msg}</div>`);
                

            },
            error: function (error) {
                
                info_div.html('<div class="woocommerce-error">An error occurred. Please try again.</div>')
                _this.prop('disabled', false);
            }

        });
    });

});