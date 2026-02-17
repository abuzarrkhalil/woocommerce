jQuery(document).ready(function($) {
    var modal = $('#whatsapp-order-modal');

    // Show the modal for single product buttons
    $('.whatsapp-order-button').on('click', function(e) {
        e.preventDefault();
        var product_id = $(this).data('product-id');
        $('#product_id').val(product_id); // Store the product ID in a hidden field
        $('#is_cart_order').val('0'); // Mark as single product order

        var form = $(this).closest('.product').find('form.cart').first();
        var qty  = form.find('input.qty').val() || '1';
        var vid  = form.find('input.variation_id').val() || '';
        var attrs = {};
        form.find('[name^="attribute_"]').each(function(){
            var n = $(this).attr('name');
            var v = $(this).val();
            if (n) { attrs[n] = v; }
        });
        $('#quantity').val(qty);
        $('#variation_id').val(vid);
        $('#attributes_json').val(JSON.stringify(attrs));

        modal.css('display', 'flex'); 
    });

    // Show the modal for cart page button
     $('.whatsapp-order-cart-button').on('click', function(e) {
        e.preventDefault();
        $('#is_cart_order').val('1'); // Mark as cart order
        $('#product_id').val(''); // Clear single product ID
        modal.css('display', 'flex'); 
    });


    // Close the modal
    $('.whatsapp-close-button').on('click', function() {
        modal.hide();
    });

    // Close the modal if the user clicks outside
    $(window).on('click', function(event) {
        if ($(event.target).is(modal)) {
            modal.hide();
        }
    });

    // Handle form submission via AJAX
    $('#whatsapp-order-form').on('submit', function(e) {
        e.preventDefault();

        if (!$('#gdpr_consent').is(':checked')) {
            alert('You must accept the privacy policy.');
            return;
        }

        var formData = {
            'action': 'wcwa_process_order',
            'nonce': $('#wcwa_order_nonce_field').val(),
            'name': $('#customer_name').val(),
            'phone': $('#customer_phone').val(),
            'address': $('#customer_address').val(),
            'notes': $('#customer_notes').val(),
            'product_id': $('#product_id').val(),
            'is_cart_order': $('#is_cart_order').val(),
            'consent': $('#gdpr_consent').is(':checked') ? '1' : '0',
            'quantity': $('#quantity').val(),
            'variation_id': $('#variation_id').val(),
            'attributes_json': $('#attributes_json').val(),
        };

        $('#send-whatsapp-order').prop('disabled', true).text('Processing...');

        $.post(wcwa_ajax.ajax_url, formData, function(response) {
            if (response && response.success && response.data && response.data.whatsapp_url) {
                // Use api.whatsapp.com for all devices because it reliably
                // keeps the chat payload and then routes to app/web.
                var targetUrl = response.data.whatsapp_api_url ? response.data.whatsapp_api_url : response.data.whatsapp_url;
                var newTab = window.open(targetUrl, '_blank', 'noopener,noreferrer');

                // If popup is blocked, gracefully fall back to same-tab redirect.
                if (!newTab) {
                    window.location.href = targetUrl;
                }

                $('#send-whatsapp-order').prop('disabled', false).text('Send Order');
            } else {
                var msg = (response && response.data && response.data.message) ? response.data.message : 'There was an error processing your order.';
                alert(msg);
                $('#send-whatsapp-order').prop('disabled', false).text('Send Order');
            }
        }).fail(function() {
            alert('Server error. Please try again.');
            $('#send-whatsapp-order').prop('disabled', false).text('Send Order');
        });
    });
});
