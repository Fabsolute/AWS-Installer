<?php

namespace Fabstract\Installer;

class BaseInstaller extends \Robo\Tasks
{
    use \Robo\Task\Base\loadShortcuts;

    /**
     * @param string $package_name
     * @return bool
     */
    protected function aptInstallPackage($package_name)
    {
        if ($this->aptPackageExists($package_name)) {
            $this->say('package already exists');
            return false;
        }

        $response = $this->_exec("apt install " . $package_name . ' -y');
        return $response->wasSuccessful();
    }

    protected function aptPackageExists($package_name)
    {
        $response = $this->_exec('dpkg-query -W --showformat=\'${Status}\' ' . $package_name);
        return $response->getOutputData() === 'install ok installed';
    }

    protected function serviceRestart($service_name)
    {
        $this->say($service_name . ' restartlaniyor');
        $restart_response = $this->_exec('service ' . $service_name . ' restart');
        if ($restart_response->wasSuccessful()) {
            $this->say("restart basarili");
            return true;
        }

        $this->say("restart basarisiz!");
        return false;
    }

    protected function makeDir($dir_name)
    {
        $dir_path = explode('/', $dir_name);
        $path = '';
        foreach ($dir_path as $direction) {
            $path .= '/' . $direction;
            if (file_exists($path)) {
                continue;
            }

            mkdir($path);
        }
    }
}
