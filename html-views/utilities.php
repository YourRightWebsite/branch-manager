<div class="branch-manager branch-manager-utilities">

    <div class="bubble bubble-welcome">
        <div class="limited-width">
            <h1 class="branch-manager-header">Version Control Utilities</h1>
            <p>
                These utilities are present only because this is a development build of this plugin.  These utilities will eventually be removed.
                These utilities may not actually do anything depending on when they are called, so it is recommended you leave these alone unless you know what you're doing.
            </p>
            <p>Choose an action to take.</p>
        </div>

        <div class="utilities-form">
            <form method="POST" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                <?php echo wp_nonce_field( 'run_utility', 'branchmanager'); ?>
                <input type="hidden" name="action" value="branchmanager_utility_form" />

                <div class="select-utility-wrapper">
                    <select name="utility_to_run" id="utility_to_run">
                        <option value="">Choose Utility...</option>
                        <option value="rollback">Rollback Merge</option>
                        <option value="abort">Abort Merge</option>
                        <option value="reset_soft">Soft Reset</option>
                        <option value="reset_hard">Hard Reset</option>
                        <option value="force_commit">Force Commit</option>
                        <option value="clear">Clear Errors</option>
                    </select>
                </div>

                <button type="submit" class="submit">Run Utility</button>

            </form>
        </div>
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
            else if($status_code == "no-choice" || $status_code == "invalid-choice") {
                echo '<div class="bubble message message-error"><strong>Error:</strong> You must select a valid utility to run.</div>';
            }
            else if($status_code == "all-ok") {
                echo '<div class="bubble message message-success"><strong>Success:</strong> Utility ran successfully!</div>';
            }
        }

    ?>
</div>