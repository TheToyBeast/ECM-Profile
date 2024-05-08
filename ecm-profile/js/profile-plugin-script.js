jQuery(document).ready(function($) {

    // Handling flair icon selection
    $('#flair-selection-form').submit(function(e) {
        e.preventDefault();
        var selectedFlair = $('input[name="flair-icon"]:checked').val();

        $.ajax({
            url: cpp_vars.ajax_url,
            method: 'POST',
            data: {
                action: 'set_icon_as_flair',
                icon_url: selectedFlair,
                nonce: cpp_vars.set_icon_as_flair_nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Flair set successfully.');
					location.reload();  // Refreshes the page
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('Error occurred. Please try again.');
            }
        });
    });

    // Handling ticket purchasing
    $(".purchase-ticket").on("click", function() {
        const ticketCost = parseInt($(this).data('cost'));

        $.ajax({
            url: cpp_vars.ajax_url,
            method: 'POST',
            data: {
                action: 'purchase_ticket',
                cost: ticketCost,
                nonce: cpp_vars.purchase_ticket_nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update UI to reflect the ticket purchase.
                    const ticketSection = $("#contest-ticket-section"); 
                    let currentTickets = parseInt(ticketSection.find('p .ticket-count').text());  // Modified selector
                    currentTickets += 1;
                    ticketSection.find('p .ticket-count').text(currentTickets);  // Modified selector

                    // Update user's points in real-time
                    const currentPointsDisplay = $("#user-points-display");
                    let currentPoints = parseInt(currentPointsDisplay.text().match(/\d+/)[0]);
                    currentPoints -= ticketCost;
                    currentPointsDisplay.text('You have ' + currentPoints + ' points.');
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('Error occurred. Please try again.');
            }
        });
    });
	    // Tab functionality
    $('.tabs .tab-link').click(function() {
        var tab_id = $(this).attr('data-tab');

        $('.tabs .tab-link').removeClass('current');
        $('.tab-content').removeClass('current');

        $(this).addClass('current');
        $("#" + tab_id).addClass('current');
    });
});
