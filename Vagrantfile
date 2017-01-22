# -*- mode: ruby -*-
# vi: set ft=ruby :

VAGRANTFILE_API_VERSION = "2"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
  config.ssh.username = "testbot"
  config.vm.box = "drupalci/testbot"
  config.vm.box_url = "https://s3-us-west-2.amazonaws.com/drupalci-vagrant/vagrant/json/drupalci/testbot.json"

  config.ssh.shell = "bash -c 'BASH_ENV=/etc/profile exec bash'"

  config.vm.network :private_network, ip: "192.168.42.42"
  config.vm.synced_folder ".", "/home/testbot/testrunner"
  config.vm.provider "virtualbox" do |v|
    v.memory = 4096
    v.cpus = 4
  end
end

#        v.customize [ "modifyvm", :id, "--nictype1", "Am79C973"]
#        v.customize [ "modifyvm", :id, "--nictype2", "Am79C973"]
