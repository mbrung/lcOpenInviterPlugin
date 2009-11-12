<?php
/**
 * LetsCod
 *
 * @author Elie Andraos
 * @version 1.6.7
 */

class autoUpdateOpenInvierTask extends sfBaseTask {
    static $instance;

    static public function getInstance() {
        if(!isset(self::$instance)) {
            throw new Exception('Cannot call instance before task has been configured.');
        }

        return self::$instance;
    }

    protected function configure() {

        self::$instance = $this;

        $this->namespace        = 'open-inviter';
        $this->name             = 'auto-update';
        $this->briefDescription = 'This task updates the open inviter classes';
        $this->detailedDescription = <<<EOF
            The [letscod:install-open-inviter|INFO] updates the open inviter classes.
Call it with:

  [php symfony letscod:auto-update-open-inviter|INFO]
EOF;
    }

    protected function execute($arguments = array(), $options = array()) {
        $open_inviter_dir = dirname(__FILE__)."/../extern/openInviter";
        $plugins_dir      = dirname(__FILE__)."/../extern/openInviter/plugins";

        // start log
        $this->logSection('Start', 'Running the auto-updates make take a while, please be patient!');

        $misconfig = false;
        if(!self::isInstalled()) $misconfig = true;
        if(!self::checkWritable($open_inviter_dir, "openInviter")) $misconfig = true;
        if(!self::checkWritable($plugins_dir, "openInviter/plugins")) $misconfig = true;

        if(!$misconfig) self::runUpdates();
    }


     /*
      * function that checks if a folder or directory is writable
      * @params $path
      * @return boolean
      */
    public function checkWritable($path,$name) {
        if (!is_writable($path)) {
            $this->logSection('Writable....NOT OK!', $name.' folder is not writable. Updates will not be posible');
            return false;
        }
        else {
            $this->logSection('Writable....OK!', $name.' folder is writable.');
            return true;
        }
    }


     /*
      * function that checks if the open inviter is installed before running the updates
      * @return boolean
      */
    public function isInstalled() {
        if(is_file(sfConfig::get('sf_plugins_dir')."/lcOpenInviterPlugin/lib/installation-complete.dat")) {
            $this->logSection('Checking installation....OK!', 'Open inviter is installed correctly on your system');
            return true;
        }
        else {
            $this->logSection('Checking installation....NOT OK!', 'You have to install the open-inviter before running the updates');
            return false;
        }
    }

     /*
      * function that runs the updates
      */
    public function runUpdates() {
        include dirname(__FILE__)."/../lcopeninviter.class.php";
        $inviter = new lcOpenInviter();
        $plugins = $inviter->getPlugins(true);
        
        $files_base['base']= array('openinviter'=>array('name'=>'openinviter','version'=>$inviter->getVersion()), '_base'=>array('name'=>'_base','version'=>$inviter->getVersion()));
        $update  = new LcOpenInviterAutoUpdate();
        
        $update->settings=$inviter->settings;
        $update->plugins=(!empty($plugins)?array_merge($files_base,$plugins):$files_base);
        $update->service_user='updater';
        $update->service_pass='updater';
        $update->service='updater';
        
        $update->makeUpdate();
    }

      /*
       * function that displays the update logs
       */
    public function displayLogs($array) {
        $updateCount = 0;
        if(is_array($array)) {
            $this->logSection('Update status', 'update started at '.date("d-m-Y H:i"));
            foreach($array as $key=>$values)
                if ($values['type']=='new') {
                    $this->logSection('Updated filed', $key.'.php');
                    $updateCount++;
                }

            if($updateCount == 0)
                $this->logSection('Update done', 'all your files are updated');
            else
                $this->logSection('Update done', $updateCount.' files updated');
        }
    }


}
