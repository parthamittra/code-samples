<?php
namespace App\Services\Feeds;

use App\Facades\ActivityLog as LOG;
use Storage;
use League\Flysystem\Filesystem;
use League\Csv\Reader;


abstract class Insert{

    public $type;
    public $filename;
    public $date;


    public function __construct($type,$filename,$date=""){
        $this->type=$type;
        $this->filename=$filename;
        if($date != ""){
           $this->date=$date;
        }
     LOG::writeToLog(LOG::$INFO,'Insert:__construct(): '.'Insert base class instantiated');
    }


    public function readCSVandInsert($dir){
        $fullpath=$dir.'/'.$this->filename;
        $csv=Reader::createFromPath($fullpath);
        $headers=$this->validateHeaders($csv->fetchOne()); 
        $data=array();
        LOG::writeToLog(LOG::$INFO,'Insert:readCSVandInsert(): '.'The headers are :',$headers);
        foreach($csv as $index=>$row){
            for($i=0;$i<count($headers);++$i){ 
                $data[$headers[$i]]=$row[$i];
            }
          if($index !=0){
           LOG::writeToLog(LOG::$INFO,'Insert:readCSVandInsert(): '.'Data to be inserted from line '.$index,$data);
           $this->insertData($data);
          }
        } 
    
    }
    public abstract function insertData($data);
    protected abstract function validateHeaders($headers);



}
?>
