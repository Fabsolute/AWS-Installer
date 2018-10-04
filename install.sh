#!/usr/bin/env bash

function package_exists(){
    PKG_OK=$(dpkg-query -W --showformat='${Status}\n' $1|grep "install ok installed")
    if [ "" == "$PKG_OK" ]; then
       return 1
    fi
    return 0
}

function install_package_if_not_exists(){
    if [ package_exists $1 == 0 ]; then
        apt install $1 -y
    fi
}

echo "LC_ALL=en_US.UTF-8" >> /etc/default/locale &&
if [ package_exists "php7.2-fpm" == 0 ]; then
    add-apt-repository ppa:ondrej/php -y &&
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
git clone https://github.com/Fabsolute/Aws-Installer.git installer &&
cd installer &&
chmod +x install.fabs &&
mv install.fabs /usr/bin/fabs &&
rm install.sh &&
fabs
