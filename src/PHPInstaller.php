<?php

namespace Fabstract\Installer;

define('MY_PHP_VERSION', '7.2');
class PHPInstaller extends BaseInstaller
{
    /**
     * mb_string eklentisini kurar
     */
    public function phpInstallMB()
    {
        $this->phpInstallExtension("mbstring");
    }

    /**
     * mysql eklentisini kurar
     */
    public function phpInstallMysql()
    {
        $this->phpInstallExtension('mysql');
    }

    /**
     * php restartlar
     */
    public function phpRestart()
    {
        return $this->serviceRestart('php' . MY_PHP_VERSION . '-fpm');
    }

    private function phpInstallExtension($extension_name)
    {
        $full_extension_name = 'php' . MY_PHP_VERSION . '-' . $extension_name;
        if (extension_loaded($extension_name)) {
            $this->say($full_extension_name . ' already installed.');
            return;
        }

        $this->say($full_extension_name . ' kuruluyor');
        $this->phpExtensionAfterInstall($this->aptInstallPackage($full_extension_name));
    }

    /**
     * @param bool $was_successful
     */
    private function phpExtensionAfterInstall($was_successful)
    {
        if ($was_successful) {
            $this->say("Kurulum basariyla tamamlandi.");
            $this->phpRestart();
        } else {
            $this->say("Kurulum tamamlanamadi!");
        }
    }

}
