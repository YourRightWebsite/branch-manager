<div class="branch-manager branch-manager-settings">

    <div class="bubble bubble-welcome">
        <h1 class="branch-manager-header">Version Control Settings</h1>
        <p>Configure the settings for this plugin.</p>
    </div>

    <?php

        if(isset($_GET['status_code']) && $_GET['status_code'] != "") {
            $status_code = sanitize_text_field($_GET['status_code']);

            if($status_code == "nonce-invalid") {
                echo '<div class="bubble message message-error"><strong>Error:</strong> Invalid nonce.  Please try logging out of WordPress and log in again, then try your action again.</div>';
            }
            else if($status_code == "nonce-empty") {
                echo '<div class="bubble message message-error"><strong>Error:</strong> Empty nonce.  Please try logging out of WordPress and log in again, then try your action again.</div>';
            }
            else if($status_code == "empty-location") {
                echo '<div class="bubble message message-error"><strong>Error:</strong> You must specify a directory where we can store the temporary files.</div>';
            }
            else if($status_code == "location-not-writable") {
                echo '<div class="bubble message message-error"><strong>Error:</strong> The location you provided is either not a directory or is not writeable.</div>';
            }
            else if($status_code == "all-ok") {
                echo '<div class="bubble message message-success"><strong>Success:</strong> Settings data updated successfully!</div>';
            }
        }

    ?>

    <div class="bubble bubble-writable-location">
        
        <div class="limited-width">
            <h2 class="branch-manager-header">Conflict Resolution Writable Directory</h2>
            <p>
                When a conflict occurs when merging branches, this plugin will need to store a temporary data file containing the user's choices of how to resolve the conflict.
                This data cannot be stored in the database, since the database is in use during the branch merge process or when a merge is attempted.
                Therefore, the data that determines how to resolve a conflict must be stored as a file on the server.
            </p>

            <p>Specify a writable directory, preferably outside of your web root, where this plugin can save temporary conflict resolution files.</p>
        </div>

        <form method="POST" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
            <?php echo wp_nonce_field( 'save_writable_location', 'branchmanager'); ?>
            <input type="hidden" name="action" value="branchmanager_save_writable_location_form" />

            <div class="field-with-label">
                <label for="writable-location">Writable Directory Location:</label>
                <input type="text" required="required" name="writable-location" value="<?php echo $writable_file_location; ?>" />
            </div>

            <button type="submit" class="submit">Save File Location</button>

        </form>
    </div>
</div>