(function ($) {
  "use strict";

  // We create a copy of the WP inline edit post function
  var $wp_inline_edit = inlineEditPost.edit;

  // And then we override the function with our own
  inlineEditPost.edit = function (id) {
    // Call the original WP edit function
    $wp_inline_edit.apply(this, arguments);

    // Get the post ID
    var post_id = 0;
    if (typeof id === "object") {
      post_id = parseInt(this.getId(id));
    }

    if (post_id > 0) {
      // Get the row data
      var $row = $("#post-" + post_id);

      // Get the taxonomy name
      var taxonomy = RunthingsTBP.taxonomy;

      // Get the term cell from the row
      var $termCell = $row.find("td.taxonomy-" + taxonomy);

      if ($termCell.length) {
        var termText = "";
        var termSlug = "";

        // Check for links (standard view)
        if ($termCell.find("a").length) {
          var $link = $termCell.find("a").first();
          termText = $link.text().trim();

          // Try to extract the slug from the link
          var href = $link.attr("href");
          if (href) {
            var slugMatch = new RegExp(taxonomy + "=([^&]+)").exec(href);
            if (slugMatch && slugMatch[1]) {
              termSlug = decodeURIComponent(slugMatch[1]);
            }
          }
        } else {
          // If no links, get the plain text
          termText = $termCell.text().trim();
        }

        // Now find the matching option in our dropdown
        if (termText || termSlug) {
          var $options = $("#access_group_term_quick_edit option");
          var matched = false;

          $options.each(function () {
            var $option = $(this);

            // Match by slug first (most reliable)
            if (termSlug && $option.data("slug") === termSlug) {
              $("#access_group_term_quick_edit").val($option.val());
              matched = true;
              return false; // Break out of each loop
            }

            // Then try matching by name
            if (
              !matched &&
              termText &&
              ($option.data("name") === termText || $option.text() === termText)
            ) {
              $("#access_group_term_quick_edit").val($option.val());
              matched = true;
              return false; // Break out of each loop
            }
          });

          // If no match found, reset to empty
          if (!matched) {
            $("#access_group_term_quick_edit").val("");
          }
        } else {
          // No term information found
          $("#access_group_term_quick_edit").val("");
        }
      }
    }
  };
})(jQuery);
