<div class="branch-manager branch-manager-dashboard">

    <div class="bubble welcome">
        <h1 class="branch-manager-header">Branch Manager Version Control Dashboard</h1>
        <p>Branch Manager Version Control Plugin by <a href="https://yourrightwebsite.com?referrer=branch-manager" target="_blank">Your Right Website</a>.</p>
    </div>

    <?php

        if(isset($_GET['status_code']) && $_GET['status_code'] != "") {
            $status_code = sanitize_text_field($_GET['status_code']);

            if($status_code == "branch-empty") {
                echo '<div class="bubble message message-error"><strong>Error:</strong> No branch was specified.</div>';
            }
            else if($status_code == "branch-invalid") {
                echo '<div class="bubble message message-error"><strong>Error:</strong> The specified branch does not exist.</div>';
            }
            else if($status_code == "nonce-invalid") {
                echo '<div class="bubble message message-error"><strong>Error:</strong> Invalid nonce.  Please try logging out of WordPress and log in again, then try your action again.</div>';
            }
            else if($status_code == "nonce-empty") {
                echo '<div class="bubble message message-error"><strong>Error:</strong> Empty nonce.  Please try logging out of WordPress and log in again, then try your action again.</div>';
            }
            else if($status_code == "branch-switch-ok") {
                echo '<div class="bubble message message-success"><strong>Success!</strong> Your branch has been switched successfully.</div>';
            }
            else if($status_code == "branch-exists") {
                echo '<div class="bubble message message-error"><strong>Error:</strong> The branch you are trying to create already exists.  Please switch to it using the interface on the right.</div>';
            }
            else if($status_code == "branch-invalid-chars") {
                echo '<div class="bubble message message-error"><strong>Error:</strong> The branch name you are trying to use is invalid.  Branch names may only contain letters, numbers and dashes.</div>';
            }
            else if($status_code == "branch-create-failed") {
                echo '<div class="bubble message message-error"><strong>Error:</strong> Creating the branch failed.  Please check your error logs for more details as to why creating the branch failed.</div>';
            }
            else if($status_code == "branch-create-success") {
                echo '<div class="bubble message message-success"><strong>Success!</strong> Your branch has been created successfully and you have been switched to your new branch.</div>';
            }
            else if($status_code == "branch-commit-success") {
                echo '<div class="bubble message message-success"><strong>Success!</strong> Your branch has been committed successfully!</div>';
            }
            else if($status_code == "branch-commit-fail") {
                echo '<div class="bubble message message-error"><strong>Error:</strong> Committing your branch failed.  Please check your error logs for more details as to why committing the branch failed.</div>';
            }
            else if($status_code == "branch-unresolved-merge") {
                echo '<div class="bubble message message-error"><strong>Error:</strong> This operation cannot be completed because a merge attempt failed due to conflicts.  Further changes to this branch are not allowed while the branch is in a conflicted state.</div>';
            }
        }

    ?>

    <div class="bubble current-branch-display">
        <div class="current-branch-info">
            <h2 class="branch-manager-header">Current Branch:</h2>
            <p class="current-branch"><?php echo $branch; ?></p>
        </div>
    </div>

    <div class="bubble-grid prioritize-right">

        <div class="bubble current-branch">

            <div class="new-branch-create">
                <h2 class="branch-manager-header">Quickly Create New Branch:</h2>
                <p>Use the form below to create a new branch off of the latest in <em>main</em>.  Branch names may only contain letters, numbers and the dash character.</p>

                <div class="new-branch-form-wrapper">
                    <form method="POST" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                        <?php echo wp_nonce_field( 'create_branch', 'branchmanager'); ?>
                        <input type="hidden" name="action" value="branchmanager_bcreate_form" />
                        
                        <div class="branch-form-flex is-flex no-flex-1440">
                            <input type="text" name="branch" value="" required="required" aria-label="Branch Name" placeholder="branch-name" />
                            <button type="submit" class="submit switch">Create Branch</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="bubble available-branches">
            
            <div class="intro">
                <h2 class="branch-manager-header">Available Branches:</h2>
                <p>
                    This interface lists all of the branches currently available to you on your Dolt database.  
                    You can switch to a branch using the interface below.
                </p>
            </div>

            <div class="branches-listing">

                <div class="branches-listing-threecol">

                    <div class="row header-row">
                        <div class="column branch-name">Branch Name</div>
                        <div class="column branch-last-commit">Last Commit Date</div>
                        <div class="column branch-last-commit">Last Commit Message</div>
                        <div class="column branch-switch">Switch</div>
                    </div>

                    <?php

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

                            echo '
                            <div class="row '. ($avail_branch->name == $branch ? 'current' : 'not-current') .'">
                                <div class="column">' . $avail_branch->name .'</div>
                                <div class="column">' . $avail_branch->latest_commit_date .'</div>
                                <div class="column">' . $avail_branch->latest_commit_message . '</div>
                                <div class="column">' . $form_html . '</div>
                            </div>';

                        }

                    ?>

                </div>

            </div>
        </div>

    </div>

    <div class="bubble-grid prioritize-right">

        <div class="bubble quick-commit">

                <h2 class="branch-manager-header">Quick Commit:</h2>
                <p>Quickly commit all changes to the <em><?php echo $branch; ?></em> branch.  The form below will auto-stage any changes on the <em><?php echo $branch; ?></em> branch and commit them back into the branch.</p>

                <div class="new-branch-form-wrapper">
                    <form method="POST" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                        <?php echo wp_nonce_field( 'commit_branch', 'branchmanager'); ?>
                        <input type="hidden" name="action" value="branchmanager_bcommit_form" />
                        
                        <div class="commit-branch-form">
                            <textarea name="commit_message" value="" required="required" aria-label="Commit Message" placeholder="Commit Message"></textarea>
                            <button type="submit" class="submit switch">Commit Changes</button>
                        </div>
                    </form>
                </div>

        </div>
        <div class="bubble branch-status">

            <h2 class="branch-manager-header">Status Report:</h2>
            <p>This table shows you the tables that have been modified inside of your current branch.  As you make changes to your branch, you'll see the changes to the tables reflected in the table below.</p>

            <div class="branch-table-changes-status">
                <?php echo $status_html; ?>
            </div>

        </div>

    </div>

</div>