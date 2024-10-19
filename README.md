# Branch Manager
A plugin for managing version control of your WordPress database using [Dolt](https://www.dolthub.com/).

## Current Plugin Version
**Version 0.1.2** - Fix a bug in conflict resolution when trying to delete a serialized option value.

## Background Information

### What is this plugin?
This plugin works with the [Dolt](https://www.dolthub.com/) database to enable version control, including branches and merging, of your WordPress database.  This plugin enables you to create branches of your primary WordPress database, enabling true version control of your WordPress database through an easy to use admin interface.

### What is Dolt?
[Dolt](https://www.dolthub.com/) is a database that is meant to be a drop-in replacement for MySQL, while also enabling Git-like functionality inside of your database.  Basically, it's as if MySQL and Git had a baby.  You can create commits and branches inside of your database and keep a history of all changes in your WordPress database.

### How can this plugin be used?
This plugin sits on top of a Dolt database and allows you to perform common git-like actions supported by Dolt on your WordPress database, including committing, branching and merging.

With this plugin you can:

- Keep track of changes to your website and see who committed those changes
- Create branches for different sets of content and then merge them back into your live branch
- Build out content in a branch for previewing and then merge that content back into your site's live (main) database branch

## Requirements
This plugin requires that your website be running the [Dolt Database](https://www.dolthub.com/), with a version >= 1.42.19.  This plugin also requires a PHP version >= 8.3.

## Installation
To install this plugin: 

1. Download this plugin and extract the contents to your *wp_contents > plugins* folder.  You should be left with a folder at *wp_contents > plugins > branch-manager*.  You can also upload the plugin .zip file in your WordPress admin area.
2. Activate the plugin.
3. Go to the *Version Control* link in your left hand sidebar.  
4. Click on the *Settings* sub menu item.
5. Provide the path to a writeable directory, preferrably outside of your web root.  This directory will be used for storing conflict files, which may contain sensitive data from your database.
6. Click *Save File Location* and then verify that you get a success message.
7. Click on *Version Control* from the menu.
8. Commit your existing WordPress database tables.  Find the *Quick Commit* section and fill out a commit message and click *Commit Changes*.  If everything was successful, you should see a success message.

If you made it this far without issue, the plugin should now be ready for you to use.

## Plugin Usage

Branch Manager enables you to manage branches on a Dolt based database from an admin interface inside of your WordPress dashboard.  Rather than having to run database queries within Dolt manually, Branch Manager simplifies common version control tasks on your database from within the WordPress admin interface, including branching, merging and conflict resolution.

### Making and Committing Changes

When you make changes to a WordPress database running Dolt, by default they are unstaged and uncommitted.  With Dolt, you can commit a group of changes as a commit, the same as you would with Git.

To commit changes, click on *Version Control* from the left hand menu and then find the *Quick Commit* section.  Ensure that the branch that is shown is the branch you want to commit on, then enter a commit message and click the *Commit Changes* button.

### Unstaged Changes
Unstaged changes are shown under the *Status Report* section of the main *Version Control* admin page.  If you have changes to your database, you will see the database table with changes listed as well as whether the changes are staged or not.

Don't worry about staging your changes.  When you perform a commit, Branch Manager automatically stages your changes before committing.

### Working with Branches
Branches enable you to work with different content in your WordPress database, the same way a Git branch enables you to work on new code features separate from your *main* branch.

One use for branches is for building out a group of like content, such as a sale, that you want to be able to preview but that isn't quite ready to go live yet.

Imagine you have to build out a Black Friday sale for your website.  Without Dolt, you would have to build your sale pages and products, but hide them from the public.  With Dolt and Branch Manager, you can build your landing pages and products in a branch and test your entire sale end to end, all while the changes are not visible on your main website.  Once it's time for the sale to go live, you simply merge your branch back into *main* to make your sale live.

#### Creating a New Branch

To create a new branch, from the main *Version Control* interface, find the *Quickly Create New Branch* form.  Type the name of your branch and click *Create Branch*.  If your branch doesn't already exist, your new branch will be created and you'll automatically be switched to your new branch.

#### Switching to a different branch.

To switch branches, from the main *Version Control* screen, find your desired branch and click the *Switch* button.

Branches work with cookies, so each user can be on a different branch.  Your live site always remains on the *main* branch.

#### Deleting a Branch

To delete a branch, click on *Manage Branches* in the left hand menu.  Then find a branch and click the *Confirm Delete* checkbox and then click the delete button.  This will delete the branch as well as any unsaved changes in that branch.  Don't delete a branch unless you're done with it!  

You also cannot delete the *main* branch or the currently active branch.

#### Merging changes into a branch
If you want to take the latest changes in one branch and put them in another branch, you'll need to perform a merge.  Before you can merge, you'll want to make sure any changes in the branch that will receive the changes have been committed.

To begin a merge, switch to the branch that will receive the updated changes.  Then go to *Pull and Merge*.  Select the branch that contains the changes you will merge into the current branch.

You can check the *Commit any changes to the main that may have occurred since your last commit before attempting the merge.* checkbox to auto-commit any changes that may have been made on your branch since your last commit.  It may be necessary to check this checkbox if WordPress is writing to your database in the background while you are attempting to initiate a merge.  Often WordPress plugins will write to log tables in the background, so there can be several automatic changes to your database since your last commit.

Click the *Merge* button to initiate the merge.  If the changes can be merged automatically, you should see a success message.  If, however, they cannot be merged, you will see an error message alerting you of a *conflict*.

### Conflict Resolution

When attempting to merge a branch, there may be cases where content you have updated has also been updated by another user in a different branch.  This can lead to a *conflict*.

Branch Manager is able to resolve certain types of conflicts when completing a merge.

#### Conflicts in Data

If two users have updated the same content, such as two users updated the same page in two different branches, this creates a data conflict.  When a data conflict occurs, you'll be shown a table listing each conflict and showing you the raw data from each of the branches, your branch and the branch you are merging into your branch.

For each conflict, from the *Actions* column you will need to choose whether to *Keep Mine* or *Keep Theirs*.  The *Keep Mine* option keeps the changes in your current branch, while the *Keep Theirs* change replaces the conflicting changes in your branch with the changes from the other side of the merge.

#### Conflicts in Unique Indexes

WordPress may save items to the *options* table in multiple branches that have the same identifying string.  If this happens, you can get a unique index conflict.  The only way to resolve these conflicts is to delete the conflicting data.  Check the *Delete* checkbox next to each conflict to confirm the deletion of the conflicting data.

#### Wrapping Up Conflicts

Once you have resolved the conflicts, you need to confirm via the checkbox at the bottom that you have resolved all conflicts, which lets Branch Manager know that it should attempt to resolve the conflicts when you submit the form.  You may need to go through the conflict resolution screen multiple times if additional changes are made behind the scenes while you are resolving the conflicts.  This can happen if your site is receiving lots of database changes while you're resolving conflicts.

If you cannot resolve a conflict via the Branch Manager conflicts screen, you will not be allowed to begin the conflict resolution process and you will need to resolve the conflict manually using your Dolt database.

## Getting Support

For support, please open an issue here on GitHub, or contact us via email at: build@yourrightwebsite.com

## Change Log

### 0.1.2
- Fixed a bug in the SQL query that deletes unique index queries in situations where the value being deleted is serialized data.

### 0.1.1
- Updated method for determining if a data conflict is resolvable to check for a primary key, which is the same method used to update data after resolving a conflict.

### 0.1.0
- Initial Public Release

## Known Bugs

The following bugs are currently known to us:

- Plugin activations fail if on any branch other than the *main* branch.  It is thought this occurs because WordPress does a redirect that causes the database to switch back to the *main* branch momentairly.  As a workaround, for now all plugins should be installed and activated on the *main* branch only.
- Users may have to re-authenticate with WordPress several times when switching branches if attempting to switch branches after a new login but before a commit has been completed on the *main* branch.

## Plugin License

Branch Manager is free for non-commercial use.  For commercial use, a license is required.  For licensing information and inquiries, please contact us at: **build@yourrightwebsite.com**

If you are interested in being a beta tester and obtaining a free commercial license, please contact us at build@yourrightwebsite.com with information about your use case.  We're looking for beta testers to provide feedback as we grow this plugin.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

© 2024 [Your Right Website LLC](https://yourrightwebsite.com/)

*Proudly made in America*


