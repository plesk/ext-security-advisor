# -*- mode: ruby -*-
# vi: set ft=ruby :

VAGRANTFILE_API_VERSION = "2"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
  config.vm.box = "plesk/plesk-12.5"

  config.vm.provider "virtualbox" do |v|
    v.memory = 1024
    v.name = "ext-security-wizard"
  end

  config.ssh.insert_key = false

  config.vm.network "forwarded_port", guest: 8443, host: 8443
  config.vm.network "forwarded_port", guest: 8880, host: 8880
  config.vm.network "forwarded_port", guest: 80, host: 1080
  config.vm.network "forwarded_port", guest: 433, host: 10443
  config.vm.network "forwarded_port", guest: 3306, host: 3306
  config.vm.network "forwarded_port", guest: 8447, host: 8447

  config.vm.provision "shell", path: "vagrant/install_dev_deps.sh"
  config.vm.provision "shell", path: "vagrant/mount_ext.sh", args: "security-wizard"
  config.vm.provision "shell", path: "vagrant/install_license.sh"
  config.vm.provision "shell", path: "vagrant/install_updates.sh"
end
