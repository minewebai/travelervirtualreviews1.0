jQuery(function($) {
    if (!$('#frm-traveler').length) return;

    // Replace location text input with dropdown
    if (typeof tvrLocations !== 'undefined' && tvrLocations.length) {
        var $location = $('<select id="location" name="location" style="margin-left: 8px; width: 200px;">');
        $locationSelect.append('<option value="">Select Location</option>');
        $.each(tvrLocations, function(i, location) {
            $locationSelect.append($('<option>').val(location.title).text(location.title));
        });

        // Find and replace the location text field
        var $locationInput = $('input[name="location"]');
        if ($locationInput.length) {
            $locationInput.replaceWith($locationSelect);
        } else {
            // Fallback: Append after the count input if no location field
            $('input[name="count"]').after($locationSelect);
        }
    } else {
        console.warn('TVR: No locations found or tvrLocations not defined. Ensure location posts exist.');
    }

    // Handle form submission
    $('#frm-traveler').on('submit', function(e) {
        e.preventDefault();
        var postId = $('select[name="post_id"]').val();
        var count = $('input[name="count"]').val();
        var lang = $('select[name="lang"]').val() || 'en';
        var location = $('#location').val();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'tvrf_generate',
                post_id: postId,
                count: count,
                lang: lang,
                location: location
            },
            success: function(response) {
                alert(response.data.message || 'Reviews generated!');
                location.reload();
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.error || 'Failed to generate reviews'));
            }
        });
    });

    // Handle bulk delete
    $('#tvr-bulk-delete').on('click', function() {
        var commentIds = $('input[name="tvr_comments[]"]:checked').map(function() {
            return $(this).val();
        }).get();

        if (!commentIds.length) {
            alert('No reviews selected.');
            return;
        }

        $.post(ajaxurl, {
            action: 'tvr_delete_reviews',
            comment_ids: commentIds
        }, function(response) {
            alert(response.message || 'Reviews deleted!');
            location.reload();
        }).fail(function() {
            alert('Error deleting reviews.');
        });
    });
});