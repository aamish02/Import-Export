<?php
/*
Plugin Name: Post Import Export 
Plugin URI: http://your-plugin-website.com/
Description: A simple plugin to import and export posts with all blocks in CSV format.
Version: 1.0
Author: Aamish Ali
Author URI: http://aamish.com/
*/
function pie_import_export_add_menu_pages() {
    add_menu_page('All Import', 'All Import', 'manage_options', 'all-import', 'pie_all_import_page');
    add_submenu_page('all-import', 'New Import', 'New Import', 'manage_options', 'new-import', 'pie_import_page');
    add_submenu_page('all-import', 'Manage Imports', 'Manage Imports', 'manage_options', 'manage-imports', 'pie_manage_import_page');
    add_submenu_page('all-import', 'Settings', 'Settings', 'manage_options', 'setting-imports', 'pie_setting_import_page');

    add_menu_page('All Export', 'All Export', 'manage_options', 'all-export', 'pie_all_export_page');
    add_submenu_page('all-export', 'New Export', 'New Export', 'manage_options', 'new-export', 'pie_export_page');
    add_submenu_page('all-export', 'Manage Exports', 'Manage Exports', 'manage_options', 'manage-exports', 'pie_manage_export_page');
    add_submenu_page('all-export', 'Settings', 'Settings', 'manage_options', 'setting-exports', 'pie_setting_export_page');
}
add_action('admin_menu', 'pie_import_export_add_menu_pages');
function pie_all_import_page() {
    $categories = get_categories();
    $uploaded_files = get_uploaded_csv_files(); // Function to get a list of uploaded CSV files
    ?>
    <div class="wrap">
        <h1>Post Import</h1>
        <form method="post" enctype="multipart/form-data">
            <h2>Import Posts</h2>
            <label for="import_file">Select CSV File:</label>
            <input type="file" name="import_file" id="import_file" accept=".csv">
            <input type="submit" name="import_posts" class="button button-primary" value="Import Posts">
        </form>
        <?php if (!empty($uploaded_files)) : ?>
            <h2>Choose from Uploaded Files:</h2>
            <form method="post">
                <label for="select_uploaded_file">Select from Uploaded Files:</label>
                <select name="select_uploaded_file" id="select_uploaded_file">
                    <option value="">Select a file</option>
                    <?php foreach ($uploaded_files as $file) : ?>
                        <option value="<?php echo esc_attr($file['url']); ?>"><?php echo esc_html($file['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" name="select_file" class="button button-primary" value="Select File">
            </form>
        <?php endif; ?>

        <?php
        if (isset($_POST['import_posts'])) {
            pie_handle_import_export_actions();
        }

        if (isset($_POST['select_file'])) {
            $selected_file_url = isset($_POST['select_uploaded_file']) ? $_POST['select_uploaded_file'] : '';
            if ($selected_file_url) {
                $file_content = file_get_contents($selected_file_url);
                if ($file_content !== false) {
                    display_selected_file_content($file_content); // Function to display the selected file's content
                } else {
                    echo '<p class="error">Error: Unable to read the selected file.</p>';
                }
            } else {
                echo '<p class="error">Error: Please select a file to import.</p>';
            }
        }
        ?>
        <h2>Options:</h2>
        <button class="button button-secondary" onclick="showUploadNew()">Upload a New CSV File</button>
        <button class="button button-secondary" onclick="showUploadExisting()">Upload an Existing CSV File</button>
        <a href="<?php echo esc_url(add_query_arg('download_file', 'true')); ?>" class="button button-secondary">Download Sample CSV File</a>
    </div>
    <script>
        function showUploadNew() {
            document.getElementById('import_file').style.display = 'block';
            document.querySelector('input[name="import_posts"]').style.display = 'block';
            document.getElementById('select_uploaded_file').style.display = 'none';
            document.querySelector('input[name="select_file"]').style.display = 'none';
        }

        function showUploadExisting() {
            document.getElementById('import_file').style.display = 'none';
            document.querySelector('input[name="import_posts"]').style.display = 'none';
            document.getElementById('select_uploaded_file').style.display = 'block';
            document.querySelector('input[name="select_file"]').style.display = 'block';
        }
    </script>
    <?php
}
function pie_all_export_page() {
    if (isset($_POST['export_posts'])) {
        // Call the export functions here
        pie_handle_post_export();
    }
    
    if (isset($_SESSION['pie_exported']) && $_SESSION['pie_exported'] === true) {
        echo '<div class="wrap">';
        echo '<h1>WP ALL EXPORT</h1>';
        echo '<h1><strong>New Export</strong></h1>';
        echo '<p>The export action has already been processed. Please click the button again to export posts.</p>';
        echo '<a href="' . admin_url('admin.php?page=post-export') . '" class="button button-primary">Export Posts</a>';
        echo '</div>';
        return;
    }
    ?>
      <style>
        .pie-export-form {
            display: <?php echo (isset($_GET['filter']) ? 'block' : 'none'); ?>;
            margin-top: 20px;
        }

        .pie-export-form label {
            display: block;
            margin-bottom: 5px;
        }

        .pie-export-form select {
            width: 100%;
            max-width: 300px;
        }

        .pie-add-filters-button {
            display: <?php echo (isset($_GET['filter']) ? 'none' : 'block'); ?>;
            margin-top: 50px;
            background-color: white;
            padding-top: 30px;
            padding-bottom: 80px;
            max-width: 1200px;
            padding-left:30px;
        }
        
        .button.button-primary {
            font-size: 30px;
            margin-right: 30px;
        }
        h2 {
            font-size: 2.5em;
        }
        .post-type-dropdown, .query-dropdown {
            display: none;
        }
    </style>
    <div class="wrap">
        <h1>WP ALL EXPORT</h1><br>
        <h1><strong>New Export</strong></h1>
        <?php if (isset($_SESSION['pie_exported']) && $_SESSION['pie_exported'] === true) : ?>
            <div class="export-processed-notice">
                <p>The export action has already been processed. Please click the button again to export posts.</p>
                <a href="<?= esc_url($_SERVER['REQUEST_URI']); ?>" class="button button-primary">Export Posts</a>
            </div>
        <?php else : ?>
            <div class="pie-add-filters-button">
                <h2>First, choose what to export.</h2>
                <button class="button button-primary" onclick="showFilters('postType')">Specific Post Type</button>
                <button class="button button-primary" onclick="showFilters('queryResults')">WP Query Results</button>
            </div>
            <form method="post" class="pie-export-form">
            <div class="post-type-dropdown">
                    <label for="post_type">Select Post Type:</label>
                    <select name="post_type" id="post_type">
                        <option value="">Choose a post type...</option>
                        <?php
                        $post_types = get_post_types(array('public' => true), 'objects');
                        foreach ($post_types as $post_type) {
                            // Exclude built-in post types like 'post' and 'page'
                            if ($post_type->name !== 'post' && $post_type->name !== 'page') {
                                ?>
                                <option value="<?php echo esc_attr($post_type->name); ?>"><?php echo esc_html($post_type->label); ?></option>
                                <?php
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="query-dropdown">
                    <label for="query_type">Select Query Type:</label>
                    <select name="query_type" id="query_type">
                        <option value="post">Post Type Query</option>
                        <option value="user">User Query</option>
                        <option value="comment">Comment Query</option>
                    </select>
                    <label for="custom_query">Enter Query:</label>
                    <textarea name="custom_query" id="custom_query" rows="5" cols="50"></textarea>
                </div>
                <h2>Export Posts</h2>
                <label for="include_custom_fields">
                    <input type="checkbox" name="include_custom_fields" id="include_custom_fields" value="1">
                    Include Custom Fields
                </label>
                <input type="submit" name="export_posts" class="button button-primary" value="Export Posts">
            </form>
            <script>
                function showFilters(filterType) {
                    var addButton = document.querySelector('.pie-add-filters-button');
                    var form = document.querySelector('.pie-export-form');
                    var postTypeDropdown = document.querySelector('.post-type-dropdown');
                    var queryDropdown = document.querySelector('.query-dropdown');
                    
                    if (filterType === 'postType') {
                        postTypeDropdown.style.display = 'block';
                        queryDropdown.style.display = 'none';
                    } else if (filterType === 'queryResults') {
                        postTypeDropdown.style.display = 'none';
                        queryDropdown.style.display = 'block';
                    }
                    
                    addButton.style.display = 'none';
                    form.style.display = 'block';
                }
            </script>
        <?php endif; ?>
    </div>
    <?php
}
function pie_handle_import_export_actions() {
    if (isset($_FILES['import_file'])) {
        $file_extension = strtolower(pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION));
        if ($file_extension === 'csv') {
            $imported_posts_count = pie_handle_post_import_from_csv($_FILES['import_file']['tmp_name']);
        } else {
            $imported_posts_count = 0;
        }
        if ($imported_posts_count > 0) {
            add_action('admin_notices', function () use ($imported_posts_count) {
                $message = $imported_posts_count . ' posts successfully imported.';
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
            });
        } else {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error is-dismissible"><p>No posts were imported. Please check the file format (CSV) and try again.</p></div>';
            });
        }
    } elseif (isset($_POST['export_posts'])) {
        pie_handle_post_export();
    }
}
add_action('admin_init', 'pie_handle_import_export_actions');
function esc_csv($data) {
    return '"' . str_replace('"', '""', $data) . '"';
}
function pie_handle_post_export() {
    // Define the CSV headers
    $csv_headers = array(
        'Post Slug',
        'Post Title',
        'Post Content',
        'Post Author',
        'Post Category',
        'Post Tags',
        'Post Format',
        'Blocks Data',
        'Featured Image URL',
        'Post Excerpt',
        'Publish Date',
        'Post Status',
        'ACF Fields', // Added ACF Fields header
        'Blocks Data CSV' // Added Blocks Data CSV header
    );
    
    $include_custom_fields = isset($_POST['include_custom_fields']) && $_POST['include_custom_fields'] === '1';
    // Initialize the CSV file
    $csv_file = fopen('php://temp', 'w');

    // Write the CSV headers to the file
    fputcsv($csv_file, $csv_headers);

    // Query the posts
    $args = array(
        'post_type' => 'post',
        'post_status' => array('publish', 'draft'), // Include both publish and draft statuses
        'posts_per_page' => -1,
    );

    $posts_query = new WP_Query($args);

    if ($posts_query->have_posts()) {
        while ($posts_query->have_posts()) {
            $posts_query->the_post();

            // Get post data
            $post_id = get_the_ID();
            $post_slug = get_post_field('post_name', $post_id);
            $post_title = get_the_title();
            $post_content = get_the_content();
            $post_author = get_the_author_meta('display_name', get_the_author_id());
            $post_categories = get_the_category();
            $post_category = '';
            if (!empty($post_categories)) {
                $post_category = $post_categories[0]->name;
            }
            $post_tags = get_the_tags();
            $post_tags_str = '';
            if ($post_tags) {
                $post_tags_str = implode(', ', wp_list_pluck($post_tags, 'name'));
            }
            $post_format = get_post_format($post_id);
            $blocks_data = get_post_meta($post_id, 'blocks_data', true);
            $featured_image_url = get_the_post_thumbnail_url($post_id);
            $post_excerpt = get_the_excerpt();

            // Get the publish date
            $post_publish_date = get_the_date('Y-m-d H:i:s', $post_id);

            // Get the post status
            $post_status = get_post_status($post_id);

            // Retrieve custom fields if they are included in the export.
            $acf_fields = array(); // Define an empty array for ACF fields

            if (function_exists('get_fields') && $include_custom_fields) {
                $acf_fields = get_fields($post_id);
            }

            // Prepare data for CSV
            $acf_fields_str = '';

            if (!empty($acf_fields)) {
                // Convert ACF fields array to a string for CSV
                $acf_fields_str = implode(', ', $acf_fields);
            }

            $csv_data = array(
                $post_slug,
                $post_title,
                $post_content,
                $post_author,
                $post_category, // Assign the category here
                $post_tags_str,
                $post_format,
                $blocks_data,
                $featured_image_url,
                $post_excerpt,
                $post_publish_date,
                $post_status,
                $acf_fields_str, // Use the string representation of ACF fields
                esc_csv($blocks_data)
            );

            $csv_data[4] = $post_category; // Update index to match header order

            // Write data to CSV
            fputcsv($csv_file, $csv_data);
        }

        // Reset the post data
        wp_reset_postdata();
    }

    // Output the CSV file for download
    $csv_filename = 'exported_posts_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $csv_filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Send the CSV file content to the browser
    rewind($csv_file);
    while (!feof($csv_file)) {
        echo fread($csv_file, 8192);
    }

    fclose($csv_file);
    exit(); // Make sure to exit after sending the file
}
function pie_handle_custom_fields_export() {
    $args = array(
        'post_type' => 'post',
        'posts_per_page' => -1, // Retrieve all posts
    );

    $posts = get_posts($args);

    if (!empty($posts)) {
        $csv_content = "Post Title,Post Content";

        // Automatically add ACF field names as headers
        $acf_fields_header_added = false; // Add this flag

        foreach ($posts as $post) {
            $acf_fields = get_fields($post->ID);

            if (!$acf_fields_header_added) {
                foreach ($acf_fields as $field_key => $field_value) {
                    // Clean field names for CSV
                    $cleaned_field_name = esc_csv(str_replace(array("\n", "\r"), " ", $field_key));
                    $csv_content .= "," . $cleaned_field_name;
                }
                $csv_content .= "\n";
                $acf_fields_header_added = true; // Set the flag to true
            }

            $csv_row = array(
                esc_csv($post->post_title),
                esc_csv($post->post_content),
            );

            // Append ACF field values to the CSV row
            foreach ($acf_fields as $field_key => $field_value) {
                $csv_row[] = esc_csv($field_value);
            }

            $csv_content .= '"' . implode('","', $csv_row) . "\"\n";
        }

        // Generate a unique filename
        $filename = 'all_posts_custom_fields_export_' . date('YmdHis') . '.csv';

        // Send the CSV file to the browser
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $csv_content;
        exit();
    } else {
        echo "No posts found for export.";
    }
}
function pie_handle_post_import_from_csv($file_path) {
    $imported_posts_count = 0;

    if (($handle = fopen($file_path, 'r')) !== false) {
        // Read the first row (CSV headers)
        $csv_headers = fgetcsv($handle);

        // Determine the column index for ACF Fields
        $acf_fields_column_index = array_search('ACF Fields', $csv_headers);
        $acf_mapping = array();

        if ($acf_fields_column_index !== false) {
            $acf_mapping_headers = explode(', ', $csv_headers[$acf_fields_column_index]);
            foreach ($acf_mapping_headers as $acf_header) {
                // Map CSV header to ACF field name (assuming the ACF field name is in the same format)
                $acf_mapping[$acf_header] = sanitize_key($acf_header);
            }
        }
        while (($data = fgetcsv($handle)) !== false) {
            $post_slug = isset($data[0]) ? sanitize_text_field($data[0]) : '';
            $post_title = isset($data[1]) ? sanitize_text_field($data[1]) : '';
            $post_content = isset($data[2]) ? wp_kses_post($data[2]) : '';
            $post_author = isset($data[3]) ? sanitize_text_field($data[3]) : '';
            $post_category_name = isset($data[4]) ? sanitize_text_field($data[4]) : '';
            $post_tags_str = isset($data[5]) ? sanitize_text_field($data[5]) : '';
            $post_tags = array_map('trim', explode(',', $post_tags_str));
            $post_format = isset($data[6]) ? sanitize_text_field($data[6]) : '';
            $blocks_data = isset($data[7]) ? sanitize_text_field($data[7]) : '';
            $featured_image_url = isset($data[8]) ? esc_url_raw($data[8]) : '';
            $post_excerpt = isset($data[9]) ? sanitize_text_field($data[9]) : '';
            $post_publish_date = isset($data[10]) ? sanitize_text_field($data[10]) : '';
            $post_status = isset($data[11]) ? sanitize_text_field($data[11]) : 'publish'; // Default to 'publish' if not provided
            $acf_fields = isset($data[12]) ? json_decode(sanitize_text_field($data[12]), true) : array();
            $blocks_data = isset($data[13]) ? sanitize_text_field($data[13]) : '';

            // Get the category ID based on the category name
            $category_id = 0;
            if ($post_category_name) {
                $category = get_term_by('name', $post_category_name, 'category');
                if ($category) {
                    $category_id = $category->term_id;
                }
            }


            // Prepare post data
            $post_data = array(
                'post_type' => 'post',
                'post_title' => $post_title,
                'post_name' => $post_slug,
                'post_content' => $post_content,
                'post_excerpt' => $post_excerpt,
                'post_author' => 1, // Set to 1 or any specific user ID as per your requirement
                'post_status' => $post_status, // Use provided post status
                'post_category' => array($category_id), // Use category ID here
                'tags_input' => $post_tags,
                'post_date' => $post_publish_date, // Set the publish date
            );

            // Set post format if available
            if ($post_format && in_array($post_format, array('aside', 'gallery', 'link', 'image', 'quote', 'status', 'video', 'audio', 'chat'))) {
                $post_data['post_format'] = $post_format;
            }

            // Insert the post
            $post_id = wp_insert_post($post_data);

            // Automatically map and update custom fields
            if (!empty($acf_mapping) && $post_id) {
                foreach ($acf_mapping as $csv_header => $acf_field_name) {
                    $acf_field_value = isset($data[$acf_fields_column_index]) ? sanitize_text_field($data[$acf_fields_column_index]) : '';
                    update_field($acf_field_name, $acf_field_value, $post_id);
                }
            }
            if (!empty($blocks_data) && $post_id) {
                update_post_meta($post_id, 'blocks_data', $blocks_data);
            }
            // Increase the imported posts count
            $imported_posts_count++;
        }

        fclose($handle);
    }

    return $imported_posts_count;
}
function pie_set_featured_image_from_url($image_url, $post_id) {
    $upload_dir = wp_upload_dir();
    $image_data = file_get_contents($image_url);
    $filename = basename($image_url);

    if (wp_mkdir_p($upload_dir['path'])) {
        $file = $upload_dir['path'] . '/' . $filename;
    } else {
        $file = $upload_dir['basedir'] . '/' . $filename;
    }

    file_put_contents($file, $image_data);

    $wp_filetype = wp_check_filetype($filename, null);
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => sanitize_file_name($filename),
        'post_content' => '',
        'post_status' => 'inherit',
    );

    $attach_id = wp_insert_attachment($attachment, $file, $post_id);

    require_once ABSPATH . 'wp-admin/includes/image.php';

    $attach_data = wp_generate_attachment_metadata($attach_id, $file);
    wp_update_attachment_metadata($attach_id, $attach_data);

    set_post_thumbnail($post_id, $attach_id);
}
function get_uploaded_csv_files() {
    $upload_dir = wp_upload_dir();
    $csv_files = array();

    $csv_files_path = $upload_dir['basedir'] . '/csv';
    if (file_exists($csv_files_path) && is_dir($csv_files_path)) {
        $files = scandir($csv_files_path);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'csv') {
                $csv_files[] = array(
                    'name' => $file,
                    'url' => $csv_files_path . '/' . $file
                );
            }
        }
    }
    return $csv_files;
}
?>