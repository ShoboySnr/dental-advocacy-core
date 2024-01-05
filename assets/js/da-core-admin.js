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

    dental_advocacy_core_admin.hide_overview_loading_style = function(show = false) {
        if(show) {
            $('.da-core-overview-loading-overlay').show();
            $('.da-core-overview-loader-image').show();
        } else {
            $('.da-core-overview-loading-overlay').hide();
            $('.da-core-overview-loader-image').hide();
        }
    }

    dental_advocacy_core_admin.submitProductsToCartForm = function(event) {
        event.preventDefault();

        const formData = $(this).serialize();
        dental_advocacy_core_admin.hide_loading_style(true);

        formData = formData+ '&da-core-prepare-products-to-cart-submit=true';
        $('#da-core-form-feedback').html('');

        $.ajax({
            url: da_core_admin_ajax_object.ajax_url,
            type: 'POST',
            data: formData
        })
        .done(function(response) {
            $(" #da-core-form-feedback ").html(response.message);
            dental_advocacy_core_admin.hide_loading_style();
        })
        .fail(function(error) {
            dental_advocacy_core_admin.hide_loading_style();
            // $("#da-core-form-feedback ").html( "<div class='da-core-message da-core-message-error'>Something went wrong.</div>" );
            console.log(error);
        })
    }

    dental_advocacy_core_admin.submitModifyMetaDetailsForm = function(event) {
        event.preventDefault();

        const formData = $(this).serialize();
        dental_advocacy_core_admin.hide_overview_loading_style(true);
        const _this = $(this);

        $.ajax({
            url: da_core_admin_ajax_object.ajax_url,
            type: 'POST',
            data: formData
        })
            .done(function(response) {
                if(response.success == true) {
                    _this.parents('td').html(response.message);
                } else {
                    alert(response.message);
                    window.location.reload();
                }
                dental_advocacy_core_admin.hide_overview_loading_style();
            })
            .fail(function(error) {
                dental_advocacy_core_admin.hide_overview_loading_style();
                // $("#da-core-form-feedback ").html( "<div class='da-core-message da-core-message-error'>Something went wrong.</div>" );
                console.log(error);
            })
    }

    dental_advocacy_core_admin.cancelModifyMetaDetailsForm = function(event) {
        event.preventDefault();

        const product_id = $(this).data('product-id');
        const user_id = $(this).data('user-id');
        dental_advocacy_core_admin.hide_overview_loading_style(true);
        const _this = $(this);

        $.ajax({
            url: da_core_admin_ajax_object.ajax_url,
            type: 'POST',
            data: {
                user_id,
                product_id,
                action: 'da_core_cancel_meta_details'
            }
        })
            .done(function(response) {
                if(response.success == true) {
                    _this.parents('td').html(response.message);
                } else {
                    alert(response.message);
                    window.location.reload();
                }
                dental_advocacy_core_admin.hide_overview_loading_style();
            })
            .fail(function(error) {
                dental_advocacy_core_admin.hide_overview_loading_style();
                // $("#da-core-form-feedback ").html( "<div class='da-core-message da-core-message-error'>Something went wrong.</div>" );
                console.log(error);
            })
    }

    dental_advocacy_core_admin.getMetaDataDetailsForm = function(event) {
        event.preventDefault();

        const product_id = $(this).data('product-id');
        const user_id = $(this).data('user-id');
        const nonce = $(this).data('modify-meta-data-nonce');

        const _this = $(this);

        dental_advocacy_core_admin.hide_overview_loading_style(true);

        $.ajax({
            url: da_core_admin_ajax_object.ajax_url,
            type: 'POST',
            data: {
                'product_id': product_id,
                'user_id': user_id,
                'modify_meta_details_nonce': nonce,
                'action': 'da_core_get_meta_details_form'
            }
        })
            .done(function(response) {
                _this.parents('td').html(response.message);
                dental_advocacy_core_admin.hide_overview_loading_style();
            })
            .fail(function(error) {
                dental_advocacy_core_admin.hide_overview_loading_style();
                // $("#da-core-form-feedback ").html( "<div class='da-core-message da-core-message-error'>Something went wrong.</div>" );
                console.log(error);
            })
    }

    dental_advocacy_core_admin.deleteSpecificMetaDetail = function(event) {
        event.preventDefault();
        if(!confirm('Are you sure you want to remove this record'))  return;

        const product_id = $(this).parents('tr').data('product-id');
        const user_id = $(this).parents('tr').data('user-id');
        const nonce = $(this).data('nonce');

        dental_advocacy_core_admin.hide_overview_loading_style(true);

        $.ajax({
            url: da_core_admin_ajax_object.ajax_url,
            type: 'POST',
            data: {
                'product_id': product_id,
                'user_id': user_id,
                'product_carts_entry_delete_nonce': nonce,
                'action': 'da_core_delete_product_carts_entry'
            }
        })
            .done(function(response) {
                alert(response.message);
                window.location.reload();
                dental_advocacy_core_admin.hide_overview_loading_style();
            })
            .fail(function(error) {
                dental_advocacy_core_admin.hide_overview_loading_style();
                // $("#da-core-form-feedback ").html( "<div class='da-core-message da-core-message-error'>Something went wrong.</div>" );
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
            $(document).on('submit', '#dental-advocacy-modify-meta-details-form', dental_advocacy_core_admin.submitModifyMetaDetailsForm);
            $(document).on('click', 'button.da-core-cancel-meta-details-submit', dental_advocacy_core_admin.cancelModifyMetaDetailsForm);
            $(document).on('click', 'button.da-core-metadata-update-button', dental_advocacy_core_admin.getMetaDataDetailsForm);
            $(document).on('click', 'button.da-core-entry-delete-button', dental_advocacy_core_admin.deleteSpecificMetaDetail);
        }
    });
})(jQuery);