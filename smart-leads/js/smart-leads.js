jQuery(document).ready(function ($) {

    $('#smart-leads-form').on('submit', function (e) {

        e.preventDefault();

        var form = $(this);

        var submitButton = form.find('button[type="submit"]');

        var messageBox = $('#smart-leads-message');

        submitButton.prop('disabled', true);

        submitButton.text('Submitting...');

        messageBox.html('');

        $.ajax({

            url: smartLeads.ajax_url,

            type: 'POST',

            data: {
                action: 'smart_leads_ajax_submit',

                nonce: smartLeads.nonce,

                name: form.find('[name="name"]').val(),

                email: form.find('[name="email"]').val(),

                phone: form.find('[name="phone"]').val(),

                company: form.find('[name="company"]').val(),

                requirement: form.find('[name="requirement"]').val()
            },

            success: function (response) {

                if (response.success) {

                    messageBox.html(
                        '<div class="smart-leads-success">' +
                        '<p>' + response.data.message + '</p>' +
                        '</div>'
                    );

                    form[0].reset();

                } else {

                    messageBox.html(
                        '<div class="smart-leads-error">' +
                        '<p>' + response.data.message + '</p>' +
                        '</div>'
                    );
                }
            },

            error: function () {

                messageBox.html(
                    '<div class="smart-leads-error">' +
                    '<p>Something went wrong. Please try again.</p>' +
                    '</div>'
                );
            },

            complete: function () {

                submitButton.prop('disabled', false);

                submitButton.text('Submit Lead');
            }

        });

    });

});