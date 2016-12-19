DrupalCI - Test Runner
======================

Running the test runner tests
-----------------------------

There is currently no continuous integration set up for drupalci_testbot. Run tests locally, please.

At this point, the tests for drupalci are somewhat coupled to the environment, so we must start clean:

- You need to have virtualbox installed. Preferably 5.0 or higher.
- You need to have vagrant installed.
- You need to install the vagrant triggers plugin:
       $ vagrant plugin install vagrant-triggers
- You probably also want to have the vagrant vbguest plugin installed. This will make it so that if your virtualbox
  app is upgraded, then the underlying guest OS's stay in sync.
       $ vagrant plugin install vagrant-vbguest

- Use a native environment, either on a testbot machine or using the virtual machine provided by vagrant or similar.
- Clear out all config. This is easiest to accomplish by removing `~/.drupalci`.
- Run the tests using `./bin/phpunit`.
- Depending on your needs, you can exclude some tests with hard dependencies with `--exclude-group`. Notably `@group docker`.

        $ git clone --branch dev https://git.drupal.org/project/drupalci_testbot.git
        $ cd drupalci_testbot
        $ vagrant up
        // Wait a while...
        $ vagrant ssh
        $ rm -rf ~/.drupalci
        $ ./drupalci init
        // Pull down the all of the listed images.
        // Remove any existing containers if this isnt the first time you've started your vagrant box.
        $ ./drupalci docker-rm containers

        // Run the tests.
        $ ./bin/phpunit
        // Tests run.
        $ ./bin/phpunit --exclude-group docker
        // Tests run without docker dependencies.

- If you destroy you vagrant box, the docker_images.vdi filesystem will still exist with the containers you pulled.
- You can remove the drupalci.vdi if you wish to save space, or keep it around so that when you vagrant up again, you
- already have the containers pulled down. The proper way to remove it is in virtualbox File>virtual media manager and remove drupalci.vdi
