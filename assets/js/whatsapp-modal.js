jQuery(document).ready(function($) {
    var modal = $('#whatsapp-order-modal');

    // Show the modal for single product buttons.
    $(document).on('click', '.whatsapp-order-button', function(e) {
        e.preventDefault();

        var productId = $(this).data('product-id');
        $('#product_id').val(productId);
        $('#is_cart_order').val('0');

        var form = $(this).closest('.product').find('form.cart').first();
        var qty = form.find('input.qty').val() || '1';
        var vid = form.find('input.variation_id').val() || '';
        var attrs = {};

        form.find('[name^="attribute_"]').each(function() {
            var n = $(this).attr('name');
            var v = $(this).val();
            if (n) {
                attrs[n] = v;
            }
        });

        $('#quantity').val(qty);
        $('#variation_id').val(vid);
        $('#attributes_json').val(JSON.stringify(attrs));

        modal.css('display', 'flex');
        return false;
    });

    // Show the modal for cart page button.
    $(document).on('click', '.whatsapp-order-cart-button', function(e) {
        e.preventDefault();
        $('#is_cart_order').val('1');
        $('#product_id').val('');
        modal.css('display', 'flex');
        return false;
    });

    // Close the modal.
    $('.whatsapp-close-button').on('click', function() {
        modal.hide();
    });

    // Close the modal if the user clicks outside.
    $(window).on('click', function(event) {
        if ($(event.target).is(modal)) {
            modal.hide();
        }
    });

    // Handle form submission via AJAX.
    $('#whatsapp-order-form').on('submit', function(e) {
        e.preventDefault();

        if (!$('#gdpr_consent').is(':checked')) {
            alert('You must accept the privacy policy.');
            return;
        }

        var formData = {
            action: 'wcwa_process_order',
            nonce: $('#wcwa_order_nonce_field').val(),
            name: $('#customer_name').val(),
            phone: $('#customer_phone').val(),
            address: $('#customer_address').val(),
            notes: $('#customer_notes').val(),
            product_id: $('#product_id').val(),
            is_cart_order: $('#is_cart_order').val(),
            consent: $('#gdpr_consent').is(':checked') ? '1' : '0',
            quantity: $('#quantity').val(),
            variation_id: $('#variation_id').val(),
            attributes_json: $('#attributes_json').val()
        };

        // Open a blank tab during direct user interaction to avoid popup blocking.
        var whatsappWindow = window.open('', '_blank', 'noopener');

        $('#send-whatsapp-order').prop('disabled', true).text('Processing...');

        $.post(wcwa_ajax.ajax_url, formData, function(response) {
            if (response && response.success && response.data && response.data.whatsapp_url) {
                var targetUrl = response.data.whatsapp_api_url ? response.data.whatsapp_api_url : response.data.whatsapp_url;

                if (whatsappWindow) {
                    whatsappWindow.location = targetUrl;
                } else {
                    window.open(targetUrl, '_blank', 'noopener');
                }
            } else {
                if (whatsappWindow) {
                    whatsappWindow.close();
                }
                var msg = (response && response.data && response.data.message) ? response.data.message : 'There was an error processing your order.';
                alert(msg);
                $('#send-whatsapp-order').prop('disabled', false).text('Send Order');
            }
        }).fail(function() {
            if (whatsappWindow) {
                whatsappWindow.close();
            }
            alert('Server error. Please try again.');
            $('#send-whatsapp-order').prop('disabled', false).text('Send Order');
        });
    });
});
