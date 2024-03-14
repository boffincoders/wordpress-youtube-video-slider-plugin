<?php
/*
    Plugin Name: YouTube Video Slider
    Plugin URI: https://boffincoders.com/wp-plugins/youtube-video-slider
    Description: YouTube Video Slider
    Version: 4.1.0
    Author: Boffincoders
    Author URI: https://boffincoders.com/
    Text Domain: youtube-video-slider
*/

if (!defined("ABSPATH")) {
    exit();
}

// Create custom table on plugin activation
 function bc_yts_create_custom_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "bc_yts_youtube_slider";
    $table_name_settings = $wpdb->prefix . "bc_yts_youtube_slider_settings";

    // Create table $table_name if not exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
            id INT AUTO_INCREMENT PRIMARY KEY,
            video_title VARCHAR(255) NOT NULL,
            thumbnail_url VARCHAR(255) NOT NULL,
            video_preview VARCHAR(255) NOT NULL,
            custom_image VARCHAR(255) DEFAULT NULL
        )";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql); // Use dbDelta to create tables
    }

    // Create table $table_name_settings if not exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name_settings'") != $table_name_settings) {
        $sql = "CREATE TABLE $table_name_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            speed INT NOT NULL,
            images_margin INT NOT NULL,
            arrow INT NOT NULL,
            arrowwidth INT NOT NULL,
            images_mobile INT,
            images_tablet INT,
            images_desktop INT,
            play_icon INT
        )";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql); // Use dbDelta to create tables

        // Insert default values into $table_name_settings
        $wpdb->insert($table_name_settings, [
            "speed" => 4000,
            "images_margin" => 30,
            "arrow" => 2,
            "arrowwidth" => 30,
            "images_mobile" => 1,
            "images_tablet" => 2,
            "images_desktop" => 4,
            "play_icon" => 4,
        ]);
    }
}

register_activation_hook(__FILE__, "bc_yts_create_custom_table");

// Add menu item for the plugin page
function bc_yts_add_youtube_slider_menu()
{
    // Add top-level menu page
    add_menu_page(
        "YouTube Video Slider",
        "YouTube Video Slider",
        "manage_options",
        "youtube-video-slider",
        "bc_yts_youtube_slider_page",
        "dashicons-slides" // Change this if you want a different icon
    );

    // Add a submenu page
    add_submenu_page(
        "youtube-video-slider", // parent slug
        "Settings", // page title
        "Settings", // menu title
        "manage_options", // capability
        "youtube-video-slider-settings", // menu slug (consistent with parent)
        "bc_yts_youtube_slider_settings_page" // callback function
    );
}

add_action("admin_menu", "bc_yts_add_youtube_slider_menu");

function bc_yts_enqueue_media_uploader()
{
    wp_enqueue_media();
}
add_action("admin_enqueue_scripts", "bc_yts_enqueue_media_uploader");

 // Display the plugin page content
function bc_yts_youtube_slider_page()
{
    wp_enqueue_style(
        "style_file",
        plugin_dir_url(__FILE__) . "style/style.css"
    ); ?>
 <div class="wrap">
      <h2>YouTube Video Slider</h2>
      <button id="add-video-btn">Add New Video</button>
	  <button id="open-delete-popup-btn">Delete Selected</button>

  <div id="delete-popup" class="popup">
    <div class="popup-content delete-popup">
        <span class="popup-close">&times;</span>
        <h3>Delete Video</h3>
        <p>Are you sure you want to delete selected videos?</p>
        <div class="popup-buttons">
            <button id="confirm-delete-btn">Delete</button>
            <button class="cancel-btn">Cancel</button>
        </div>
    </div>
  </div>
        <div id="add-popup" class="popup">
            <div class="popup-content">
                <span class="popup-close">&times;</span>
                <h3>Add New Video</h3>
                    <form id="video-form">
						<input type="text" id="video-title" name="video_title" placeholder="Video Title" style="width: 100%;" required><br><br>
						<input type="text" id="thumbnail-url" name="thumbnail_url" placeholder="YouTube Video URL" style="width: 100%;" required><br>
						<div id="video-preview-container" class="video-preview-container"></div> <br>
					   
						 
						 <input type="checkbox" id="video-preview" name="video_preview" value="1">
					 
						 <label for="video-preview"> Use Custom Image</label><br><br>
			 
                         <input type="text" id="custom-image" name="custom_image" style="display: none;">
						<img id="custom-image-preview" src="#" alt="No Image Selected Yet" style="display: none;">
						<button id="upload-image-button">Select Image</button>

						
						<div class="popup-buttons">
							<button type="submit" id="submit-video-btn">Submit</button>
							<button type="button" class="cancel-btn">Cancel</button>
						</div>
                 </form>
            </div>
        </div>
        
    <div id="success-popup" class="popup">
        <div class="popup-content">
            <p id="success-message"></p>
        </div>
    </div>
    
    <div id="video-list-container">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                   <th style="width: 35px; text-align: center;"><input type="checkbox" id="select-all-checkbox"></th>
                    <th style="width:35px; text-align:center;">ID</th>
                    <th>Video Title</th>
                    <th>Thumbnail</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="video-list"></tbody>
        </table>
    </div>
    
    <div id="edit-popup" class="popup">
        <div class="popup-content">
            <span class="popup-close">&times;</span>
            <h3>Edit Video</h3>
            <form id="edit-form">
                <input type="hidden" id="edit-video-id" name="video_id">
                <input type="text" id="edit-video-title" name="video_title" placeholder="Video Title" style="width: 100%;" required><br><br>
                <input type="text" id="edit-thumbnail-url" name="thumbnail_url" placeholder="YouTube Video URL" style="width: 100%;" required><br>
                <div id="edit-video-preview-container" class="video-preview-container"></div><br>
        
				 <input type="checkbox" id="edit-video-preview" name="video_preview" value="1" >
				 <label for="edit-video-preview">Use Custom Image</label><br>
				 <br>
					<input type="text" id="edit-custom-image" name="edit_custom_image" style="display: none;">
					<img id="edit-custom-image-preview" src="#" alt="No Image Selected Yet" style="">
					<button id="edit-upload-image-button">Select Image</button>
				 
                <div class="popup-buttons">
                    <button type="submit">Save</button>
                    <button type="button" class="cancel-btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="delete-popup" class="popup">
        <div class="popup-content delete-popup">
            <span class="popup-close">&times;</span>
            <h3>Delete Video</h3>
            <p>Are you sure you want to delete this video?</p>
            <div class="popup-buttons">
                <button id="confirm-delete-btn">Delete</button>
                <button class="cancel-btn">Cancel</button>
            </div>
            <input type="hidden" id="delete-video-id">
        </div>
    </div>
 </div>

<script>
jQuery(document).ready(function($) {
 
	   // Function to open the media library for image selection
   function BcYtopenMediaLibrary(previewContainer) {
        var customUploader = wp.media({
            title: 'Select Image',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });

        customUploader.on('select', function() {
            var attachment = customUploader.state().get('selection').first().toJSON();
            $(previewContainer).attr('src', attachment.url).show();
            $('#custom-image').val(attachment.url);
        });

        customUploader.open();
    }

    // Open media library for custom image selection
    $('#upload-image-button').on('click', function(e) {
        e.preventDefault();
        BcYtopenMediaLibrary('#custom-image-preview');
    });

    // Open media library for custom image selection in the edit form
    $('#edit-upload-image-button').on('click', function(e) {
        e.preventDefault();
        BcYtopenMediaLibrary('#edit-custom-image-preview');
    });
	
 
	  jQuery('#video-preview').change(function() {
        if (jQuery(this).is(':checked')) {
            jQuery('#upload-image-button').show();
			jQuery('#custom-image').show();
			jQuery('#custom-image-preview').show();
			 

        } else {
           jQuery('#upload-image-button').hide();
			jQuery('#custom-image').hide();
			jQuery('#custom-image-preview').hide();
			 
        }
      });
	  
	    jQuery('#edit-video-preview').change(function() {
        if (jQuery(this).is(':checked')) {
				jQuery('#edit-custom-image').show();
				jQuery('#edit-upload-image-button').show();
				jQuery('#edit-custom-image-preview').show();
			 

        } else {
			jQuery('#edit-custom-image').hide();
			jQuery('#edit-upload-image-button').hide();
			jQuery('#edit-custom-image-preview').hide();
        }
    });
 
	
    // Function to fetch and display saved videos
    function fetchVideos() {
        $.ajax({
            type: 'GET',
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            data: {
                action: 'get_saved_videos'
            },
            success: function(response) {
                jQuery('#video-list').html(response);
            }
        });
    }

    // Display saved videos on page load
    fetchVideos();

    // Toggle add form popup
    jQuery('#add-video-btn').click(function() {
        jQuery('#add-popup').fadeIn();
    });

    // Close popup when close button is clicked
    jQuery('.popup-close, .cancel-btn').click(function() {
        jQuery('.popup').fadeOut();
    });

    // Submit add form via AJAX
    jQuery('#video-form').submit(function(e) {
        e.preventDefault();
        var video_title = jQuery('#video-title').val();
        var thumbnail_url = jQuery('#thumbnail-url').val();
        var video_preview = jQuery('#video-preview').val();
         if(jQuery('#video-preview').is(":checked"))
		 {
			var video_preview = 1;

		 }
		 
		 else
		 {
			 var video_preview = 0;
		 }
 
        var custom_image = jQuery('#custom-image').val(); // Added custom image field

        $.ajax({
            type: 'POST',
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            data: {
                action: 'save_video',
                video_title: video_title,
                thumbnail_url: thumbnail_url,
                video_preview: video_preview,
                custom_image: custom_image // Added custom image field
            },
            success: function(response) {
                jQuery('#success-message').text(response);
                jQuery('#success-popup').fadeIn();
                // Fetch and display saved videos after adding
                fetchVideos();
                // Hide success message after 3 seconds
                setTimeout(function() {
                    jQuery('#success-popup').fadeOut();
                }, 3000);
                // Close popup after adding
                jQuery('.popup').fadeOut();
                // Clear form fields
                jQuery('#video-form')[0].reset();
            }
        });
    });

    // Open edit form when edit button is clicked
		jQuery(document).on('click', '.edit-btn', function(e) {
			e.preventDefault();
			var video_id = jQuery(this).data('id');

			$.ajax({
				type: 'GET',
				url: '<?php echo admin_url("admin-ajax.php"); ?>',
				data: {
					action: 'get_video_details',
					video_id: video_id
				},
				success: function(response) {
					var video = JSON.parse(response);
					jQuery('#edit-video-id').val(video.id);
					jQuery('#edit-video-title').val(video.video_title);
					jQuery('#edit-thumbnail-url').val(video.thumbnail_url);
					jQuery('#edit-custom-image').val(video.custom_image); // Added custom image field
					//jQuery('#edit-custom-image-preview').src(video.custom_image); // Added custom image field
					 jQuery('#edit-custom-image-preview').attr("src", video.custom_image);
					
					// Set the value of video_preview and update the checkbox
					var video_preview_value = video.video_preview;
					if (video_preview_value == 1) {
						jQuery('#edit-video-preview').prop('checked', true);
						jQuery('#edit-custom-image').show();
						jQuery('#edit-upload-image-button').show();
					} else {
						jQuery('#edit-video-preview').prop('checked', false);
						jQuery('#edit-custom-image').hide();
						jQuery('#edit-upload-image-button').hide();
					}

					// Generate video preview based on thumbnail URL
					bcYtgenerateVideoPreview(jQuery('#edit-thumbnail-url'), jQuery('#edit-video-preview-container'));
					jQuery('#edit-popup').fadeIn();
				}
			});
		});

    // Submit edit form via AJAX
    jQuery('#edit-form').submit(function(e) {
        e.preventDefault();
        var video_id = jQuery('#edit-video-id').val();
        var video_title = jQuery('#edit-video-title').val();
        var thumbnail_url = jQuery('#edit-thumbnail-url').val();
 
	    if(jQuery('#edit-video-preview').is(":checked"))
		 {
			var video_preview = 1;
		 }
		 
		 else
		 {
			 var video_preview = 0;
 
		 }
		
      //  var custom_image = jQuery('#edit-custom-image').val(); // Added custom image field
		var custom_image = jQuery('#edit-custom-image-preview').attr('src');
  
        $.ajax({
            type: 'POST',
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            data: {
                action: 'update_video',
                video_id: video_id,
                video_title: video_title,
                thumbnail_url: thumbnail_url,
                video_preview: video_preview,
                custom_image: custom_image // Added custom image field
            },
            success: function(response) {
                jQuery('#success-message').text(response);
                jQuery('#success-popup').fadeIn();
                // Fetch and display saved videos after updating
                fetchVideos();
                // Hide success message after 3 seconds
                setTimeout(function() {
                    jQuery('#success-popup').fadeOut();
                }, 3000);
                // Close popup after updating
                jQuery('.popup').fadeOut();
            }
        });
    });

    // Open delete confirmation popup when delete button is clicked
    jQuery(document).on('click', '.delete-btn', function(e) {
        e.preventDefault();
        var video_id = jQuery(this).data('id');
        jQuery('#delete-popup').fadeIn();
        // Store video ID in a hidden field for later use
        jQuery('#delete-video-id').val(video_id);
    });

    // Confirm deletion and send AJAX request
    jQuery('#confirm-delete-btn').click(function() {
        var video_id = jQuery('#delete-video-id').val();

        $.ajax({
            type: 'POST',
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            data: {
                action: 'delete_video',
                video_id: video_id
            },
            success: function(response) {
                jQuery('#success-message').text(response);
                jQuery('#success-popup').fadeIn();
                // Fetch and display saved videos after deletion
                fetchVideos();
                // Hide success message after 3 seconds
                setTimeout(function() {
                    jQuery('#success-popup').fadeOut();
                }, 3000);
                // Close popup after deletion
                jQuery('.popup').fadeOut();
            }
        });
    });

    // Function to validate YouTube URL format and generate video preview
    jQuery('#thumbnail-url, #edit-thumbnail-url').on('input', function() {
        bcYtgenerateVideoPreview(jQuery(this), jQuery(this).siblings('.video-preview-container'));
    });

    // Function to generate video preview with YouTube thumbnail
    function bcYtgenerateVideoPreview(input, container) {
        var thumbnail_url = jQuery(input).val();

        // Validate YouTube URL format for Thumbnail URL
        if (bcYtisValidYouTubeUrl(thumbnail_url)) {
            var videoId = bcYtgetYouTubeVideoId(thumbnail_url);
            if (videoId) {
				var thumbnailUrl = '';
				if (thumbnail_url.indexOf('/shorts/') !== -1) {
					// For Shorts URLs, use the 'default' thumbnail
					thumbnailUrl = 'https://i.ytimg.com/vi/' + videoId + '/default.jpg';
				} else {
					// For regular video URLs, use the 'hqdefault' thumbnail
					thumbnailUrl = 'https://img.youtube.com/vi/' + videoId + '/hqdefault.jpg';
				}
				var videoPreviewHtml = '<img src="' + thumbnailUrl + '" alt="Video Thumbnail">';
				jQuery(container).html(videoPreviewHtml);
			}
        } else {
            // Clear video preview if URL is not valid
            jQuery(container).empty();
            // jQuery(input).closest('form').find('#video-preview').val('').prop('readonly', false);
        }
    }

    // Function to validate YouTube URL format
    function bcYtisValidYouTubeUrl(url) {
        // Regular expression to match YouTube video URLs
        var youtubeRegExp = /^(https?\:\/\/)?(www\.youtube\.com|youtu\.?be)\/.+$/;
        return youtubeRegExp.test(url);
    }

    // Function to extract video ID from YouTube URL
    function bcYtgetYouTubeVideoId(url) {
        var videoId = '';
		// Extract video ID from various YouTube URL formats
		if (url.indexOf('youtube.com/watch') !== -1) {
			videoId = url.split('v=')[1];
		} else if (url.indexOf('youtu.be') !== -1) {
			videoId = url.split('youtu.be/')[1];
		} else if (url.indexOf('/shorts/') !== -1) {
			videoId = url.split('/shorts/')[1];
		}
		// Remove additional parameters
		if (videoId.indexOf('&') !== -1) {
			videoId = videoId.split('&')[0];
		}
		return videoId;
    }
	
	// Function to handle checkbox selections
	var selectedItems = [];
	jQuery(document).on('change', '.delete-checkbox', function() {
		var videoId = $(this).data('id');
		if ($(this).is(':checked')) {
			selectedItems.push(videoId);
		} else {
			selectedItems = selectedItems.filter(item => item !== videoId);
		}
	});
 
	// Confirm deletion and send AJAX request for multiple items
	jQuery('#confirm-delete-btn').click(function() {
     // Send AJAX request with the list of selected items
      $.ajax({
        type: 'POST',
        url: '<?php echo admin_url("admin-ajax.php"); ?>',
        data: {
            action: 'delete_multiple_videos',
            video_ids: selectedItems // Send the list of selected video IDs
        },
        success: function(response) {
            jQuery('#success-message').text(response);
            jQuery('#success-popup').fadeIn();
            // Fetch and display saved videos after deletion
            fetchVideos();
            // Hide success message after 3 seconds
            setTimeout(function() {
                $('#success-popup').fadeOut();
            }, 3000);
            // Close popup after deletion
            jQuery('.popup').fadeOut();
        }
      });
   });
	
	
	
	// Open delete popup when delete button is clicked
	jQuery('#open-delete-popup-btn').click(function() {
		$('#delete-popup').fadeIn();
	});	


	// Handle click event of select all checkbox
	jQuery('#select-all-checkbox').click(function() {
		var isChecked = $(this).prop('checked');
		jQuery('.delete-checkbox').prop('checked', isChecked);
	});
	 
	 // Open delete confirmation popup when delete button is clicked
	jQuery('#open-delete-popup-btn').click(function() {
		var selectedCheckboxes = jQuery('.delete-checkbox:checked');
		if (selectedCheckboxes.length === 0) {
			// If no checkboxes are selected, display a message
			alert("No videos selected.");
			 jQuery('#delete-popup').hide();
			return;
		}
 
	});
  });
</script>
<?php
}


// Handle AJAX request to delete multiple videos
function delete_multiple_videos_ajax_handler() {
    if (isset($_POST['video_ids']) && is_array($_POST['video_ids'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . "bc_yts_youtube_slider";
        foreach ($_POST['video_ids'] as $video_id) {
            $wpdb->delete($table_name, array('id' => $video_id));
        }
        echo "Selected videos have been deleted.";
    } else {
        echo "No videos selected for deletion.";
    }
    wp_die();
}
add_action("wp_ajax_delete_multiple_videos", "delete_multiple_videos_ajax_handler");

// Handle AJAX request to save video details
function bc_yts_save_video_ajax_handler()
{
    global $wpdb;
 
    // Sanitize and retrieve data from AJAX request
    $video_title = isset($_POST["video_title"]) ? sanitize_text_field($_POST["video_title"]) : '';
    $thumbnail_url = isset($_POST["thumbnail_url"]) ? esc_url_raw($_POST["thumbnail_url"]) : '';
    $video_preview = isset($_POST["video_preview"]) ? intval($_POST["video_preview"]) : 0;
    $custom_image = isset($_POST["custom_image"]) ? esc_url_raw($_POST["custom_image"]) : '';

    // Check if required fields are provided
    if (empty($video_title) || empty($thumbnail_url)) {
        wp_send_json_error("Video title and thumbnail URL are required.");
    }

    // Insert data into database
    $table_name = $wpdb->prefix . "bc_yts_youtube_slider";
    $insert_result = $wpdb->insert($table_name, [
        "video_title" => $video_title,
        "thumbnail_url" => $thumbnail_url,
        "video_preview" => $video_preview,
        "custom_image" => $custom_image,
    ]);

    // Check if insertion was successful
    if (!$insert_result) {
        wp_send_json_error("Failed to save video details.");
    }

    // Send success response
    echo "Video saved successfully!";

}

add_action("wp_ajax_save_video", "bc_yts_save_video_ajax_handler");


 
// Handle AJAX request to get saved videos
function bc_yts_get_saved_videos_ajax_handler()
{
    global $wpdb;

    // Retrieve saved videos
    $table_name = $wpdb->prefix . "bc_yts_youtube_slider";
    $saved_videos = $wpdb->get_results("SELECT * FROM $table_name");

    // Prepare HTML for displaying saved videos
    $html = "";
    foreach ($saved_videos as $key => $video) {
        // Sanitize video title
        $video_title = esc_html($video->video_title);

        // Extract video ID from the link
        $videoLink = $video->thumbnail_url;
        $videoId = '';

        if (strpos($videoLink, 'v=') !== false) {
            $videoId = explode("v=", parse_url($videoLink, PHP_URL_QUERY))[1];
        } elseif (strpos($videoLink, '/embed/') !== false) {
            $videoId = explode("/embed/", $videoLink)[1];
        } elseif (strpos($videoLink, '/shorts/') !== false) {
            $videoId = explode("/shorts/", $videoLink)[1];
        }

        // Construct thumbnail URL with maxresdefault quality
        $thumbnailUrl = "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg";

        $html .= "<tr>";
		$html .= "<td style='text-align:center;'><input type='checkbox' class='delete-checkbox' data-id='" . $video->id . "'></td>"; 
        $html .= '<td style="text-align:center;">' . ($key + 1) . "</td>";
        $html .= "<td>" . $video_title . "</td>";
        if ($video->video_preview == 1 && $video->custom_image != "") {
            $html .= '<td><img src="' . esc_url($video->custom_image) . '" alt="Thumbnail" style="width:60px;"></td>';
        } else {
            $html .= '<td><img src="' . esc_url($thumbnailUrl) . '" alt="Thumbnail" style="width:60px;"></td>';
        }

        $html .= '<td><a href="#" class="edit-btn" data-id="' . $video->id . '">Edit</a> | <a href="#" class="delete-btn" data-id="' . $video->id . '">Delete</a></td>';
        $html .= "</tr>";
    }

    // Send response
    echo $html;

    // Always die in functions echoing AJAX content
    wp_die();
}

add_action("wp_ajax_get_saved_videos", "bc_yts_get_saved_videos_ajax_handler");


// Handle AJAX request to get video details for editing
function bc_yts_get_video_details_ajax_handler()
{
    global $wpdb;

    // Get video ID from AJAX request
    $video_id = $_GET["video_id"];

    // Retrieve video details
    $table_name = $wpdb->prefix . "bc_yts_youtube_slider";
    $video = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $video_id)
    );

    // Send response as JSON
    echo json_encode($video);

    // Always die in functions echoing AJAX content
    wp_die();
}

add_action("wp_ajax_get_video_details", "bc_yts_get_video_details_ajax_handler");

// Handle AJAX request to update video details
function bc_yts_update_video_ajax_handler()
{
    global $wpdb;

    // Get data from AJAX request
    $video_id = $_POST["video_id"];
    $video_title = $_POST["video_title"];
    $thumbnail_url = $_POST["thumbnail_url"];
    $video_preview = $_POST["video_preview"];
    $custom_image = $_POST["custom_image"]; // Added custom image field

    // Update data in database
    $table_name = $wpdb->prefix . "bc_yts_youtube_slider";
    $wpdb->update(
        $table_name,
        [
            "video_title" => $video_title,
            "thumbnail_url" => $thumbnail_url,
            "video_preview" => $video_preview,
            "custom_image" => $custom_image,
        ],
        ["id" => $video_id]
    ); // Updated query to include custom image field

    // Send response
    echo "Video updated successfully!";

    // Always die in functions echoing AJAX content
    wp_die();
}

add_action("wp_ajax_update_video", "bc_yts_update_video_ajax_handler");

// Handle AJAX request to delete video
function bc_yts_delete_video_ajax_handler()
{
    global $wpdb;

    // Get video ID from AJAX request
    $video_id = $_POST["video_id"];

    // Delete data from database
    $table_name = $wpdb->prefix . "bc_yts_youtube_slider";
    $wpdb->delete($table_name, ["id" => $video_id]);

    // Send response
    echo "Video deleted successfully!";

    // Always die in functions echoing AJAX content
    wp_die();
}

add_action("wp_ajax_delete_video", "bc_yts_delete_video_ajax_handler");

// Function to enqueue scripts and styles
function bc_yts_enqueue_youtube_slider_scripts()
{
    wp_enqueue_script("jquery");
}

add_action("admin_enqueue_scripts", "bc_yts_enqueue_youtube_slider_scripts");

add_shortcode("bc_yt_slider", "bcYtVideoSlider");

function bcYtVideoSlider()
{
    ob_start();
    wp_enqueue_style(
        "style_file",
        plugin_dir_url(__FILE__) . "style/style.css"
    );
	
	
	wp_enqueue_style("owl-carousel", "https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.0.0-beta.3/assets/owl.carousel.css");
    wp_enqueue_style("magnific-popup", "https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/magnific-popup.css");
    wp_enqueue_style("font-awesome", "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css");
   
    ?>
	 
	<script src='https://code.jquery.com/jquery-1.11.1.min.js'></script>
	<script src='https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.0.0-beta.3/owl.carousel.js'></script>
	<script src='https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/jquery.magnific-popup.js'></script>
 
	<?php
	 global $wpdb;
	 // Retrieve saved videos
	 $table_name = $wpdb->prefix . "bc_yts_youtube_slider";
	 $saved_videos = $wpdb->get_results("SELECT * FROM $table_name");

	 $SettingTable_name = $wpdb->prefix . "bc_yts_youtube_slider_settings";
	 $savedSettings = $wpdb->get_row(
		 "SELECT * FROM $SettingTable_name WHERE id = '1'"
	 );
	 $arrowslected = $savedSettings->arrow;
	 $arrowSectionHeight = $savedSettings->arrowwidth + 15;
	 $arrowSectionWidth = $savedSettings->arrowwidth + 8;
 ?>
	
	<style>
	   .owl-prev
		{
			 background:url(../wp-content/plugins/yt-slider/img/<?php echo $arrowslected; ?>-left.svg) no-repeat 0 0 !Important;
			 background-size: <?php echo $savedSettings->arrowwidth; ?>px !Important;
			 height: <?php echo $arrowSectionHeight; ?>px !Important;
			 width: <?php echo $arrowSectionWidth; ?>px !Important;
		}
		 
		.owl-next
		{
			 background:url(../wp-content/plugins/yt-slider/img/<?php echo $arrowslected; ?>-right.svg) no-repeat 0 0 !Important; 
			 background-size: <?php echo $savedSettings->arrowwidth; ?>px !Important;
			 height: <?php echo $arrowSectionHeight; ?>px !Important;
			 width: <?php echo $arrowSectionWidth; ?>px !Important;
		}
	</style>
	<div class="owl-carousel">
		<?php foreach ($saved_videos as $video) {

      $videoLink = $video->thumbnail_url;
      $video_preview = $video->video_preview;
      $custom_image = $video->custom_image;
      $videoId = explode("=", parse_url($videoLink, PHP_URL_QUERY))[1];

      if (str_contains($videoLink, "embed")) {
          $videoId = explode("/embed/", $videoLink)[1];
      }

      if (str_contains($videoLink, "/shorts/")) {
          $videoId = explode("/shorts/", $videoLink)[1];
      }

      $thumbnailUrl = "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg";

	  $headers = get_headers($thumbnailUrl);
      if (strpos($headers[0], '200') === false) { $thumbnailUrl = "https://img.youtube.com/vi/{$videoId}/mqdefault.jpg"; }
 
      if ($video_preview == 1 && !empty($custom_image)) {
          $thumbnailUrl = $custom_image;
      }

      ?>
 
		<div class="item">
			<a class="popup-youtube" href="https://www.youtube.com/watch?v=<?php echo $videoId; ?>">
			<img src="<?php echo $thumbnailUrl; ?>"><i class="" aria-hidden="true"> <img src="../wp-content/plugins/yt-slider/img/play-<?php echo $savedSettings->play_icon; ?>.svg" style="max-width: 50px;"></i></a>
		</div>
	<?php
		}  ?>
	</div>
 
	<script>
		 jQuery('.owl-carousel').owlCarousel({
		  autoplay: true,
		  autoplayTimeout: <?php echo $savedSettings->speed; ?>,
		  autoplayHoverPause: true,
		  loop: true,
		  margin: <?php if ($savedSettings->images_margin) {
        echo $savedSettings->images_margin;
    } else {
        echo "30";
    } ?>,
		  responsiveClass: true,
		  nav: true,
		  loop: true,
		  stagePadding: 100,
		  responsive: {
			0: {
			  items: <?php echo $savedSettings->images_mobile; ?>
			},
			568: {
			  items: <?php echo $savedSettings->images_tablet; ?>
			},
			600: {
			  items: <?php echo $savedSettings->images_tablet; ?>
			},
			1000: {
			  items: <?php echo $savedSettings->images_desktop; ?>
			}
		  }
		})
		jQuery(document).ready(function() {
		  jQuery('.popup-youtube').magnificPopup({
			disableOn: 320,
			type: 'iframe',
			mainClass: 'mfp-fade',
			removalDelay: 160,
			preloader: false,
			fixedContentPos: true
		  });
		});
		jQuery('.item').magnificPopup({
		  delegate: 'a',
		});
 
	</script>
 
<?php return ob_get_clean();
}

function bc_yts_youtube_slider_settings_page()
{
    global $wpdb;
    
    // Enqueue stylesheet
    wp_enqueue_style("style_file", plugin_dir_url(__FILE__) . "style/style.css");

    $table_name_settings = $wpdb->prefix . "bc_yts_youtube_slider_settings";

    // Check if form is submitted
    if (isset($_POST["submit"])) {
        // Sanitize and retrieve form data
        $speed = intval($_POST["speed"]);
        $images_margin = intval($_POST["images_margin"]);
        $images_mobile = intval($_POST["images_mobile"]);
        $images_tablet = intval($_POST["images_tablet"]);
        $images_desktop = intval($_POST["images_desktop"]);
        $arrow = intval($_POST["arrow_existing"]);
        $arrowwidth = intval($_POST["arrowwidth"]);

        // Sanitize file upload (play icon)
        $play_icon = bc_yts_handle_svg_upload($_FILES["play_icon"], "play_icon");
        $play_icon = empty($play_icon) ? sanitize_text_field($_POST["play_icon_existing"]) : $play_icon;

        // Update settings in the database
        $wpdb->update(
            $table_name_settings,
            compact("speed", "images_margin", "arrow", "arrowwidth", "images_mobile", "images_tablet", "images_desktop", "play_icon"),
            ["id" => 1],
            ["%d", "%d", "%d", "%d", "%d", "%d", "%d", "%s"],
            ["%d"]
        );
    }

    // Retrieve existing settings from the database
    $settings = $wpdb->get_row("SELECT * FROM $table_name_settings");
    // Display the form
    ?>
    <div class="wrap yt-slider-settings">
         <h2>YouTube Video Slider Settings</h2>
        <form method="post" action="" enctype="multipart/form-data">
            <label for="speed">Slide Speed: (in milliseconds)</label>
            <input type="number" id="speed" name="speed" value="<?php echo $settings->speed ?? ""; ?>"><br><br>

            <label for="images_margin">Margin between slider Images: (px)</label>
            <input type="number" id="images_margin" name="images_margin" value="<?php echo $settings->images_margin ?? ""; ?>"><br><br>
             
            <label for="arrow">Arrows :</label><br>
            <?php for ($i = 1; $i <= 5; $i++) : ?>
                <input name="arrow_existing" id="arrow<?php echo $i; ?>" type="radio" value="<?php echo $i; ?>" <?php if ($settings->arrow == $i) { ?> checked="checked" <?php } ?>>
                <label class="form-control" for="arrow<?php echo $i; ?>" style="display: inline;"><img style="width:30px; margin-left:15px;" src="../wp-content/plugins/yt-slider/img/<?php echo $i; ?>-left"> <img style="width:30px; margin-left:15px;" src="../wp-content/plugins/yt-slider/img/<?php echo $i; ?>-right"></label> 
                <br><br>
            <?php endfor; ?>
            
            <label for="arrowwidth">Arrows Size: (px) </label>
            <input type="number" id="arrowwidth" name="arrowwidth" value="<?php echo $settings->arrowwidth ?? ""; ?>" min="10" max="50">
            <br>  
            <span> min:10, Max:50 </span><br><br>

            <label for="images_mobile">Slider Images (Mobile/Tablet/Desktop):</label><br>
            <select id="images_mobile" name="images_mobile">
                <?php for ($i = 1; $i <= 8; $i++) : ?>
                    <option value="<?php echo $i; ?>" <?php if ($settings->images_mobile == $i) { echo "selected"; } ?>><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
            <select id="images_tablet" name="images_tablet">
                <?php for ($i = 1; $i <= 8; $i++) : ?>
                    <option value="<?php echo $i; ?>" <?php if ($settings->images_tablet == $i) { echo "selected"; } ?>><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
            <select id="images_desktop" name="images_desktop">
                <?php for ($i = 1; $i <= 8; $i++) : ?>
                    <option value="<?php echo $i; ?>" <?php if ($settings->images_desktop == $i) { echo "selected"; } ?>><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
            
            <br><br>
            <label for="play_icon">Play Button:</label><br>
            <?php for ($i = 1; $i <= 4; $i++) : ?>
                <input name="play_icon_existing" id="play<?php echo $i; ?>" type="radio" value="<?php echo $i; ?>" <?php if ($settings->play_icon == $i) { ?> checked="checked" <?php } ?>/>
                <label class="form-control" for="play<?php echo $i; ?>" style="display: inline;"><img style="width:30px; margin-left:15px;" src="../wp-content/plugins/yt-slider/img/play-<?php echo $i; ?>.svg"></label> 
                <br><br>
            <?php endfor; ?>
            
            <input type="submit" name="submit" value="Save">
        </form>
    </div>

    <!-- Your JavaScript code here -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileUploadButtons = document.querySelectorAll('.file-upload-button');

            fileUploadButtons.forEach(function(button) {
                const input = button.querySelector('input[type="file"]');
                const span = button.querySelector('span');
                input.addEventListener('change', function() {
                    if (input.files.length > 0) {
                        const filename = input.files[0].name;
                        span.innerHTML = filename;
                    } else {
                        span.innerHTML = 'Browse';
                    }
                });
            });
        });
    </script>
    <?php
}

function bc_yts_handle_svg_upload($file, $name)
{
    // Get the upload directory
    $upload_dir = wp_upload_dir();
    // Set the path for the image folder
    $upload_path = $upload_dir["basedir"] . "/img/";
    // Set the full path for the uploaded file
    $upload_file = $upload_path . $name . ".svg";
    // Set the URL for the uploaded file
    $upload_url = $upload_dir["baseurl"] . "/img/" . $name . ".svg";

    // Check if the upload directory exists, if not, create it
    if (!file_exists($upload_path)) {
        mkdir($upload_path, 0755, true);
    }

    // Move the uploaded file to the designated location
    if (!empty($file["tmp_name"])) {
        // Check if file is uploaded
        if (move_uploaded_file($file["tmp_name"], $upload_file)) {
            return $upload_url; // Return the URL of the uploaded file
        } else {
            // Handle upload failure
            error_log("Failed to move uploaded file: " . $file["tmp_name"]);
        }
    } else {
        // Handle case where no file is uploaded
        error_log("No file uploaded.");
    }

    return ""; // Return empty string if upload failed or no file uploaded
}
