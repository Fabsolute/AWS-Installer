#!/usr/bin/env bash
if [[ $EUID -ne 0 ]]; then
   echo "This script must be run as root"
   exit 1
fi

function package_exists(){
  return dpkg -l "$1" &> /dev/null
}

function install_package_if_not_exists(){
    if ! package_exists "$1"; then
        apt install "$1" -y
    fi
}

echo "LC_ALL=en_US.UTF-8" >> /etc/default/locale &&
if ! package_exists "php7.2-fpm"; then
    LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php -y &&
    apt update &&
    install_package_if_not_exists "php7.2-fpm"
fi &&

install_package_if_not_exists "php7.2-zip" &&
install_package_if_not_exists "unzip" &&

wget https://getcomposer.org/installer &&
php installer --install-dir=/usr/local/bin --filename=composer &&
rm installer &&
wget http://robo.li/robo.phar &&
chmod +x robo.phar &&
mv robo.phar /usr/bin/robo &&
apt install git &&
git clone https://github.com/Fabsolute/Aws-Installer.git installer &&
cd installer &&
chmod +x install.phar &&
mv install.phar /usr/bin/fabs &&
cd .. &&
rm install.sh &&
rm -r installer &&
fabs
