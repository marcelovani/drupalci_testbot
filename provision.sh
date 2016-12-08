#!/bin/bash -e
#
# Name:         provision.sh
#
# Purpose:      quick start the vagrant box with all the things
#
# Comments:
#
# Usage:        vagrant up (on the repo root)
#
# Author:       Ricardo Amaro (mail_at_ricardoamaro.com)
# Contributors: Jeremy Thorson jthorson
# Contributors: Rudy Grigar (basic)
#
# Bugs/Issues:  Use the issue queue on drupal.org
#               IRC #drupal-testing
#
# Docs:         README.md for complete information
#

export HOME="/home/ubuntu"

if [ -f /home/ubuntu/drupalci_testbot/PROVISIONED ];
then
  echo "--------------------------------------------------------------------"
  echo
  echo "######                                      #####  ###"
  echo "#     # #####  #    # #####    ##   #      #     #  # "
  echo "#     # #    # #    # #    #  #  #  #      #        # "
  echo "#     # #    # #    # #    # #    # #      #        # "
  echo "#     # #####  #    # #####  ###### #      #        # "
  echo "#     # #   #  #    # #      #    # #      #     #  # "
  echo "######  #    #  ####  #      #    # ######  #####  ###   TESTBOT"
  echo ""
  echo "--------------------------------------------------------------------"
  echo
  echo "Hi there, it is your local Testbot!"
  echo
  echo "You seem to have this box already installed - which is a good thing!"
  echo "Documentation can be found in README.md or read on..."
  echo ""
else
  echo 'cd /home/ubuntu/drupalci_testbot' >> /home/ubuntu/.bashrc
  echo 'Defaults        env_keep +="HOME"' >> /etc/sudoers
  echo "Installing and building the all the things..."
  echo "on: $(hostname) with user: $(whoami) home: $HOME"
  swapoff -a
  dd if=/dev/zero of=/var/swapfile bs=1M count=2048
  chmod 600 /var/swapfile
  mkswap /var/swapfile
  swapon /var/swapfile
  /bin/echo "/var/swapfile swap swap defaults 0 0" >>/etc/fstab
  add-apt-repository ppa:ondrej/php
  echo "deb https://apt.dockerproject.org/repo ubuntu-xenial main" > /etc/apt/sources.list.d/docker.list
  apt-key adv --keyserver hkp://p80.pool.sks-keyservers.net:80 --recv-keys 58118E89F3A912897C070ADBF76221572C52609D

  apt-get update && apt-get upgrade -y
  apt-get install -y git mc ssh gawk grep sudo htop mysql-client php7.0 curl php7.0-curl php7.0-mysql php7.0-pgsql php7.0-sqlite php-xdebug php7.0-xml php7.0-mbstring postgresql-client postgresql-client-common sqlite3 apt-transport-https ca-certificates linux-image-extra-$(uname -r) linux-image-extra-virtual
  apt-get install -y docker-engine=1.12.2-0~xenial

  apt-get autoclean && apt-get autoremove -y

  #Update/change cli php.ini
  echo "Updating php.ini for cli"
  sed -i 's/; sys_temp_dir = "\/tmp"/sys_temp_dir = "\/var\/lib\/drupalci\/web\/"/g' /etc/php/7.0/cli/php.ini
  sed -i 's/variables_order = \"GPCS\"/variables_order = \"EGPCS\"/g' /etc/php/7.0/cli/php.ini
  echo "Disabling Xdebug. To re-enable, uncomment extension in /etc/php/7.0/cli/conf.d/20-xdebug.ini"
  echo ";zend_extension=xdebug.so" > /etc/php/7.0/cli/conf.d/20-xdebug.ini



  cd /home/ubuntu/drupalci_testbot

  echo "Installing composer"
  curl -sS https://getcomposer.org/installer | php
  mv composer.phar /usr/local/bin/composer

  echo "Running php composer.phar install"
  composer install --no-progress

  echo "Creating drupalci symlink"
  if ! [ -h /opt/drupalci_testbot ];
  then
    ln -s /home/ubuntu/drupalci_testbot /opt/drupalci_testbot
  fi
  if ! [ -h /usr/local/bin/drupalci ];
    then
     ln -s /home/ubuntu/drupalci_testbot/drupalci /usr/local/bin/drupalci
  fi

  echo "Creating directories for docker binds"
  DCIPATH="/var/lib/drupalci"

  echo "LABEL=DrupalCI       $DCIPATH     ext4    defaults        0       0" >> /etc/fstab

  mkdir -p $DCIPATH
  mount $DCIPATH
  if ! [ -d $DCIPATH/web ]; then
    mkdir -p $DCIPATH/web
  fi
  if ! [ -d $DCIPATH/database ]; then
    mkdir -p $DCIPATH/database
  fi
  if ! [ -d $DCIPATH/docker-images ]; then
    mkdir -p $DCIPATH/docker-images
  fi
  if ! [ -d $DCIPATH/drupal-checkout ]; then
    mkdir -p $DCIPATH/drupal-checkout
    echo "Checking out drupal for testing drupalci"
      git clone https://git.drupal.org/project/drupal $DCIPATH/drupal-checkout
      cd $DCIPATH/drupal-checkout
      composer install --no-progress
  fi



  echo "Changing ownership for the directories"
  # setting the uid:gid to www-data
  chown ubuntu:ubuntu $DCIPATH
  chown -R ubuntu:ubuntu $DCIPATH/drupal-checkout
  chown -R ubuntu:www-data $DCIPATH/web
  # setting the uid:gid to database (mysql/postgres)
  chown -R ubuntu:102 $DCIPATH/database
  adduser ubuntu www-data
  chmod -R 775 $DCIPATH/web
  chmod -R 775 $DCIPATH/database

  echo "Installing docker"
    # curl -sSL get.docker.io | sed 's/docker-engine/docker-engine=1.12.1-0~xenial/' | sh 2>&1 | egrep -i -v "Ctrl|docker installed"
    usermod -a -G docker ubuntu
    mkdir -p /etc/systemd/system/docker.service.d
    cat << EOF > /etc/systemd/system/docker.service.d/docker.conf
  [Service]
  ExecStart=
  ExecStart=/usr/bin/dockerd --graph="/var/lib/drupalci/docker-images" --storage-driver=overlay
EOF
    systemctl daemon-reload
    systemctl restart docker.service

  touch /home/ubuntu/drupalci_testbot/PROVISIONED

fi

chown -fR ubuntu:ubuntu /home/ubuntu
echo "Box started up, run *vagrant halt* to stop."
echo
echo "To access the box and run tests, run:"
echo "- vagrant ssh"

