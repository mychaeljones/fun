<?php

class Application_Model_File {

    public function __construct() {
        $this->_db = Zend_Registry::get('db');
    }

    public function getFileById($id) {
        return $this->_db->fetchRow("SELECT * from files WHERE file_id = '$id' ");
    }
    
    public function get_real_size($file) {
        clearstatcache();
        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            if (class_exists("COM")) {
                $fsobj = new COM('Scripting.FileSystemObject');
                $f = $fsobj->GetFile(realpath($file));
                $ff = $f->Size;
            } else {
                $ff = trim(exec("for %F in (\"" . $file . "\") do @echo %~zF"));
            }
        } elseif (PHP_OS == 'Darwin') {
            $ff = trim(shell_exec("stat -f %z " . escapeshellarg($file)));
        } elseif ((PHP_OS == 'Linux') || (PHP_OS == 'FreeBSD') || (PHP_OS == 'Unix') || (PHP_OS == 'SunOS')) {
            $ff = trim(shell_exec("stat -c%s " . escapeshellarg($file)));
        } else {
            $ff = filesize($file);
        }

        /** Fix for 0kb downloads by AlanReiblein */
        if (!ctype_digit($ff)) {
            /* returned value not a number so try filesize() */
            $ff = filesize($file);
        }

        return $ff;
    }

}