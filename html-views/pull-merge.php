<div class="branch-manager branch-manager-pull-merge">
    <div class="bubble bubble-welcome">
        <h1 class="branch-manager-header">Merge Branch</h1>
        <p>This interface allows you to merge content from another branch into your current branch, <em><?php echo $branch; ?></em>.</p>
    </div>

    <?php if($is_merging === false && $is_resolving_conflicts === false && (!isset($_GET['status_code']) || $_GET['status_code'] != "merge-ok")) { ?>

        <div class="bubble bubble-status message <?php echo (is_array($status) ? 'message-error' : 'message-success'); ?>">

            <h2 class="branch-manager-header">Branch Status</h2>

            <?php if(is_array($status)) { ?>

                <p>Your branch is currently <strong>dirty</strong> because it has changes in it that have not been committed.  It is required that you commit any changes in your branch before continuing with a merge.</p>

            <?php 
                }
                else {
            ?>

                <p>Your branch is currently <strong>clean</strong> and you can merge changes into it from another branch.</p>

            <?php } ?>
        </div>

    <?php } ?>

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
            else if($status_code == "branch-commit-fail") {
                echo '<div class="bubble message message-error"><strong>Error:</strong> Committing your branch failed.  Please check your error logs for more details as to why committing the branch failed.</div>';
            }
            else if($status_code == "branch-unresolved-merge") {
                echo '<div class="bubble message message-error"><strong>Error:</strong> This operation cannot be completed because a merge attempt failed due to conflicts.  Further changes to this branch are not allowed while the branch is in a conflicted state.</div>';
            }
            else if($status_code == "merge-fail" && $last_message != "") {
                echo '<div class="bubble message message-error"><strong>Error:</strong> Merging your branch failed.  The last message received from the database was: '. sanitize_text_field($last_message) .'</div>';
            }
            else if($status_code == "merge-fail") {
                echo '<div class="bubble message message-error"><strong>Error:</strong> Merging your branch failed.</div>';
            }
            else if($status_code == "merge-ok") {
                echo '<div class="bubble message message-success"><strong>Success:</strong> Your merge was completed successfully!</div>';
            }
            else if($status_code == "conflicts" && $is_resolving_conflicts != true) {
                echo '<div class="bubble message message-error"><strong>Error:</strong> Merging your branch failed due to conflicts.  Please see below for options for resolving the issue.</div>';
            }
            else if($status_code == "failed-to-confirm") {
                echo '<div class="bubble message message-error"><strong>Error:</strong> You must confirm that you wish to resolve the conflicts using the provided checkbox.</div>';
            }
            else if($status_code == "unresolvable-conflicts") {
                echo '<div class="bubble message message-error"><strong>Error:</strong> One or more of the conflicts in your merge are unresolvable using this interface.  You must complete the merge manually using your database.</div>';
            }
            else if($status_code == "could-not-resolve") {
                echo '<div class="bubble message message-error"><strong>Error:</strong> Resolving conflicts failed!  You must complete the merge manually using your database.</div>';
            }
        }

        if($is_merging === true) {
            echo '<div class="bubble message message-error"><strong>Error:</strong> A merge operation is currently in progress.</div>';
        }

        if($is_resolving_conflicts === true) {
            echo '<div class="bubble message message-error"><strong>Error:</strong> One or more conflicts were detected with your merge.  You must choose how to resolve them below.</div>';
        }

    ?>

    <?php if($is_merging !== true && $is_resolving_conflicts != true) { ?>

        <div class="bubble bubble-merge">
            <h2 class="branch-manager-header">Merge Into Branch</h2>
            <p class="instructions">Choose a branch to merge into your current branch, <em><?php echo $branch; ?></em>.</p>

            <div class="merge-selection-interface">
                <form method="POST" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                    <?php echo wp_nonce_field( 'merge_into_branch', 'branchmanager'); ?>
                    <input type="hidden" name="action" value="branchmanager_mergeinto_form" />
                    
                    <div class="flex-branch-selection-screen">
                        <div class="the-current-branch">
                            <span class="current-branch"><?php echo $branch; ?></span>
                        </div>
                        <div class="the-arrow"><span class="dashicons dashicons-arrow-left-alt"></span></div>
                        <div class="the-merge-branch">
                            <select name="merge_branch" id="merge_branch">
                                <option value="">Select a Branch...</option>
                                
                                <?php

                                    foreach($all_branches as $all_branch) {
                                        if($all_branch->name != $branch) {
                                            echo '<option value="'. $all_branch->name .'">' . $all_branch->name . '</option>';
                                        }
                                    }

                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="autocommit-checkbox">
                        <input type="checkbox" id="autocommit" name="autocommit" value="commit" />
                        <label for="autocommit" class="autocommit-label">Commit any changes to the <?php echo $branch; ?> that may have occurred since your last commit before attempting the merge.</label>
                    </div>

                    <div class="branch-selection-submit">
                        <button type="submit" class="submit">Merge</button>
                    </div>
                </form>
            </div>
        </div>

    <?php } ?>  
    
    <?php if($is_resolving_conflicts == true) { ?>

        <div class="bubble bubble-conflicts">

            <h2 class="branch-manager-header">Resolve Conflicts</h2>
            <p class="instructions">You must resolve conflicts to merge this branch.</p>

            <?php if(boolval($conflicts['resolvable']) === true) { ?>

            <div class="conflicts-display">
                <form method="POST" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                    <?php echo wp_nonce_field( 'merge_into_branch', 'branchmanager'); ?>
                    <input type="hidden" name="action" value="branchmanager_mergeinto_form" />
                    <input type="hidden" name="mode" value="conflict-resolution" />
                    <input type="hidden" name="merge_branch" value="<?php echo $conflicts['branch_to_merge']; ?>" />

                    <?php if(isset($conflicts["dolt_conflicts"])) { ?>

                        <div class="conflicts-listing data-conflicts">
                            <h3 class="branch-manager-header">Data Conflicts</h3>
                            <p>These conflicts occur with the data, where a value may be different in the branch compared to the main.</p>

                            <div class="resolve-conflicts-table table-data-conflicts">

                                <div class="conflict-row header-row">
                                    <div class="column">Table</div>
                                    <div class="column conflicts">Conflicts</div>
                                    <div class="column action">Action</div>
                                </div>

                                <?php

                                    foreach($conflicts["dolt_conflicts"]["low_level"] as $table => $conflict_group) {

                                        $counter = 1;

                                        foreach($conflict_group as $conflict) {

                                            $mine_and_theirs    = \BranchManager\BranchManager::separateBaseOursTheirs($conflict);
                                            $diff               = \BranchManager\BranchManager::showDiff($mine_and_theirs);

                                            $action = '
                                                <select name="'.$conflict['dolt_conflict_id'].'" id="conflict_data_'.$table.'_'.$counter.'">
                                                    <option value="">Choose Action...</option>
                                                    <option value="mine">Keep Mine</option>
                                                    <option value="theirs">Keep Theirs</option>
                                                </select>
                                            ';

                                            $conflicts_html = '
                                            <div class="conflict-html">';

                                            foreach($diff as $key => $value) {

                                                $my_key = $key = array_keys($value)[0];
                                                $their_key = $key = array_keys($value)[1];

                                                $conflicts_html .= '
                                                    <div class="conflict-split">
                                                        <div class="conflict-split-row header-row">
                                                            <div class="column">Mine ('.$branch.')</div>
                                                            <div class="column">Theirs ('.$conflicts['branch_to_merge'].')</div>
                                                        </div>
                                                        <div class="conflict-split-row">
                                                            <div class="column mine"><p class="key"><strong>Table Column:</strong> '. str_replace("our_", "", $my_key) .'</p><div class="value"><textarea disabled="disabled">'. $value[$my_key] .'</textarea></div></div>
                                                            <div class="column theirs"><p class="key"><strong>Table Column:</strong> '. str_replace("our_", "", $my_key) .'</p><div class="value"><textarea disabled="disabled">'. $value[$their_key] .'</textarea></div></div>
                                                        </div>
                                                    </div>
                                                ';

                                            }

                                            $conflicts_html = $conflicts_html . '</div>';

                                            echo '
                                            <div class="conflict-row">
                                                <div class="column">'. $table .'</div>
                                                <div class="column results conflicts">'. $conflicts_html .'</div>
                                                <div class="column action">'.$action.'</div>
                                            </div>';

                                            $counter++;
                                        }

                                    }

                                ?>

                            </div>
                        </div>

                    <?php } ?>

                    <?php if(isset($conflicts["dolt_schema_conflicts"])) { ?>

                        

                    <?php } ?>

                    <?php if(isset($conflicts["constraint_violations"])) { ?>

                        <div class="conflicts-listing data-conflicts">
                            <h3 class="branch-manager-header">Constraint Violation Conflicts</h3>
                            <p>These conflicts occur due to issues with database constraints, such as primary and foreign keys.</p>
                        </div>

                        <div class="resolve-conflicts-table table-constraint-violations">

                            <div class="conflict-row header-row">
                                <div class="column">Table</div>
                                <div class="column">Violation Type</div>
                                <div class="column">Violation Info</div>
                                <div class="column json">Raw JSON</div>
                                <div class="column action">Action</div>
                            </div>

                            <?php

                                foreach($conflicts["constraint_violations"]["low_level"] as $table => $conflict_group) {

                                    $counter = 1;

                                    foreach($conflict_group as $conflict) {
                                        $action = '';

                                        if($conflict['violation_type'] == "unique index") {
                                            $action = '
                                                <input type="checkbox" id="conflict_constraint_'. $table .'_' . str_replace(" ", "", $conflict['violation_type']) . '_' . $counter .'" name="conflict_constraint_'. $table .'_' . str_replace(" ", "", $conflict['violation_type']) . '_' . $counter .'" value="confirmdelete" />
                                                <label for="conflict_constraint_'. $table .'_' . str_replace(" ", "", $conflict['violation_type']) . '_' . $counter .'">Delete</label>
                                            ';
                                        }

                                        $violation_info_html = "";
                                        $violation_info = json_decode($conflict['violation_info'], true);

                                        foreach($violation_info as $key => $value) {
                                            if(is_array($value)) {
                                                $violation_info_html .= "<p><strong>".$key.": </strong>";

                                                $temp_html = "";

                                                foreach($value as $temp_value) {
                                                    $temp_html .= $temp_value . ",";
                                                }

                                                $violation_info_html .= substr($temp_html, 0, -1) . "</p>";
                                            }
                                            else {
                                                $violation_info_html .= "<p><strong>$key:</strong> $value</p>";
                                            }
                                        }

                                        echo '
                                        <div class="conflict-row">
                                            <div class="column">'. $table .'</div>
                                            <div class="column">'. $conflict['violation_type'] .'</div>
                                            <div class="column violation-info">'. $violation_info_html .'</div>
                                            <div class="column json"><textarea>'. json_encode($conflict) .'</textarea></div>
                                            <div class="column action">'.$action.'</div>
                                        </div>';

                                        $counter++;
                                    }

                                }

                            ?>

                        </div>

                    <?php } ?>

                    <div class="autocommit-checkbox">
                        <input type="checkbox" id="autocommit" name="autocommit" value="commit" />
                        <label for="autocommit" class="autocommit-label">Commit any changes to the <em><?php echo $branch; ?></em> branch that may have occurred since your last commit before attempting the merge.</label>
                    </div>

                    <div class="autocommit-checkbox">
                        <input type="checkbox" id="confirmresolution" name="confirmresolution" value="confirmed" />
                        <label for="confirmresolution" class="confirmresolution">I have resolved all of the conflicts presented above</label>
                    </div>

                    <div class="branch-selection-submit">
                        <button type="submit" class="submit">Resolve Conflicts and Merge</button>
                    </div>
            </div>

            <?php 
            
                }
                else {
                ?>

                    <div class="conflicts-display unresolvable">
                        <p>The conflicts you have encountered are not resolvable via this interface and instead must be manually resolved.</p> 
                    </div>

                <?php
                }
            
            ?>

        </div>

    <?php } ?>
</div>