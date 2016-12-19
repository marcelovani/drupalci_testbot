How to contribute to DrupalCI projects
======================================

August 4, 2016

The Basics
----------

The DrupalCI project is split into a few different sub-projects. It can be difficult to know which sub-project is appropriate for a given issue.

Drupal.org Testing Infrastructure is the catch-all projects for new issues, which will then be moved to a more specific project as needed: https://www.drupal.org/project/drupalci

If you have an issue you'd like to file about DrupalCI, search for it in the Drupal.org Testing Infrastructure issues first: https://www.drupal.org/project/issues/drupalci

Then move on to the more specific projects. They should be listed on the drupalci project page: https://www.drupal.org/project/drupalci

Finally, after looking in all the places, file an issue in the drupalci project, unless you know for sure you should file it in a more specific project.


Branch-Based Workflow
---------------------

The various DrupalCI projects use a git-branch-based review process.

Each repo will have a `dev` branch, which is a staging branch for evaluation before moving to the `production` branch.

Each issue will have its own 'feature' branch in the project repo. The branch will be named for the issue, starting with the issue number, like this: `2614516-coding-standards-cleanup`

Commits to project repos which start with the issue number will appear in the issue comment thread. See the Drupal standard for commit messages here: https://www.drupal.org/node/52287

Only maintainers have permission to push feature branches.

This means that if you are working on an issue that does not yet have a feature branch, you should submit a patch against `dev`. If the issue has a branch, submit a patch against the branch, and optionally against `dev` if you think there might be a conflict.


Reviewing An Issue
------------------

Each drupalci sub-project will have its own testing process and will probably involve setting up a virtual machine and downloading a bunch of docker images. This can be a lengthly and tedious process so thank you for undertaking it. :-)

For instance, here are the instructions for setting up a local `drupalci_testbot` development environment: https://www.drupal.org/node/2487065 `drupalci_testbot` also has a `TESTING.md` file you should read: http://cgit.drupalcode.org/drupalci_testbot/tree/TESTING.md

In order to review an issue, first make sure you can reproduce the problem on the `dev` branch.

If there's a patch, make sure you can apply it to the issue branch or `dev` as needed.

It might also be that the issue branch needs to be rebased, to be up-to-date from `dev`. Report any issues that arise during rebase.

Submitting Code
---------------

As noted above, each issue should have its own branch in the project repo. Patch against this branch. If it has not yet been created, patch against the `dev` branch.

Code must pass all tests. DrupalCI sub-projects themselves do not have continuous integration, so you must run tests locally. There should be instructions for running tests in each project, in a `TESTING.md` file. If there aren't, then consider writing some and filing a patch.

Code should also pass Drupal coding standards.
