<?php
/**
 * LetsCod
 *
 * @author Elie Andraos
 * @version 1.6.7
 */

include_once dirname(__FILE__)."/extern/openInviter/autoupdate.php";

class LcOpenInviterAutoUpdate extends update {

    public function makeUpdate() {
        $xml=$this->checkVersions();
        if (!empty($xml)) {
            $update_files=$this->parseXmlUpdates($xml);
            $update=true;
            $newFiles=array();
            
            foreach($update_files as $name_file=>$arrayfile)
            {
                if ($arrayfile['type']=='new')
                {
                    $newOnes[$name_file] = $arrayfile;
                    if(!empty($this->settings['update_files']))
                    {
                      if (isset($this->plugins[$arrayfile['plugin_type']][$name_file]))
                      {
                          if (!empty($this->plugins[$arrayfile['plugin_type']][$name_file]['autoupdate']))
                          {
                              $newFiles[$name_file]=array('sum'=>$arrayfile['sum'],'plugin_type'=>$arrayfile['plugin_type']);
                          }
                          elseif($arrayfile['plugin_type']=='base')
                          {
                              $newFiles[$name_file]=array('sum'=>$arrayfile['sum'],'plugin_type'=>$arrayfile['plugin_type']);
                          }
                      }
                    } 
                    else
                    {
                       $newFiles[$name_file]=array('sum'=>$arrayfile['sum'],'plugin_type'=>$arrayfile['plugin_type']);
                    }
                  
                }
                
            }
            
            // $newFiles means the files needed to be updated (key of array refers to files)
            foreach ($newFiles as $name_file=>$arrayFile) {
                $headers=array('Content-Type'=>'application/xml','X_USER'=>$this->settings['username'],'X_SIGNATURE'=>$this->makeSignature($this->settings['private_key'],$this->xmlFile($name_file)));
                $res = $this->getNewFile(gzcompress($this->xmlFile($name_file),9),$headers);

                if (!empty($res)) {
                    $fileDeCmp        = gzuncompress($res);
                    $elementsDownload = $this->getElementsDownload($fileDeCmp);
                    $file_content     = $elementsDownload['fileStrip'];
                    $signatureBulk    = $elementsDownload['signatureBulk'];
                    $this->verifySignature($signatureBulk,$file_content);
                    if($arrayFile['sum'] != md5($file_content))
                        $update = false;
                    elseif (!file_put_contents($this->getUpdateFilePath($name_file).".tmp",$file_content))
                        $this->ers("Unable to write new updates");
                }
                else
                    $update=false;
            }

            if ($update) {
                foreach($newFiles as $name_file=>$arrayfile) {
                    file_put_contents($this->getUpdateFilePath($name_file),file_get_contents($this->getUpdateFilePath($name_file).".tmp"));
                    unlink($this->getUpdateFilePath($name_file).".tmp");
                    $this->writeConf($name_file,$arrayfile['plugin_type']);
                }
                $this->array2Log($update_files);
            }
            else {
                foreach($newFiles as $name_file=>$arrayfile)
                    if(file_exists($this->getUpdateFilePath($name_file).".tmp"))
                        unlink($this->getUpdateFilePath($name_file).".tmp");
                if(!$update)
                    $this->ers("Unable to download updates");
            }
        }
        else
            $this->ers("Unable to connect to Server");
    }


    private function makeSignature($var1,$var2) {
        return md5(md5($var1).md5($var2));
    }

    private function verifySignature($signatureBulk,$fileContent) {
        if (strpos($signatureBulk,'X_SIGNATURE:')===false) $this->ers("INVALID SIGNATURE");
        else {
            $start=strpos($signatureBulk,'X_SIGNATURE:')+strlen('X_SIGNATURE:');$end=strlen($signatureBulk);
            $signature=trim(substr($signatureBulk,$start,$end-$start));
            $signature_check=$this->makeSignature($this->settings['private_key'],$fileContent);
            if($signature!=$signature_check) $this->ers("Invalid SIGNATURE");
            else return true;
        }
    }

    protected function getUpdateFilePath($plugin) {
        if ($plugin=='openinviter' ) return dirname(__FILE__)."/extern/openInviter/{$plugin}.php"; // OR $plugin=='_base'
        else if( $plugin=='_base' ) return dirname(__FILE__)."/extern/openInviter/plugins/{$plugin}.php";
        else return dirname(__FILE__)."/extern/openInviter/plugins/{$plugin}.plg.php";
    }


    public function writeConf($name_file,$type=false) {
        if (!file_exists(dirname(__FILE__)."/extern/openInviter/conf"))
            mkdir(dirname(__FILE__)."/conf",0755,true);
        if ($type=='social') {
            if (!file_exists(dirname(__FILE__)."/extern/openInviter/conf/{$name_file}.conf"))
                file_put_contents(dirname(__FILE__)."/extern/openInviter/conf/{$name_file}.conf",'<?php $enable=true;$autoUpdate=true;$messageDelay=1;$maxMessages=10;?>');
        }
        elseif($type=='email') {
            if (!file_exists(dirname(__FILE__)."/extern/openInviter/conf/{$name_file}.conf"))
                file_put_contents(dirname(__FILE__)."/extern/openInviter/conf/{$name_file}.conf",'<?php $enable=true;$autoUpdate=true; ?>');
        }
    }


    private function array2Log($array) {
        $date=date("Y-m-d H:i:s");$updateCount=0;
        $string="[$date] UPDATE STARTED\r\n";
        foreach($array as $key=>$values) if ($values['type']=='new') { $string.="\tUPDATED: {$key}.php\r\n";$updateCount++; }
        $string.="\tUPDATE DONE. {$updateCount} FILES UPDATED\r\n";
        $this->writeLog($string);
        autoUpdateOpenInvierTask::getInstance()->displayLogs($array);
    }


}
