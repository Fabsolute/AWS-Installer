<?php

namespace Fabstract\Installer;

use RomanPitak\Nginx\Config\Directive;
use RomanPitak\Nginx\Config\Scope;

define('NGINX_DEFAULT_PATH', '/etc/nginx');

class NginxInstaller extends BaseInstaller
{
//    public function couchInstall()
//    {
//        $this->say('couchdb kuruluyor');
//        $response = $this->aptInstallPackage('couchdb');
//        if ($response) {
//            $this->makeDir('/data/couchdb');
//
//            chown('/data/couchdb', 'couchdb');
//            $this->couchINISet(
//                'httpd',
//                'bind_address',
//                '0.0.0.0'
//            );
//
//            $this->couchINISet(
//                'httpd',
//                'port',
//                '5984'
//            );
//
//            $this->couchINISet(
//                'couchdb',
//                'database_dir',
//                '/data/couchdb'
//            );
//
//            $this->couchINISet(
//                'couchdb',
//                'view_index_dir',
//                '/data/couchdb'
//            );
//
//            $this->couchRestart();
//        } else {
//            $this->say("Kurulum tamamlanamadi!");
//        }
//    }

//    public function couchAuthSet()
//    {
//        $this->say('couch auth ayarlaniyor');
//
//        $this->couchINISet(
//            'httpd',
//            'WWW-Authenticate',
//            'Basic realm="administrator"'
//        );
//
//        $this->couchINISet(
//            'couch_httpd_auth',
//            'require_valid_user',
//            'true'
//        );
//
//        $this->couchRestart();
//    }

//    public function couchCompactSet()
//    {
//        $this->say('couch compact ayarlaniyor');
//
//        $this->couchINISet(
//            'daemons',
//            'compaction_daemon',
//            '{couch_compaction_daemon, start_link, []}'
//        );
//
//        $this->couchINISet(
//            'compaction_daemon',
//            'check_interval',
//            '300'
//        );
//
//        $this->couchINISet(
//            'compaction_daemon',
//            'min_file_size',
//            '131072'
//        );
//
//        $this->couchINISet(
//            'compactions',
//            '_default',
//            '[{db_fragmentation, "70%"}, {view_fragmentation, "60%"}]'
//        );
//
//        $this->couchRestart();
//    }

//    public function couchLuceneSet()
//    {
//        $this->say('couch lucene ayarlaniyor');
//
//        $this->couchINISet(
//            'httpd_global_handlers',
//            '_fti',
//            '{couch_httpd_proxy, handle_proxy_req, <<"http://127.0.0.1:5985">>}'
//        );
//
//        $this->couchRestart();
//    }
//
//    public function couchRestart()
//    {
//        return $this->serviceRestart('couchdb');
//    }


    public function nginxCreate($opts = ['frontend|f' => false, 'backend|b' => false])
    {
        if (!$opts['frontend']) {
            $this->createNginxFrontend();
            return;
        } else if (!$opts['backend']) {
            $this->createNginxBackend();
            return;
        }

        $this->say('--frontend|-f or --backend|-b required');
    }

    public function nginxInstallCloudflare()
    {
        $path = "/php7-2.conf";
        if ($this->nginxConfExists($path)) {
            $this->say('php7-2.conf already exists');
            return;
        }

        $conf = Scope::create();
        $real_ip_list = [
            '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '104.16.0.0/12',
            '108.162.192.0/18', '131.0.72.0/22', '141.101.64.0/18', '162.158.0.0/15',
            '172.64.0.0/13', '173.245.48.0/20', '188.114.96.0/20', '190.93.240.0/20',
            '197.234.240.0/22', '198.41.128.0/17', '2400:cb00::/32', '2606:4700::/32',
            '2803:f800::/32', '2405:b500::/32', '2405:8100::/32', '2c0f:f248::/32', '2a06:98c0::/29'
        ];

        foreach ($real_ip_list as $real_ip) {
            $conf->addDirective(Directive::create('set_real_ip_from', $real_ip));
        }

        $conf->addDirective(Directive::create('real_ip_header', 'CF-Connecting-IP'));
        $this->nginxConfSave($conf, $path);
    }

    public function nginxInstallPHP()
    {
        $path = "/conf.d/cloudflare.conf";
        if ($this->nginxConfExists($path)) {
            $this->say('cloudflare.conf already exists');
            return;
        }

        $conf = Scope::create();
        $conf->addDirective(Directive::create('fastcgi_pass', 'fpm_pool'));
        $conf->addDirective(Directive::create('fastcgi_index', '/index.php'));
        $conf->addDirective(Directive::create('include', 'fastcgi_params'));
        $conf->addDirective(Directive::create('fastcgi_split_path_info', '^(.+\.php)(/.+)$'));
        $conf->addDirective(Directive::create('fastcgi_param', 'PATH_INFO $fastcgi_path_info'));
        $conf->addDirective(Directive::create('fastcgi_param', 'PATH_TRANSLATED $document_root$fastcgi_path_info'));
        $conf->addDirective(Directive::create('fastcgi_param', 'SCRIPT_FILENAME $document_root$fastcgi_script_name'));
        $this->nginxConfSave($conf, $path);
    }

    public function nginxConfigurePool()
    {
        $pool_count = intval($this->ask("Pool count:"));
        if ($pool_count <= 0) {
            $this->say('pool count must greater than zero');
            return;
        }

        $path = "/conf.d/pool.conf";

        $pool_scope = Scope::create();
        for ($i = 0; $i < $pool_count; $i++) {
            $number = $i + 1;
            $pool_scope->addDirective(
                Directive::create('server', 'unix:/var/run/fpm' . $number . '.sock')
            );
        }

        $conf = Scope::create();
        $conf->addDirective(
            Directive::create('upstream', 'fpm_pool')->setChildScope($pool_scope)
        );

        $this->nginxConfSave($conf, $path);
    }

    private function createNginxBackend()
    {
        $app_name = $this->ask("Application name:");
        $path = '/sites-available/' . $app_name . '.conf';
        if ($this->nginxConfExists($path)) {
            $this->say('this application already exists');
            return;
        }

        $folder_name = $this->ask("Folder name:");
        $server_name = $this->ask("Server name");

        $scope = Scope::create()->addDirective(
            Directive::create('server')
                ->setChildScope(Scope::create()
                    ->addDirective(Directive::create('listen', '80'))
                    ->addDirective(Directive::create('listen', '[::]:80'))
                    ->addDirective(Directive::create('root', '/data/backend/' . $folder_name . '/public'))
                    ->addDirective(Directive::create('index', 'index.php'))
                    ->addDirective(Directive::create('server_name', $server_name))
                    ->addDirective(
                        Directive::create('location', '/')->setChildScope(Scope::create()
                            ->addDirective(Directive::create('try_files', '$uri $uri/ /index.php?_url=$uri&$args'))
                        ))
                    ->addDirective(Directive::create('location', '~ \.php$')->setChildScope(Scope::create()
                        ->addDirective(Directive::create('include', '/etc/nginx/php7-2.conf'))
                    ))));

        $this->nginxConfSave($scope, $path);
    }

    private function createNginxFrontend()
    {
        $app_name = $this->ask("Application name:");
        $path = '/sites-available/' . $app_name . '.conf';
        if ($this->nginxConfExists($path)) {
            $this->say('this application already exists');
            return;
        }

        $folder_name = $this->ask("Folder name:");
        $server_name = $this->ask("Server name");

        $scope = Scope::create()->addDirective(
            Directive::create('server')
                ->setChildScope(Scope::create()
                    ->addDirective(Directive::create('listen', '80'))
                    ->addDirective(Directive::create('listen', '[::]:80'))
                    ->addDirective(Directive::create('root', '/data/frontend/' . $folder_name . '/dist'))
                    ->addDirective(Directive::create('index', 'index.html'))
                    ->addDirective(Directive::create('server_name', $server_name))
                    ->addDirective(
                        Directive::create('location', '/')->setChildScope(Scope::create()
                            ->addDirective(Directive::create('try_files', '$uri $uri/ /index.html =404'))
                        ))));

        $this->nginxConfSave($scope, $path);
    }

    /**
     * @param string $path
     * @return Scope
     */
    private function nginxGetConf($path)
    {
        return Scope::fromFile(NGINX_DEFAULT_PATH . $path);
    }

    /**
     * @param Scope $scope
     * @param string $path
     */
    private function nginxConfSave($scope, $path)
    {
        $scope->saveToFile(NGINX_DEFAULT_PATH . $path);
    }

    private function nginxConfExists($path)
    {
        return file_exists(NGINX_DEFAULT_PATH . $path);
    }
}
