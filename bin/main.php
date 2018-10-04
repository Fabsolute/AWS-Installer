#!/usr/bin/env php
<?php
echo __DIR__;
// If we're running from phar load the phar autoload file.
$pharPath = \Phar::running(true);
if ($pharPath) {
    $autoloaderPath = "$pharPath/vendor/autoload.php";
} else {
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        $autoloaderPath = __DIR__ . '/vendor/autoload.php';
    } elseif (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        $autoloaderPath = __DIR__ . '/../vendor/autoload.php';
    }elseif (file_exists(__DIR__ . '/../../autoload.php')) {
        $autoloaderPath = __DIR__ . '/../../autoload.php';
    } else {
        die("Could not find autoloader. Run 'composer install'.");
    }
}
$classLoader = require $autoloaderPath;

// Customization variables
$appName = "Aws Installer";
$appVersion = "0.0.1";
$commandClasses = [\Fabstract\Installer\PHPInstaller::class, \Fabstract\Installer\CouchInstaller::class];
$selfUpdateRepository = 'Fabsolute/Aws-Installer';

// Define our Runner, and pass it the command classes we provide.
$runner = new \Robo\Runner($commandClasses);
$runner
    ->setSelfUpdateRepository($selfUpdateRepository)
    ->setClassLoader($classLoader);

$argv = $_SERVER['argv'];
$output = new \Symfony\Component\Console\Output\ConsoleOutput();
$statusCode = $runner->execute($argv, $appName, $appVersion, $output);
exit($statusCode);
