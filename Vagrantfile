# -*- mode: ruby -*-
# vi: set ft=ruby :

VAGRANTFILE_API_VERSION = "2"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
  config.vm.box = "ubuntu/xenial64"
  config.ssh.shell = "bash -c 'BASH_ENV=/etc/profile exec bash'"

  config.vm.network :private_network, ip: "192.168.42.42"
  config.vm.synced_folder ".", "/home/ubuntu/drupalci_testbot"
  config.vm.define "testbot" do |testbot|
      testbot.vm.provider "virtualbox" do |v|
        unless File.exists?("drupalci.vdi")
            v.customize [
              'createmedium', 'disk',
              '--filename', "drupalci.vdi",
              '--format', 'VDI',
              '--size', 50 * 1024
            ]
          end

          v.customize [
            'storageattach', :id,
            '--storagectl', 'SCSI Controller',
            '--port', 2,
            '--device', 0,
            '--type', 'hdd',
            '--medium', "drupalci.vdi"
          ]

        v.customize [ "modifyvm", :id, "--cpus", "4" ]
        v.customize [ "modifyvm", :id, "--memory", "4096" ]
        v.customize [ "modifyvm", :id, "--natdnshostresolver1", "on" ]
        v.customize [ "modifyvm", :id, "--natdnsproxy1", "on"]
        v.customize [ "modifyvm", :id, "--nictype1", "Am79C973"]
        v.customize [ "modifyvm", :id, "--nictype2", "Am79C973"]
        config.trigger.before :destroy do
          info "Preserving Containers filesystem. Remove drupalci.vdi to completely destroy"
          vm_id = `VBoxManage list vms |grep drupalci_testbot |awk '{gsub(/[{}]/,"",$2);print $2}'`
          vm_id_stripped = vm_id.strip
          poweroff = "VBoxManage controlvm " + vm_id_stripped + " poweroff"
          system(poweroff)
          unmount = [
                'VBoxManage storageattach', vm_id_stripped,
                '--storagectl', '"SCSI Controller"',
                '--port', 2,
                '--device', 0,
                '--type', 'hdd',
                '--medium', "none"
          ].join(' ')
          system(unmount)
          unprovision = "rm PROVISIONED"
          system(unprovision)
          end
      end
  end
  config.vm.provision "shell", inline: <<-EOF
          fstype=`file -sL /dev/sdc|awk '{print $5}'`
          if [ "$fstype" != "ext4" ]; then echo "make disk";
            parted /dev/sdc mklabel gpt
            parted -a opt /dev/sdc mkpart primary ext4 0% 100%
            mkfs.ext4 -L DrupalCI /dev/sdc -F
          fi
      EOF
  config.vm.provision :shell, :path => "provision.sh"
end
