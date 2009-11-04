<?php
/**
 * LetsCod
 *
 * @author Elie Andraos
 * @version 1.6.7
 */

include dirname(__FILE__)."/extern/openInviter/plugins/_base.php";

class LcOpenInviterPostInstall extends OpenInviter_Base {
    public function login($user,$pass) {
        return;
    }

    public function getMyContacts() {
        return;
    }

    public function logout() {
        return;
    }

    public function checkVersion() {
        $this->init();
        $res=$this->get("http://update.openinviter.com/updater/check_version.php?key={$this->settings['private_key']}");
        $this->stopPlugin();
        return $res;
    }

    public function check($url) {
        $this->init();
        $res=$this->get($url);
        $this->stopPlugin();
        if (empty($res)) return false; else return true;
    }

}
