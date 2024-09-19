<div class="branch-manager branch-manager-history">

    <div class="bubble bubble-welcome">
        <div class="limited-width">
            <h1 class="branch-manager-header">Commit History</h1>
            <p>Showing the commit history for the <em><?php echo $branch; ?></em> branch.</p>
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
        else if($status_code == "all-ok") {
            echo '<div class="bubble message message-success"><strong>Success:</strong> Settings data updated successfully!</div>';
        }
    }

    ?>

    <div class="bubble bubble-history">

        <div class="commit-history-table-outer">

            <?php if($commit_history["num_pages"] > 1) { ?>

                <div class="pagination">
                    <div class="pagination-item back">
                        <?php

                            if($commit_history["current_page"] > 1) {
                                echo '<a class="pagination-link" href="' . get_admin_url() . 'admin.php?page=branch-manager-history&page_no='. ($commit_history["current_page"] - 1).'">Newer Commits</a>';
                            }
                            else {
                                echo '<button class="pagination-link disabled">Newer Commits</button>';
                            }

                        ?>
                    </div>
                    <div class="pagination-item forward">
                        <?php

                            if(($commit_history["current_page"] + 1) <= $commit_history["num_pages"]) {
                                echo '<a class="pagination-link" href="' . get_admin_url() . 'admin.php?page=branch-manager-history&page_no='. ($commit_history["current_page"] + 1).'">Older Commits</a>';
                            }
                            else {
                                echo '<button class="pagination-link disabled">Older Commits</button>';
                            }

                        ?>
                    </div>
                </div>

            <?php } ?>

            <div class="commit-history-table-inner">

                <div class="row header-row">
                    <div class="column">Committer</div>
                    <div class="column non-essential">Email</div>
                    <div class="column">Date</div>
                    <div class="column">Message</div>
                </div>

                <?php

                    foreach($commit_history['results'] as $result) {
                       
                        echo '
                        <div class="row">
                            <div class="column">'. sanitize_text_field($result->committer) .'</div>
                            <div class="column non-essential">'. sanitize_text_field($result->email) .'</div>
                            <div class="column">'. sanitize_text_field($result->date) .'</div>
                            <div class="column">'. sanitize_text_field($result->message) .'</div>
                        </div>
                        ';

                    }

                ?>

            </div>

            <?php if($commit_history["num_pages"] > 1) { ?>

            <div class="pagination">
                <div class="pagination-item back">
                    <?php

                        if($commit_history["current_page"] > 1) {
                            echo '<a class="pagination-link" href="' . get_admin_url() . 'admin.php?page=branch-manager-history&page_no='. ($commit_history["current_page"] - 1).'">Newer Commits</a>';
                        }
                        else {
                            echo '<button class="pagination-link disabled">Newer Commits</button>';
                        }

                    ?>
                </div>
                <div class="pagination-item forward">
                    <?php

                        if(($commit_history["current_page"] + 1) <= $commit_history["num_pages"]) {
                            echo '<a class="pagination-link" href="' . get_admin_url() . 'admin.php?page=branch-manager-history&page_no='. ($commit_history["current_page"] + 1).'">Older Commits</a>';
                        }
                        else {
                            echo '<button class="pagination-link disabled">Older Commits</button>';
                        }

                    ?>
                </div>
            </div>

            <?php } ?>


        </div>

    </div>

</div>