DrupalCI - Test Runner
======================

Set up the environment
----------------------

Get the drupalci_testbot repo.

        $ git clone --branch dev https://git.drupal.org/project/drupalci_testbot.git
        $ cd drupalci_testbot

Running drupalci locally is fully supported as a virtualbox/vagrant machine that
exactly replicates the environments that drupalci runs in on aws. It requires at
least 4gb of spare ram, and at most 25GB of local disk.

Installing drupalci in a local linux host may be possible, but there are many
dependencies required by the host OS. Please refer to the packer build scripts
here for more information regarding what that might entail:
http://cgit.drupalcode.org/infrastructure/tree/drupalci/debian-testbot

- You need to have virtualbox installed. Preferably 5.0 or higher.
- You need to have vagrant installed.
- You need to have the vagrant vbguest plugin installed. This will make it so
  that if your virtualbox app is upgraded, then the underlying guest OS's stay
  in sync.

      $ vagrant plugin install vagrant-vbguest

- Use a native environment, either on a testbot machine or using the virtual
  machine provided by vagrant or similar.

Before you create your vagrant box, you may wish to adjust some Vagrantfile
settings, depending upon your local development resources. Primarily you would
want to adjust the 'v.memory = 8192', and 'v.cpus = 7' in the Vagrantfile.

        $ vagrant up
        // Wait a while...
        $ vagrant ssh
        // You're now logged in to the vagrant guest box.
        $ cd /home/testbot/testrunner
        $ composer install
        // @TODO: composer install should be run by vagrant.

The testbot makes use of quite a few docker containers. These are not available
locally at this point. The testbot will pull them as needed. Or, if you know you
are going to be working on a specific environment, you can pull the containers
beforehand.

The drupalci_environments project is where all these containers are built. You
can explore the repo to find the names of the containers:
http://cgit.drupalcode.org/drupalci_environments/tree/ For instance, available
PHP containers are listed here:
http://cgit.drupalcode.org/drupalci_environments/tree/php

On occasion you may see that the vagrant box has been updated to a newer version.

        // upgrade to newer version of the host
        $ vagrant box update
        // prune older versions of the host to free up space.
        $ vagrant box prune

Running the test runner tests
-----------------------------

When developing, the full test suite can take a long time to fully complete, so
it is suggested to focus on the test of the functionality you are working on
first, then run the full suite as a final regression test.

- Run the tests using `./bin/phpunit`.
- Depending on your needs, you can exclude some tests with hard dependencies
  with `--exclude-group`. Notably `@group docker`.

        // Run the tests.
        $ ./bin/phpunit
        // Tests run.
        $ ./bin/phpunit --exclude-group docker
        // Tests run without docker dependencies.

Commits and merges to the dev branch of drupalci_testbot are automatically run
and the results can be found here:
https://dispatcher.drupalci.org/job/DrupalCI_CI_Ci_ci/.
