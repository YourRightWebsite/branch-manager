<?php

namespace BranchManager;

/*
Plugin Name: Branch Manager Version Control
Description: Version control for WordPress databases using Dolt.
Version: 1.0.0
Author: Your Right Website
Author URI: https://www.yourrightwebsite.com
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Require Composer's autoload file
require_once(plugin_dir_path(__FILE__) . "vendor/autoload.php");

// Add any of our namespaced child classes that we wish to use

class BranchManager {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'createAdminMenu']);
        add_action('plugins_loaded', [$this, 'checkForBranchSwitch']);
        add_action('admin_enqueue_scripts', [$this, 'loadScriptsAndStyles']);
        add_action('admin_post_branchmanager_bswitch_form', [$this, 'handleSwitchBranchForm']);
        add_action('admin_post_branchmanager_bcreate_form', [$this, 'handleCreateBranchForm'] );
        add_action('admin_post_branchmanager_bcommit_form', [$this, 'handleCommitBranchForm'] );
        add_action('admin_post_branchmanager_mergeinto_form', [$this, 'handleMergeBranchForm'] );
        add_action('admin_post_branchmanager_bdelete_form', [$this, 'handleDeleteBranchForm']);
        add_action('admin_post_branchmanager_utility_form', [$this, 'handleRunUtilityForm'] );
        add_action('admin_post_branchmanager_save_writable_location_form', [$this, 'handleSaveWritableLocationForm'] );
    }

    public function createAdminMenu() {
        add_menu_page( 'Version Control', 'Version Control', 'manage_options', 'branch-manager', function() {
            $this->displayAdminMenu();
        });

        add_submenu_page('branch-manager', 'Pull and Merge', 'Pull and Merge', 'manage_options', 'branch-manager-merge', function() {
            $this->displayMergeInterface();
        });

        add_submenu_page('branch-manager', 'Manage Branches', 'Manage Branches', 'manage_options', 'branch-manager-branches', function() {
            $this->displayBranchesInterface();
        });

        add_submenu_page('branch-manager', 'Commit History', 'Commit History', 'manage_options', 'branch-manager-history', function() {
            $this->displayCommitHistory();
        });

        add_submenu_page('branch-manager', 'Version Control Settings', 'Settings', 'manage_options', 'branch-manager-settings', function() {
            $this->displaySettingsInterface();
        });

        add_submenu_page('branch-manager', 'Utilities', 'Utilities', 'manage_options', 'branch-manager-utilities', function() {
            $this->displayUtilitiesInterface();
        });
    }

    public static function checkPermissions() {
        if ( !current_user_can( 'manage_options') )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
    }

    public static function loadScriptsAndStyles() {
        if(is_admin()) {

            $allowed_pages = [];
            $allowed_pages[] = "branch-manager";
            $allowed_pages[] = "branch-manager-branches";
            $allowed_pages[] = "branch-manager-merge";
            $allowed_pages[] = "branch-manager-utilities";
            $allowed_pages[] = "branch-manager-settings";
            $allowed_pages[] = "branch-manager-history";

            if (isset($_GET['page']) && in_array($_GET['page'], $allowed_pages)) {
                wp_enqueue_style('branchmanager-admin-styles', plugin_dir_url(__FILE__) . 'assets/css/global.css', [], null);

                if($_GET['page'] == "branch-manager") {
                    wp_enqueue_style('branchmanager-admin-styles-dashboard', plugin_dir_url(__FILE__) . 'assets/css/dashboard.css', [], null);
                }

                if($_GET['page'] == "branch-manager-branches") {
                    wp_enqueue_style('branchmanager-admin-styles-branches', plugin_dir_url(__FILE__) . 'assets/css/branches.css', [], null);
                }

                if($_GET['page'] == "branch-manager-merge") {
                    wp_enqueue_style('branchmanager-admin-styles-merge', plugin_dir_url(__FILE__) . 'assets/css/pull-merge.css', [], null);
                }

                if($_GET['page'] == "branch-manager-utilities") {
                    wp_enqueue_style('branchmanager-admin-styles-utilities', plugin_dir_url(__FILE__) . 'assets/css/utilities.css', [], null);
                }

                if($_GET['page'] == "branch-manager-settings") {
                    wp_enqueue_style('branchmanager-admin-styles-settings', plugin_dir_url(__FILE__) . 'assets/css/settings.css', [], null);
                }

                if($_GET['page'] == "branch-manager-history") {
                    wp_enqueue_style('branchmanager-admin-styles-history', plugin_dir_url(__FILE__) . 'assets/css/history.css', [], null);
                }
            }
        }
    }

    public static function displayAdminMenu() {
        self::checkPermissions();

        $branch         = self::getCurrentBranch();
        $all_branches   = self::listBranches(true);
        $status         = self::getStatus();
        $nonce          = wp_create_nonce('branchmanager');
        $status_html    = self::displayStatus($status);

        require_once(plugin_dir_path(__FILE__) . "html-views/dashboard.php");

    }

    public static function getCurrentBranch() {

        global $wpdb;

        // Get the current branch using Dolt SQL syntax
        $query = "SELECT active_branch() as branch";

        // Execute the query
        $results = $wpdb->get_results($query);

        if ($results) {
            
            if(isset($results[0]) && isset($results[0]->branch)) {
                return $results[0]->branch;
            }

        }
        
        return false;

    }

    public static function listBranches($all_data = false) {
        global $wpdb;

        // Get the current branch using Dolt SQL syntax
        $query = "SELECT * FROM dolt_branches;";

        // Execute the query
        $results = $wpdb->get_results($query);

        if ($results) {
            
            if($all_data === true) {
                return $results;
            }
            else {

                $branches = [];

                foreach($results as $result) {
                    $branches[] = $result->name;
                }

                return $branches;
            }

        }

        return false;
    }

    public static function switchBranchForRequest($new_branch) {

        // First check that the branch exists
        $branch_exists = self::checkBranchExists($new_branch);

        if($branch_exists === true) {
            global $wpdb;

            $query = $wpdb->prepare("CALL dolt_checkout('%s');", $new_branch);
            $wpdb->query($query);

            if ($wpdb->last_error == '') {
                return true;
            }
            else {
                return $wpdb->last_error;
            }
        }
        
        return false;

    }

    public static function checkBranchExists($new_branch) {

        $the_branches = self::listBranches();

        if(is_array($the_branches)) {
            if(in_array($new_branch, $the_branches)) {
                return true;
            }
        }

        return false;

    }

    public static function getStatus() {
        global $wpdb;

        // Get whether there are any staged or unstaged changes
        $query = "select * from dolt_status;";

        // Execute the query
        $results = $wpdb->get_results($query);

        if ($results) {
            
            return $results;

        }

        return false;
    }

    public static function displayStatus($status_results) {

        $html = '<!-- Status Results -->';

        if(is_array($status_results)) {
            $html .= '
            <div class="dolt-status-table">
                <div class="dolt-status-row header-row">
                <div class="column">Table Name:</div>
                <div class="column">Changes Staged:</div>
                <div class="column">Table Status:</div>
            </div>';

            foreach($status_results as $result) {

                $html .= '
                    <div class="dolt-status-row">
                        <div class="column">' . $result->table_name . '</div>
                        <div class="column">' . ($result->staged != 0 ? 'Yes' : 'No') . '</div>
                        <div class="column">' . $result->status . '</div>
                    </div>';

            }

            $html .= "<div>";
        }
        else {
            $html .= '<p class="no-changes">There are no changes to your database.</p>';
        }

        return $html;

    }

    /**
     * Resets the current branch to a clean state, deleting any 
     */
    public static function reset($mode = "soft") {
        global $wpdb;
        
        if($mode == "hard") {
            $query = $wpdb->query("CALL dolt_reset('--hard');");
        }
        else {
            $query = $wpdb->query("CALL dolt_reset('--soft');");
        }
    }

    public static function abort() {
        global $wpdb;
        $query = $wpdb->query("call dolt_merge('--abort');");
        $result = $wpdb->get_results($query);
    }

    public static function getUserInfo() {
        $current_user_object = wp_get_current_user();
        
        $user_data = [];
        $user_data["name"] = "WordPress";
        $user_data["email"] = "wordpress@example.com";

        if(isset($current_user_object->data->user_email)) {
            $user_data["name"] = $current_user_object->data->user_nicename;
            $user_data["email"] = $current_user_object->data->user_email;
        }

        return $user_data;
    }

    public static function getAuthorString() {
        $author_data = self::getUserInfo();
        return $author_data['name'] . " <". $author_data['email'] .">";
    }

    public static function commit($message="", $auto_stage=true) {

        self::checkPermissions();
        
        if(self::isMerging()) {
            return false;
        }

        global $wpdb;

        if($message == "") {
            $message = "No Commit Message Provided";
        }

        $author = self::getAuthorString();

        if($auto_stage === true) {
            $query = $wpdb->prepare("CALL dolt_commit('-A', '-m', '%s', '--author', '%s');", $message, $author);
        }
        else {
            $query = $wpdb->prepare("CALL dolt_commit('-m', '%s', '--author', '%s');", $message, $author);
        }
        
        $wpdb->query($query);

        if ($wpdb->last_error == '') {
            return true;
        }
        else {
            return $wpdb->last_error;
        }

        return false;

    }

    /**
     * Checks if there is a cookie set that is requesting a branch switch.
     * 
     * Returns true on branch switch, false on no branch switch or a WP DB error on database error.
     */
    public static function checkForBranchSwitch() {
        global $wpdb;
        
        if(isset($_COOKIE['branchmanager_branch']) && $_COOKIE['branchmanager_branch'] != "") {
            $new_branch = sanitize_text_field($_COOKIE['branchmanager_branch']);

            if(self::checkBranchExists($new_branch) === true) {
                return self::switchBranch($new_branch);
            }
            else {
                self::deleteBranchCookie();
            }
            
        }

        return false;
    }

    public static function deleteBranchCookie() {
        if(isset($_COOKIE['branchmanager_branch'])) {
            unset($_COOKIE['branchmanager_branch']);
            setcookie("branchmanager_branch", "", -1, "/", "", false, false);
        }
    }

    /**
     * Switches the branch of the Dolt database that WordPress is using for the duration of the request.
     */
    
    public static function switchBranch($branch="") {

        /*
            Since we're working with the database at a really low level here, we can't really do much for escaping directly via the DB.
            So, to be safe we only allow branch names that have letters, numbers and the dash character.
            We directly switch the database WordPress is using for the request to that of the branch if a branch change is requested.
        */

        $branch = sanitize_text_field($branch);
        $branch = preg_replace('/[^a-zA-Z0-9-]/', '', $branch);

        if($branch == "") {
            $branch = "main";
        }

        $branch_exists = self::checkBranchExists($branch);

        if($branch_exists === true) {

            global $wpdb;

            if($branch != "") {
                $wpdb->select(DB_NAME . '/' . $branch);

                if ($wpdb->last_error == '') {
                    return true;
                }
                else {
                    return $wpdb->last_error;
                }
            } 

        }

        return false;
    }

    /* Sets a cookie with the current branch */
    public static function setBranchCookie($branch="") {

        if($branch != "") {
            // Set or update the cookie
            setcookie("branchmanager_branch", $branch, 0, "/", "", false, false);
        }
        else {
            // Using main branch, so delete the cookie
            self::deleteBranchCookie();
        }

    }

    public static function handleSwitchBranchForm() {
        self::checkPermissions();

        $branch         = "";       // The new branch to switch to
        $status_code    = "";       // The status code to return to the dashboard view
        $fail           = true;     // Whether we are in a failure state

        if(isset($_POST['branchmanager'])) {
            if(wp_verify_nonce( $_POST['branchmanager'], 'switch_branch' )) {
                if(isset($_POST['branch'])) {
                    $branch = sanitize_text_field($_POST['branch']);
        
                    if($branch == "") {
                        $branch = "main";
                    }
        
                    if(self::checkBranchExists($branch) === true) {
                        self::setBranchCookie($branch);
                        $fail = false;
                    }
                    else {
                        $status_code = "branch-invalid";
                    }
                }
                else {
                    $status_code = "branch-empty";
                }
            }
            else {
                $status_code = 'nonce-invalid';
            }
        } 
        else {
            $status_code = 'nonce-empty';
        }
        
        if($fail === false) {
            $status_code = "branch-switch-ok";
        }

        wp_redirect(admin_url('/admin.php?page=branch-manager&status_code=' . $status_code), 301);
        exit;
    }

    public static function handleCreateBranchForm() {
        self::checkPermissions();

        $branch         = "";       // The new branch to switch to
        $status_code    = "";       // The status code to return to the dashboard view
        $fail           = true;     // Whether we are in a failure state

        if(isset($_POST['branchmanager'])) {
            if(wp_verify_nonce( $_POST['branchmanager'], 'create_branch' )) {
                if(isset($_POST['branch'])) {
                    $branch = sanitize_text_field($_POST['branch']);
        
                    if(self::checkBranchExists($branch) === true) {
                        $status_code = "branch-exists";
                    }
                    else if($branch != preg_replace('/[^a-zA-Z0-9-]/', '', $branch)) {
                        $status_code = "branch-invalid-chars";
                    }
                    else if(self::isMerging()) {
                        $status_code = "branch-unresolved-merge";
                    }
                    else {
                        $status = self::createBranch($branch);

                        if($status === true) {
                            self::setBranchCookie($branch);
                            $status_code = "branch-create-success";
                            $fail = false;
                        }
                        else {
                            $status_code = "branch-create-failed";
                        }
                    }

                }
                else {
                    $status_code = "branch-empty";
                }
            }
            else {
                $status_code = 'nonce-invalid';
            }
        } 
        else {
            $status_code = 'nonce-empty';
        }
        
        if($fail === false) {
            $status_code = "branch-switch-ok";
        }

        wp_redirect(admin_url('/admin.php?page=branch-manager&status_code=' . $status_code), 301);
        exit;
    }

    /**
     * Creates a new branch off of the main branch
     */
    public static function createBranch($new_branch) {

        self::checkPermissions();
        
        global $wpdb;

        $query = $wpdb->prepare("CALL dolt_branch('%s');", $new_branch);
        $wpdb->query($query);

        if ($wpdb->last_error == '') {
            return true;
        }
        else {
            return $wpdb->last_error;
        }

        return false;
    }

    public static function handleCommitBranchForm() {
        self::checkPermissions();

        $branch         = "";       // The new branch to switch to
        $status_code    = "";       // The status code to return to the dashboard view
        $fail           = true;     // Whether we are in a failure state

        if(isset($_POST['branchmanager'])) {
            if(wp_verify_nonce( $_POST['branchmanager'], 'commit_branch' )) {
                $message = "";

                if(isset($_POST['commit_message']) && $_POST['commit_message'] != "") {
                    $message = sanitize_text_field($_POST['commit_message']);
                }

                if(self::isMerging()) {
                    $status_code = "branch-unresolved-merge";
                }
                else {
                    $result = self::commit($message);

                    if($result === true) {
                        $fail = false;
                        $status_code = "branch-commit-success";
                    }
                    else {
                        $status_code = "branch-commit-fail";
                    }
                }                
            }
            else {
                $status_code = 'nonce-invalid';
            }
        } 
        else {
            $status_code = 'nonce-empty';
        }

        wp_redirect(admin_url('/admin.php?page=branch-manager&status_code=' . $status_code), 301);
        exit;
    }

    public function displayBranchesInterface() {
        self::checkPermissions();

        $branch         = self::getCurrentBranch();
        $all_branches   = self::listBranches(true);
        $status         = self::getStatus();
        $nonce          = wp_create_nonce('branchmanager');

        require_once(plugin_dir_path(__FILE__) . "html-views/branches.php");
    }

    public function displayMergeInterface() {
        self::checkPermissions();

        $branch                     = self::getCurrentBranch();
        $all_branches               = self::listBranches(true);
        $status                     = self::getStatus();
        $nonce                      = wp_create_nonce('branchmanager');
        $status_html                = self::displayStatus($status);
        $is_merging                 = self::isMerging();
        $is_resolving_conflicts     = false;
        $last_message               = self::getOption('branchmanager_last_message');
        $conflicts                  = [];

        $merging = self::isMerging();

        if($last_message == "") {
            $cookie_message = self::getLastErrorCookie();

            if($cookie_message != "") {
                $last_message = $cookie_message;
            }
        }

        if(isset($_GET['resolving-conflicts']) && $_GET['resolving-conflicts'] == "yes") {
            $is_resolving_conflicts = true;
            $conflicts = self::getConflictData();

            if($conflicts != "" && $conflicts !== false) {
                $conflicts = json_decode($conflicts, true);
            }
        }

        require_once(plugin_dir_path(__FILE__) . "html-views/pull-merge.php");
    }

    public static function isMerging() {

        // SELECT * FROM dolt_merge_status;
        // If is_merging column equals 1, then prevent new operations

        global $wpdb;

        // Get the current branch using Dolt SQL syntax
        $query = "SELECT * FROM dolt_merge_status;";

        // Execute the query
        $results = $wpdb->get_results($query);

        if ($results) {
            
            foreach($results as $result) {
                return boolval($result->is_merging);
            }

        }

        return false;

    }

    public static function rollback() {
        global $wpdb;
        $wpdb->query('ROLLBACK;');
    }

    public function handleMergeBranchForm() {
        self::checkPermissions();

        $branch_to_merge            = "";                           // The branch to merge into the current branch
        $current_branch             = self::getCurrentBranch();     // The current branch
        $autocommit                 = false;
        $status_code                = "";                           // The status code to return to the dashboard view
        $fail                       = true;                         // Whether we are in a failure state

        if(isset($_POST['branchmanager'])) {
            if(wp_verify_nonce( $_POST['branchmanager'], 'merge_into_branch' )) {

                if(isset($_POST['merge_branch']) && $_POST['merge_branch'] != "" && self::checkBranchExists($_POST['merge_branch']) === true) {
                    
                    $branch_to_merge = $_POST['merge_branch'];

                    $commit_message = "System commit before merge";

                    if(self::isMerging()) {
                        $status_code = "branch-unresolved-merge";
                    }

                    if(isset($_POST['autocommit']) && $_POST['autocommit'] == "commit") {
                        $autocommit = true;
                    }
                    
                    if($autocommit === true && is_array(self::getStatus())) {

                        // Do a commit, but only if there are uncommitted changes

                        $result = self::commit($commit_message);

                        if($result !== true) {
                            $status_code = "branch-commit-fail";
                        }
                    }

                    // Attempt the merge

                    if($status_code == "") {

                        if($status_code == "") {

                            if($autocommit === true && is_array(self::getStatus()) && isset($_POST['mode']) && $_POST['mode'] == "conflict-resolution") {
                                $result = self::commit("Pre Merge Commit");
                            }

                            $result = self::mergeBranch($branch_to_merge);

                            if($result === true) {
                                $fail = false;
                                $status_code = 'merge-success';
                            }
                            else {
                                
                                if(is_array($result)) {

                                    if(in_array("conflicts", $result)) {
                                        $status_code = "conflicts";
                                    }

                                    if(in_array("conflicts-unresolvable", $result)) {
                                        $status_code = "could-not-resolve";
                                    }

                                    if(in_array("failed-to-confirm", $result)) {
                                        $status_code = "failed-to-confirm";
                                    }

                                    if(in_array("conflicits-not-resolvable", $result)) {
                                        $status_code = "unresolvable-conflicts";
                                    }

                                    if(in_array("could-not-resolve-conflicts", $result)) {
                                        $status_code = "could-not-resolve";
                                    }

                                }

                                if($status_code == "") {
                                    $status_code = "merge-fail";
                                }

                            }
                        }
                    }

                }   
                else {
                    $status_code = 'branch-invalid';
                }  
            }
            else {
                $status_code = 'nonce-invalid';
            }
        } 
        else {
            $status_code = 'nonce-empty';
        }
        
        if($fail === false) {
            $status_code = "merge-ok";
        }

        if($status_code == "conflicts") {
            wp_redirect(admin_url('/admin.php?page=branch-manager-merge&status_code=' . $status_code . '&resolving-conflicts=yes'), 301);
        }
        else {
            wp_redirect(admin_url('/admin.php?page=branch-manager-merge&status_code=' . $status_code), 301);
        }
        
        exit;
    }

    public static function mergeBranch($branch_to_merge) {
        // https://docs.dolthub.com/sql-reference/version-control/merges

        $errors = [];

        global $wpdb;

        self::saveOption('branchmanager_last_message', "");
        self::deleteLastErrorCookie();

        $author = self::getAuthorString();

        $wpdb->query("SET autocommit = 0;");

        $query = $wpdb->prepare("CALL DOLT_MERGE('%s', '--author', '%s');", $branch_to_merge, self::getAuthorString());
        $result = $wpdb->get_results($query);

        if ($wpdb->last_error != '') {
            $errors[] = "database";

            $last_error = $wpdb->last_error;
            
            self::abort();
            self::saveOption('branchmanager_last_message', sanitize_text_field($last_error));
            self::setLastErrorCookie($last_error);

            $wpdb->query("SET autocommit = 1;");

            return $errors;
        }
        else {
            self::saveOption('branchmanager_last_message', '');
        }

        if(isset($result)) {

            if(isset($result[0])) {
                $result = $result[0];
            }

            if(isset($result->conflicts)) {
                if(boolval($result->conflicts) === true) {

                    /*
                        Check the post data and see if the user supplied a way to resolve the conflicts
                    */

                    if(isset($_POST['mode']) && $_POST['mode'] == "conflict-resolution") {

                        if(isset($_POST['confirmresolution']) && $_POST['confirmresolution'] == "confirmed") {

                            $old_conflicts = self::getConflictData();

                            if($old_conflicts === false) {
                                $errors[] = "invalid-conflicts-file";
                            }

                            $old_conflicts = json_decode($old_conflicts, true);

                            if(boolval($old_conflicts['resolvable']) !== true) {
                                $errors[] = "conflicits-not-resolvable";
                            }
                            
                            $conflict_resolve_result = self::resolveConflicts($old_conflicts);

                            if($conflict_resolve_result !== true) {
                                $errors[] = "could-not-resolve-conflicts";
                            }
                            else {
                                $query = $wpdb->prepare("CALL DOLT_COMMIT('-A', '-m', '%s', '--author', '%s');", "Merged branch " . $branch_to_merge . " into " . self::getCurrentBranch(), $author);
                                $result = $wpdb->get_results($query);

                                if(isset($result[0])) {
                                    $result = $result[0];

                                    if(isset($result->message)) {
                                        self::saveOption('branchmanager_last_message', sanitize_text_field($result->message));
                                        self::setLastErrorCookie($result->message);
                        
                                        if($result->message == "merge successful") {
                                            return true;
                                        }
                                    }
                                }
                            }

                        }
                        else {
                            $errors[] = "failed-to-confirm";
                        }

                    }
                    else {

                        $errors[] = "conflicts";

                        $query = $wpdb->prepare("CALL DOLT_MERGE('%s', '--author', '%s');", $branch_to_merge, self::getAuthorString());
                        $result = $wpdb->get_results($query);

                        $conflicts = self::checkForConflicts();
                        $conflicts["branch_to_merge"] = $branch_to_merge;
                        $conflicts_json = json_encode($conflicts);

                        $wpdb->query("COMMIT; SET autocommit = 1;");

                        self::saveConflictData($conflicts_json);

                    }

                }
            }

            if(isset($result->message)) {
                self::saveOption('branchmanager_last_message', sanitize_text_field($result->message));
                self::setLastErrorCookie($result->message);

                if($result->message == "merge successful") {
                    return true;
                }
            }

            $conflicts = self::checkForConflicts();

            if($conflicts === false and count($errors) === 0) {
                // $query = $wpdb->prepare("CALL DOLT_COMMIT('-Am', '%s');", "Merged branch " . $branch_to_merge . " into " . self::getCurrentBranch());
                $query = "COMMIT; SET autocommit = 1;";
                $wpdb->query($query); 

                return true;
            }

        }

        if(self::checkForConflicts() === false) {
            $query = "SET autocommit = 1;";
            $wpdb->query($query);
        }

        if(count($errors) > 0) {
            return $errors;
        }

        return false;
    }

    public static function checkForConflicts() {
        global $wpdb;

        $conflicts = [];

        // Data Conflicts

        $results = $wpdb->get_results("SELECT * FROM dolt_conflicts;");

        if(isset($results) AND is_array($results) AND count($results) > 0) {
    
            $data_conflicts = [];
            $data_conflicts["high_level"] = $results;
            
            $individual_conflicts = [];

            foreach($results as $result) {
                
                $table = "dolt_conflicts_" . $result->table;

                $table_query_results = $wpdb->get_results("SELECT * FROM " . $table);

                $individual_conflicts[$result->table] = $table_query_results;
            }

            $data_conflicts["low_level"] = $individual_conflicts;

            $conflicts["dolt_conflicts"] = $data_conflicts;

        }

        // Schema Conflicts

        $results = $wpdb->get_results("SELECT * FROM dolt_schema_conflicts;");

        if(isset($results) AND is_array($results) AND count($results) > 0) {
            $conflicts["dolt_schema_conflicts"] = $results;
        }

        // Constraint Violations

        $results = $wpdb->get_results("SELECT * from dolt_constraint_violations;");

        if(isset($results) AND is_array($results) AND count($results) > 0) {

            $constraint_conflicts = [];
            $constraint_conflicts["high_level"] = $results;
            
            $individual_conflicts = [];

            foreach($results as $result) {
                
                $table = "dolt_constraint_violations_" . $result->table;

                $table_query_results = $wpdb->get_results("SELECT * FROM " . $table);

                $individual_conflicts[$result->table] = $table_query_results;
            }

            $constraint_conflicts["low_level"] = $individual_conflicts;

            $conflicts["constraint_violations"] = $constraint_conflicts;
        }

        if(count($conflicts) > 0) {

            $resolvable = (self::checkIfConflictsUserResolvable($conflicts) === true ? 1 : 0);
            $conflicts['resolvable'] = $resolvable;

            return $conflicts;
        }

        return false;
    }

    /**
     * Checks if a set of conflicts is resolvable by the user in the admin interface.
     * There may be some conflicts that users must resolve manually with SQL queries.
     */
    public static function checkIfConflictsUserResolvable($conflicts) {

        // Normalize data since we're using objects in some places and arrays in others
        $conflicts = json_encode($conflicts);
        $conflicts = json_decode($conflicts, true);

        // Data Conflicts

        if(isset($conflicts["dolt_conflicts"])) {
            
            // Ensure that the conflicted table has something that looks like an ID
            // If there isn't an ID we won't be able to properly resolve the conflict

            $low_level = $conflicts["dolt_conflicts"]["low_level"];

            $at_least_one_id = false;

            foreach($low_level as $table => $conflicts) {
                foreach($conflicts as $conflict) {
                    foreach($conflict as $conflicted_field => $conflicted_value) {
                        $conflicted_field = strtolower($conflicted_field);

                        if($conflicted_field == "id" || substr($conflicted_field, -3) == "_id" || substr($conflicted_field, 0, 3) == "id_") {
                            $at_least_one_id = true;
                        }
                    }
                }
            }

            return $at_least_one_id;

        }

        // Schema Conflicts

        if(isset($conflicts["dolt_schema_conflicts"])) {
            return false;
        }

        // Constraint Violations

        if(isset($conflicts['constraint_violations'])) {
            $low_level_conflict_data = $conflicts['constraint_violations']['low_level'];

            $resolvable_types = array("unique index");

            foreach($low_level_conflict_data as $conflicted_table) {
                foreach($conflicted_table as $conflict) {
                    if(!in_array($conflict['violation_type'], $resolvable_types)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    public static function getOption($option_name) {
        return get_option($option_name);
    }

    public static function saveOption($option_name, $option_value) {
        update_option($option_name, $option_value);
    }

    public static function setLastErrorCookie($error) {
        if(isset($_COOKIE['branchmanager_lasterror'])) {
            unset($_COOKIE['branchmanager_lasterror']);
        }

        setcookie("branchmanager_lasterror", sanitize_text_field($error), 0, "/", "", false, false);
    }

    public static function getLastErrorCookie() {
        if(isset($_COOKIE['branchmanager_lasterror'])) {
            return $_COOKIE['branchmanager_lasterror'];
        }

        return "";
    }

    public static function deleteLastErrorCookie() {
        if(isset($_COOKIE['branchmanager_lasterror'])) {
            unset($_COOKIE['branchmanager_lasterror']);
            setcookie("branchmanager_lasterror", "", -1, "/", "", false, false);
        }
    }

    public function displayUtilitiesInterface() {
        self::checkPermissions();

        require_once(plugin_dir_path(__FILE__) . "html-views/utilities.php");
    }

    public function handleRunUtilityForm() {

        self::checkPermissions();

        $status_code                = "";                           // The status code to return to the dashboard view
        $fail                       = true;                         // Whether we are in a failure state
        $utility_to_run             = "";                           // The utility to run

        if(isset($_POST['branchmanager'])) {
            if(wp_verify_nonce( $_POST['branchmanager'], 'run_utility' )) {

                if(isset($_POST['utility_to_run']) && $_POST['utility_to_run'] != "") {

                    $utility_to_run = sanitize_text_field($_POST['utility_to_run']);

                    if($utility_to_run == "rollback") {
                        self::rollback();
                    }
                    else if($utility_to_run == "abort") {
                        self::abort();
                    }
                    else if ($utility_to_run == "clear") {
                        self::deleteLastErrorCookie();
                    }
                    else if ($utility_to_run == "reset_soft") {
                        self::reset("soft");
                    }
                    else if ($utility_to_run == "reset_hard") {
                        self::reset("hard");
                    }
                    else if ($utility_to_run == "force_commit") {
                        self::forceCommit();
                    }
                    else {
                        $status_code = "invalid_choice";
                    }

                    if($status_code == "") {
                       $fail = false; 
                    }

                }
                else {
                    $status_code = 'no-choice';
                }
                
            }
            else {
                $status_code = 'nonce-invalid';
            }
        } 
        else {
            $status_code = 'nonce-empty';
        }
        
        if($fail === false) {
            $status_code = "all-ok";
        }

        wp_redirect(admin_url('/admin.php?page=branch-manager-utilities&status_code=' . $status_code), 301);
        exit;
    }

    public static function forceCommit() {
        global $wpdb;
        $query = $wpdb->prepare("CALL DOLT_COMMIT('-A', '-m', '%s', '--author', '%s');", "SYSTEM COMMIT VIA UTILITIES", self::getAuthorString());
        $wpdb->query($query);
    }

    public static function resolveConflicts($conflicts) {

        $success = true;

        if(self::checkIfConflictsUserResolvable($conflicts) !== true) {
            $success = false;
        }

        if(isset($conflicts['dolt_conflicts'])) {
            $result = self::resolveDataConflicts($conflicts['dolt_conflicts']);

            if($result !== true) {
                $success = false;
            }
        }

        if(isset($conflicts['constraint_violations'])) {
            $result = self::resolveConstraintViolations($conflicts['constraint_violations']);

            if($result !== true) {
                $success = false;
            }
        }

        return $success;

    }

    public static function resolveConstraintViolations($constraint_violations) {

        global $wpdb;

        $low_level_violations = $constraint_violations["low_level"];

        foreach($low_level_violations as $table => $violations) {

            $violation_counter = count($violations);

            foreach($violations as $violation) {
                if($violation['violation_type'] == "unique index") {

                    // Unique index conflicts require deletion of the conflicting data
                    // We are expecting that a checkbox exists for each conflicting entry
                    // It should be in the format of: conflict_constraint_wp_options_uniqueindex_0

                    $field_name = "conflict_constraint_". $table ."_uniqueindex_" . $violation_counter;

                    if(isset($_POST[$field_name]) AND $_POST[$field_name] == "confirmdelete") {

                        $violation_length = count($violation);
                        $violation_loop_counter = 0;

                        $query = "";
                        $wp_delete_query = "DELETE FROM " . $table . " WHERE ";

                        foreach($violation as $violation_field => $violation_value) {

                            if($violation_loop_counter != 0 && $violation_loop_counter != 1 && $violation_loop_counter != ($violation_length - 1)) {

                                // These are the fields that are different for every table

                                $query = $query . "" . $violation_field . "='". $violation_value ."'";

                                if($violation_loop_counter < ($violation_length -2 )) {
                                    $query = $query . " AND ";
                                }
                                else {
                                    $query = $query . ";";
                                }

                            }

                            $violation_loop_counter++;

                        }

                        $wp_delete_query = $wp_delete_query . $query;
                        $results = $wpdb->get_results($wp_delete_query);

                        $dolt_constraint_query = "DELETE FROM dolt_constraint_violations_" . $table . " WHERE " . $query;
                        $results = $wpdb->get_results($dolt_constraint_query);

                    }
                    else {
                        return false;   // Proper confirmation doesn't exist
                    }

                }
                else {
                    // We don't know how to handle this conflict type, so bail
                    return false;
                }

                $violation_counter--;
            }

            // Run queries to clear the rest of the conflicts within Dolt
            // $wpdb->query("DELETE FROM dolt_constraint_violations WHERE table='". $table ."';");

        }

        return true;

    }

    /**
     * Separates BASE, OURS and THEIRS into separate arrays given a conflicted row
     */
    public static function separateBaseOursTheirs($conflicted_row) {

        $base = [];
        $ours = [];
        $theirs = [];

        foreach($conflicted_row as $field => $value) {

            if(str_starts_with(strtolower($field), "base_")) {
                $base[$field] = $value;
            } 
            else if(str_starts_with(strtolower($field), "our_")) {
                $ours[$field] = $value;
            }
            else if(str_starts_with(strtolower($field), "their_")) {
                $theirs[$field] = $value;
            }
        }

        $all = [$base, $ours, $theirs];

        return $all;

    }

    public static function showDiff($separated_conflicted_row) {

        $ours = $separated_conflicted_row[1];
        $theirs = $separated_conflicted_row[2];

        $conflicts = [];

        foreach($ours as $key => $item) {
            $their_key = str_replace("our_", "their_", $key);
            $their_value = $separated_conflicted_row[2][$their_key];

            if($their_value != $item) {
                $the_conflict = [];
                $the_conflict[$key] = $item;
                $the_conflict[$their_key] = $their_value;
                $conflicts[] = $the_conflict;
            }
        }

        return $conflicts;

    }

    public static function resolveDataConflicts($data_conflicts) {

        global $wpdb;

        $low_level_violations = $data_conflicts["low_level"];

        foreach($low_level_violations as $table => $violations) {

            $violation_counter = count($violations);

            foreach($violations as $violation) {
                
                // Data conflicts require us to either choose a mine or theirs option
                // If selecting mine, we just delete data
                // If selecting theirs, it's more complicated, some data needs to be replaced

                $field_name = $violation['dolt_conflict_id'];

                if(isset($_POST[$field_name]) && ($_POST[$field_name] == "mine" || $_POST[$field_name] == "theirs")) {

                    $violation_length = count($violation);
                    $violation_loop_counter = 0;

                    if($_POST[$field_name] == "theirs") {

                        // Choosing theirs requires replacing some data

                        // Get the table structure for the table we'll be replacing into
                        $table_structure_result = $wpdb->get_results("SHOW COLUMNS FROM " . $table);

                        $fields_to_update = [];
                        $primary_key = "";
                        $primary_key_value = "";

                        foreach($table_structure_result as $structure_result) {
                            
                            if($structure_result->Key == "PRI" && $primary_key == "") {
                                $primary_key = $structure_result->Field;
                            }
                            
                            $fields_to_update[] = $structure_result->Field;

                        }

                        if($primary_key == "") {
                            return false; // There's no primary key so no easy way to update this table
                        }

                        // Get the data from the dolt_conflicts table for the "theirs_" fields
                        $data_results_query = $wpdb->prepare("SELECT * FROM dolt_conflicts_" . $table . " WHERE dolt_conflict_id='%s';", $field_name);
                        $data_results = $wpdb->get_results($data_results_query);

                        if(!isset($data_results[0])) {
                            return false; // No Valid Result
                        }
                        
                        $data_result = $data_results[0];
                        $replacement_values = [];
                        $their_fields = [];
                        
                        foreach($data_result as $field => $value) {

                            if(str_starts_with(strtolower($field), "their_")) {
                                if($field == "their_" . $primary_key) {
                                    $primary_key_value = $value;
                                }
                                
                                if($field != "their_diff_type") {
                                    $their_fields[] = $field;
                                    $replacement_values[] = $value;
                                }
                            }
                        }

                        // Build our replace into and delete queries
                        $replace_into_query = "REPLACE INTO " . $table . " (". implode(", ", $fields_to_update) .") (SELECT ". implode(", ", $their_fields) ." FROM dolt_conflicts_".$table." WHERE their_". $primary_key ." = '". $primary_key_value ."');"; 
                        $replace_into_result = $wpdb->query($replace_into_query);

                        if ($wpdb->last_error != '') {
                            return false;
                        }

                        // Clear the conflict
                        
                        $clear_conflict_query = $wpdb->prepare("DELETE FROM dolt_conflicts_" . $table . " WHERE dolt_conflict_id='%s';", $field_name);
                        $clear_conflict_result = $wpdb->query($clear_conflict_query);

                        if ($wpdb->last_error != '') {
                            return false;
                        }
                        
                    }
                    else {

                        // Choosing mine just requires deleting some data

                        $query = $wpdb->prepare("DELETE FROM dolt_conflicts_" . $table . " WHERE dolt_conflict_id='%s';", $field_name);
                        $result = $wpdb->get_results($query);

                        if ($wpdb->last_error != '') {
                            return false;
                        }

                    }

                }
                else {
                    return false;   // Proper confirmation doesn't exist
                }

                $violation_counter--;
            }

        }

        return true;

    }

    public static function getSafeFileStorageLocation() {

        $file_storage_location = self::getOption("branchmanager_writeable_location");

        if(is_dir($file_storage_location) && is_writable($file_storage_location)) {
            return $file_storage_location;
        }

        return false;
        
    }

    public static function getConflictFilePath() {
        $storage_location = self::getSafeFileStorageLocation();
        $file_string = "conflict-for-user-" . get_current_user_id() . '.json';
        $file_path = $storage_location . "/" . $file_string;

        return $file_path;
    }

    public static function saveConflictData($conflict_data_json) {

        $file_path = self::getConflictFilePath();
        $file_pointer = fopen($file_path, "w") or die("Unable to open file for writing!");
        fwrite($file_pointer, $conflict_data_json);
        fclose($file_pointer);

    }

    public static function getConflictData() {

        $file_path = self::getConflictFilePath();

        if(file_exists($file_path)) {
            $file_pointer = fopen($file_path, "r") or die("Unable to open file for reading!");
            $conflicts_json = fread($file_pointer, filesize($file_path));
            fclose($file_pointer);

            if(json_validate($conflicts_json)) {
                return $conflicts_json;
            }
        }

        return false;

    }

    public function displaySettingsInterface() {
        self::checkPermissions();

        $writable_file_location = sanitize_text_field(self::getSafeFileStorageLocation());

        require_once(plugin_dir_path(__FILE__) . "html-views/settings.php");
    }

    public static function handleSaveWritableLocationForm() {
        self::checkPermissions();

        $status_code = "";

        if(isset($_POST['branchmanager'])) {
            if(wp_verify_nonce( $_POST['branchmanager'], 'save_writable_location' )) {

                if(isset($_POST['writable-location']) && $_POST['writable-location'] != "") {

                    $writeable_location = sanitize_text_field($_POST['writable-location']);

                    if(is_dir($writeable_location) && is_writable($writeable_location)) {
                        self::saveOption("branchmanager_writeable_location", $writeable_location);
                    }
                    else {
                        $status_code = "location-not-writable";
                    }

                }
                else {
                    $status_code = 'empty-location';
                }
                
            }
            else {
                $status_code = 'nonce-invalid';
            }
        } 
        else {
            $status_code = 'nonce-empty';
        }
        
        if($status_code == "") {
            $status_code = "all-ok";
        }

        wp_redirect(admin_url('/admin.php?page=branch-manager-settings&status_code=' . $status_code), 301);
        exit;
    }

    public function displayCommitHistory() {
        self::checkPermissions();

        $branch = self::getCurrentBranch();

        $commit_history = self::getCommitHistory();

        require_once(plugin_dir_path(__FILE__) . "html-views/history.php");
    }

    public function getCommitHistory() {
        global $wpdb;

        $history_query = "SELECT * FROM dolt_log";
        $total_query = "SELECT COUNT(1) FROM ($history_query) AS the_count";
        $total = $wpdb->get_var($total_query);

        $items_per_page = 20;
        $offset = 0;
        $page = 1;
        $num_pages = intval(ceil($total / $items_per_page));

        if(isset($_GET["page_no"]) && is_numeric($_GET["page_no"]) && $_GET["page_no"] > 0 && $_GET["page_no"] <= $num_pages) {
            $offset = intval(ceil($_GET["page_no"] - 1)) * $items_per_page;
            $page = $_GET["page_no"];
        }

        $paginated_query = $wpdb->prepare($history_query . " LIMIT %d OFFSET %d;", $items_per_page, $offset);
        
        $history_results = [];
        $history_results["results"] = $wpdb->get_results($paginated_query);
        $history_results["total"] = $total;
        $history_results["num_pages"] = $num_pages;
        $history_results["current_page"] = $page;

        return $history_results;
    }

    public static function handleDeleteBranchForm() {
        self::checkPermissions();

        $branch         = "";       // The new branch to switch to
        $current_branch = "";       // The branch we are currently on
        $status_code    = "";       // The status code to return to the dashboard view
        $fail           = true;     // Whether we are in a failure state

        if(isset($_POST['branchmanager'])) {
            if(wp_verify_nonce( $_POST['branchmanager'], 'delete_branch' )) {
                if(isset($_POST['branch'])) {
                    $branch = sanitize_text_field($_POST['branch']);
        
                    if(self::checkBranchExists($branch) === true) {
                        $status_code = "branch-exists";

                        if(isset($_POST["confirmation"]) && $_POST["confirmation"] == "confirm") {

                            if($branch == "main") {
                                $status_code = "is-main";
                            }
                            elseif($branch == $current_branch) {
                                $status_code = "is-current";
                            }
                            else {

                                // Delete the branch
                                global $wpdb;

                                $query = $wpdb->prepare("CALL dolt_branch('-d', '-f', '%s');", $branch);
                                $wpdb->query($query);

                                if ($wpdb->last_error == '') {
                                    $fail = false;
                                }
                                else {
                                    $status_code = "delete-fail";
                                }

                            }

                        }
                        else {
                            $status_code = "confirmation-fail";
                        }

                    }
                    else {
                        $status_code = "branch-no-exist";
                    }
                }
                else {
                    $status_code = "branch-empty";
                }
            }
            else {
                $status_code = 'nonce-invalid';
            }
        } 
        else {
            $status_code = 'nonce-empty';
        }
        
        if($fail === false) {
            $status_code = "branch-delete-ok";
        }

        wp_redirect(admin_url('/admin.php?page=branch-manager-branches&status_code=' . $status_code), 301);
        exit;
    }
}

// Initialize the plugin
$branch_manager_plugin = new BranchManager();
