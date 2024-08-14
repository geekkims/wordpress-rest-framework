(function($) {
    'use strict';

    $(document).ready(function() {
        // API Key Generation
        $('#generate_api_key').on('click', function() {
            $.ajax({
                url: techspaceRF.ajax_url,
                type: 'POST',
                data: {
                    action: 'techspace_generate_api_key',
                    nonce: techspaceRF.nonce
                },
                beforeSend: function() {
                    $('#generate_api_key').prop('disabled', true).text('Generating...');
                },
                success: function(response) {
                    if (response.success) {
                        $('#techspace_api_key').val(response.data.api_key);
                        showToast('New API key generated successfully. Please save this key as it won\'t be shown again.', 'success');
                    } else {
                        showToast('Error generating API key: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    showToast('An error occurred while generating the API key.', 'error');
                },
                complete: function() {
                    $('#generate_api_key').prop('disabled', false).text('Generate New API Key');
                }
            });
        });

        // Copy API Key to Clipboard
        $('#copy_api_key').on('click', function() {
            var apiKeyInput = document.getElementById('techspace_api_key');
            apiKeyInput.select();
            document.execCommand('copy');
            showToast('API key copied to clipboard', 'success');
        });

        // Function to show toast notifications
        function showToast(message, type) {
            var toast = $('<div class="techspace-toast ' + type + '">' + message + '</div>');
            $('body').append(toast);
            toast.animate({ top: '16px', opacity: 1 }, 300);
            setTimeout(function() {
                toast.animate({ top: '-100px', opacity: 0 }, 300, function() {
                    toast.remove();
                });
            }, 3000);
        }

        // Initialize tooltips
        $('.techspace-tooltip').tooltip();

        // Toggle sections in the dashboard
        $('.techspace-toggle-section').on('click', function() {
            var targetId = $(this).data('target');
            $('#' + targetId).slideToggle();
            $(this).find('i').toggleClass('dashicons-arrow-down dashicons-arrow-up');
        });

        // Datepicker for custom date range in analytics
        if ($.fn.datepicker) {
            $('.techspace-datepicker').datepicker({
                dateFormat: 'yy-mm-dd',
                maxDate: '0'
            });
        }

        // AJAX load more for pagination
        $('#load_more_data').on('click', function() {
            var button = $(this);
            var page = button.data('page');
            $.ajax({
                url: techspaceRF.ajax_url,
                type: 'POST',
                data: {
                    action: 'techspace_load_more_data',
                    nonce: techspaceRF.nonce,
                    page: page
                },
                beforeSend: function() {
                    button.prop('disabled', true).text('Loading...');
                },
                success: function(response) {
                    if (response.success) {
                        $('#techspace_data_table tbody').append(response.data.html);
                        button.data('page', page + 1);
                        if (!response.data.has_more) {
                            button.remove();
                        }
                    } else {
                        showToast('Error loading more data: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    showToast('An error occurred while loading more data.', 'error');
                },
                complete: function() {
                    button.prop('disabled', false).text('Load More');
                }
            });
        });
    });

})(jQuery);