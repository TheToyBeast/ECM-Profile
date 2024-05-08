jQuery(document).ready(function($) {

    // Open WordPress Media Uploader
    $('#add-flair-icon').on('click', function(e) {
        e.preventDefault();

        var frame = wp.media({
            title: 'Select or Upload Flair Icon',
            button: {
                text: 'Use this icon'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            var iconUrl = attachment.url;

            var newItem = '<div class="flair-icon-item">';
            newItem += '<img src="' + iconUrl + '" width="50" height="50">';
            newItem += '<input type="hidden" name="upvote_flair_icons[]" value="' + iconUrl + '">';
            newItem += '<label>Title: <input type="text" name="upvote_flair_titles[]"></label>';
			newItem += '<label>Cost: <input type="text" name="upvote_flair_costs[]"></label>';
            newItem += '<button class="remove-flair-icon">Remove</button>';
            newItem += '</div>';

            $('#flair-icons-repeater').append(newItem);
        });

        frame.open();
    });

    // Remove Icon Functionality
    $(document).on('click', '.remove-flair-icon', function(e) {
        e.preventDefault();
        $(this).closest('.flair-icon-item').remove();
    });
});
