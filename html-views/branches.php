<div class="branch-manager branch-manager-branches">

    <div class="bubble bubble-welcome">
        <div class="limited-width">
            <h1 class="branch-manager-header">All Branches</h1>
            <p>Here you can view all branches currently active on your site as well as delete a branch.  Deleting a branch is PERMANENT and cannot be undone!</p>
            <p>You cannot delete the <em>main</em> branch or the currently selected branch.</p>
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
        else if($status_code == "branch-no-exist") {
            echo '<div class="bubble message message-error"><strong>Error:</strong> Cannot delete branch because the branch does not exist.</div>';
        }
        else if($status_code == "confirmation-fail") {
            echo '<div class="bubble message message-error"><strong>Error:</strong> You must check the <em>Confirm Delete</em> checkbox to delete the branch.</div>';
        }
        else if($status_code == "is-main") {
            echo '<div class="bubble message message-error"><strong>Error:</strong> You cannot delete the <em>main</em> branch.</div>';
        }
        else if($status_code == "is-current") {
            echo '<div class="bubble message message-error"><strong>Error:</strong> You cannot delete the currently active branch.  Please switch to another branch, then try deleting this branch again.</div>';
        }
        else if($status_code == "delete-fail") {
            echo '<div class="bubble message message-error"><strong>Error:</strong> A database error occurred while attempting to delete your branch.  Please check your error logs for more details.</div>';
        }
        else if($status_code == "branch-delete-ok") {
            echo '<div class="bubble message message-success"><strong>Success:</strong> Your branch has been deleted successfully!</div>';
        }
    }

    ?>

    <div class="bubble bubble-branches">

        <div class="branches-table-outer">

            <div class="branches-table-inner">

                <div class="row header-row">
                    <div class="column">Branch Name</div>
                    <div class="column">Last Commit Date</div>
                    <div class="column non-essential">Last Commit Message</div>
                    <div class="column">Switch</div>
                    <div class="column">Delete</div>
                </div>

                <?php

                    $counter = 0;

                    foreach($all_branches as $avail_branch) {

                        $form_html = '';

                        if($avail_branch->name != $branch) {
                            $form_html = '
                            <form method="POST" action="'. esc_url( admin_url('admin-post.php') ) . '">
                                '. wp_nonce_field( 'switch_branch', 'branchmanager' ) .'
                                <input type="hidden" name="action" value="branchmanager_bswitch_form" />
                                <input type="hidden" name="branch" value="'. $avail_branch->name .'" />
                                <button type="submit" class="submit switch">Switch</button>
                            </form>';
                        }

                        $delete_form_html = "";

                        if($avail_branch->name != "main" && $avail_branch->name != $branch) {
                            $delete_form_html = '
                            <form method="POST" action="'. esc_url( admin_url('admin-post.php') ) . '">
                                '. wp_nonce_field( 'delete_branch', 'branchmanager' ) .'
                                <input type="hidden" name="action" value="branchmanager_bdelete_form" />
                                <input type="hidden" name="branch" value="'. $avail_branch->name .'" />
                                
                                <div class="delete-flex-container">
                                    <div class="are-you-sure">
                                        <input type="checkbox" id="confirmation-'.$counter.'" name="confirmation" value="confirm" class="checkbox" />
                                        <label for="confirmation-'.$counter.'">Confirm Delete</label>
                                    </div>
                                    <button type="submit" class="submit delete">Delete</button>
                                </div>

                            </form>';
                        }

                        echo '
                        <div class="row '. ($avail_branch->name == $branch ? 'current' : 'not-current') .'">
                            <div class="column">' . $avail_branch->name .'</div>
                            <div class="column">' . $avail_branch->latest_commit_date .'</div>
                            <div class="column non-essential">' . $avail_branch->latest_commit_message . '</div>
                            <div class="column">' . $form_html . '</div>
                            <div class="column column-delete">' . $delete_form_html . '</div>
                        </div>';

                        $counter++;

                    }

                ?>

            </div>

        </div>

    </div>

</div>