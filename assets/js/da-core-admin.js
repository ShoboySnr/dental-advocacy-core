(function ($) {
    "use strict";

    let dental_advocacy_core_admin = {}

    dental_advocacy_core_admin.hide_loading_style = function (show = false) {
        if(show) {
            $('.da-core-loading-overlay').show();
            $('.da-core-loader-image').show();
        } else {
            $('.da-core-loading-overlay').hide();
            $('.da-core-loader-image').hide();
        }
    }

    dental_advocacy_core_admin.submitProductsToCartForm = function(event) {
        event.preventDefault();

        var formData = $(this).serialize();
        dental_advocacy_core_admin.hide_loading_style(true);

        formData = formData+ '&da-core-prepare-products-to-cart-submit=true';

        jQuery.ajax({
            url: atcaa_admin_ajax_object.ajax_url,
            type: 'POST',
            data: formData
        })
        .done(function(response) {
            jQuery(" #da-core-form-feedback ").html(response);
            dental_advocacy_core_admin.hide_loading_style();
        })
        .fail(function(error) {
            ental_advocacy_core_admin.hide_loading_style();
            jQuery(" #da-core-form-feedback ").html( "<div class='da-core-message da-core-message-error'>Something went wrong.</div>" );
            console.log(error);
        })

    }

    $(document).on('ready', () => {
        if($('#dental-advocacy-products-to-cart').length > 0) {
            dental_advocacy_core_admin.hide_loading_style();
            $('.da-core-customer-details-select2').select2({
                width: '100%',
                placeholder: 'Search for customers via names or email',
                minimumInputLength: 3,
                ajax: {
                    url: da_core_admin_ajax_object.ajax_url,
                    dataType: 'json',
                    type: 'POST',
                    delay: 250,
                    data: function (params) {
                        return {
                            user_name: params.term,
                            action: 'da_core_get_customer_names'
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                }
            });
            $('.da-core-product-ids-select2').select2({
                width: '100%',
                placeholder: 'Search for products via id or title',
                minimumInputLength: 3,
                ajax: {
                    url: da_core_admin_ajax_object.ajax_url,
                    dataType: 'json',
                    type: 'POST',
                    delay: 250,
                    data: function (params) {
                        return {
                            product_id: params.term,
                            action: 'da_core_get_products'
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                }
            });
            $(document).on('submit', '#dental-advocacy-products-add-to-cart-form', dental_advocacy_core_admin.submitProductsToCartForm);
        }
    });
})(jQuery);