<?php

namespace Fabstract\Installer;

use \Fabstract\INI\INI;
use \Fabstract\INI\Line;
use \Fabstract\INI\Constant\LineTypes;

define('COUCH_LOCAL_INI', '/etc/couchdb/local.ini');

class CouchInstaller extends BaseInstaller
{
    public function couchInstall()
    {
        $this->say('couchdb kuruluyor');
        $response = $this->aptInstallPackage('couchdb');
        if ($response) {
            $this->makeDir('/data/couchdb');

            chown('/data/couchdb', 'couchdb');
            $this->couchINISet(
                'httpd',
                'bind_address',
                '0.0.0.0'
            );

            $this->couchINISet(
                'httpd',
                'port',
                '5984'
            );

            $this->couchINISet(
                'couchdb',
                'database_dir',
                '/data/couchdb'
            );

            $this->couchINISet(
                'couchdb',
                'view_index_dir',
                '/data/couchdb'
            );

            $this->couchRestart();
        } else {
            $this->say("Kurulum tamamlanamadi!");
        }
    }

    public function couchAuthSet()
    {
        $this->say('couch auth ayarlaniyor');

        $this->couchINISet(
            'httpd',
            'WWW-Authenticate',
            'Basic realm="administrator"'
        );

        $this->couchINISet(
            'couch_httpd_auth',
            'require_valid_user',
            'true'
        );

        $this->couchRestart();
    }

    public function couchCompactSet()
    {
        $this->say('couch compact ayarlaniyor');

        $this->couchINISet(
            'daemons',
            'compaction_daemon',
            '{couch_compaction_daemon, start_link, []}'
        );

        $this->couchINISet(
            'compaction_daemon',
            'check_interval',
            '300'
        );

        $this->couchINISet(
            'compaction_daemon',
            'min_file_size',
            '131072'
        );

        $this->couchINISet(
            'compactions',
            '_default',
            '[{db_fragmentation, "70%"}, {view_fragmentation, "60%"}]'
        );

        $this->couchRestart();
    }

    public function couchLuceneSet()
    {
        $this->say('couch lucene ayarlaniyor');

        $this->couchINISet(
            'httpd_global_handlers',
            '_fti',
            '{couch_httpd_proxy, handle_proxy_req, <<"http://127.0.0.1:5985">>}'
        );

        $this->couchRestart();
    }

    public function couchRestart()
    {
        return $this->serviceRestart('couchdb');
    }

    private function couchINISet($section_name, $key, $value)
    {
        $ini = $this->couchGetINI();

        $setting_line = $ini->getSettingLine($section_name, $key);
        if ($setting_line === null) {
            $section_line = $ini->getSectionLine($section_name);
            if ($section_line === null) {
                $section_line = Line::create(LineTypes::SECTION, $section_line);
                $first_line = $ini->getFirstLine();
                if ($first_line !== null) {
                    $first_line->insertBefore($section_line);
                } else {
                    $ini->setFirstLine($section_line);
                }
            }

            $section_line->insertAfter(Line::create(
                LineTypes::SETTING,
                $value,
                $key
            ));
        } else {
            $setting_line->setValue($value);
        }

        $this->couchINISave($ini);
    }

    /**
     * @return INI
     */
    private function couchGetINI()
    {
        return INI::fromFile(COUCH_LOCAL_INI);
    }

    /**
     * @param INI $ini
     */
    private function couchINISave($ini)
    {
        $ini->write(COUCH_LOCAL_INI);
    }
}
