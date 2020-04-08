<?php
namespace App\Services\Feeds\Subscriber;

use App\Models\Subscriber;
use App\Services\Feeds\Insert;
use App\Facades\ActivityLog as LOG;



class SubscriberInsert extends Insert{

    public $IdSet;
    public $IdSubscriber;
    public function __construct($type,$filename,$date){
        parent::__construct($type,$filename,$date);
        $this->IdSet=array();
        $this->IdSubscriber=array();
    }

    public function readCSVandInsert($dir){
        parent::readCSVandInsert($dir);
    }

    public function insertData($data){
        print_r($data);
        $subscriber=Subscriber::firstOrNew(['member_id'=>$data['member_id']]);
        $subscriber->id=$data['plexis_member_id'];
        $subscriber->subscriber_id=$data['subscriber_id'];
        $subscriber->first_name=$data['first_name'];
        $subscriber->last_name=$data['last_name'];
        $subscriber->address1=$data['address1'];
        $subscriber->address2=$data['address2'];
        if(isset($data['city'])){
            $subscriber->city=$data['city'];
        }
        if(isset($data['state'])){
            $subscriber->state=$data['state'];
        }
        if(isset($data['zip'])){
            $subscriber->zip=$data['zip'];
        }
        if(isset($data['email'])){
            $subscriber->email=$data['email'];
        }
        if(isset($data['phone'])){
            $subscriber->phone=$data['phone'];
        }
        if(isset($data['dob'])){
            $date=\DateTime::createFromFormat('M j Y',$data['dob']);
            LOG::writeToLog(LOG::$ERROR,'SubscriberInsert:InsertData()->dob : Date Time errors',\DateTime::getLastErrors());
            $dt=$date->format('Y-m-d');
            $subscriber->dob=$dt;

        }
        if(($data['end_date'] != "")){
            $date=\DateTime::createFromFormat('Y-m-d H:i:s',$data['end_date']);
            LOG::writeToLog(LOG::$ERROR,'SubscriberInsert:InsertData()->end_date : Date Time errors',\DateTime::getLastErrors());
            $dt=$date->format('Y-m-d');
            $end_date=$dt;
        }else{
            $end_date='2016-12-31';
        }
        if(($data['effective_date'] !="")){
            $date=\DateTime::createFromFormat('Y-m-d H:i:s',$data['effective_date']);
            LOG::writeToLog(LOG::$ERROR,'SubscriberInsert:InsertData()->effective_date : Date Time errors',\DateTime::getLastErrors());
            $today=date('Y-m-d H:i:s');
            if($today > $date){
                $effective_date=$today;
            }else{
            $dt=$date->format('Y-m-d');
            $effective_date=$dt;
            }
        }else{
            $effective_date='2016-01-01';
        }
        $subscriber->gender=$data['gender'];
        $subscriber->member_id=$data['member_id'];
        $subscriber->is_enrolled=$data['is_enrolled'];
//        $subscriber->plan_id=$data['plan_id'];
        $subscriber->employer_id=$data['employer_id'];
        $subscriber->secondary_id=$data['secondary_id'];
        $subscriber->marital_status=$data['marital_status'];
        $subscriber->is_active=$data['is_active'];
        if($subscriber->trashed()){
             $subscriber->restore();
        }else{
            $subscriber->save();
        }
        $date=new \DateTime();
        $this->makeSet($data['subscriber_id'],$data['plan_id']);
        $this->setPlexisMemberID($data['plexis_member_id']);
        if($data['plan_id'] >0 ){
            $subscriber->plans()->attach([$subscriber->id => ['plan_id'=>$data['plan_id'],'subscriber_id'=>$data['plexis_member_id'],'start_date'=>$effective_date, 'end_date'=>$end_date]]);
        }else {//plan id <=0
            if($this->inSet($data['subscriber_id'].$data['plan_id'])){
                //lookup and insert
                $subscriber->plans()->attach([$subscriber->id => ['plan_id'=>$this->lookupPlanId($data['group_contract_ud']),'subscriber_id'=>$data['plexis_member_id'],'start_date'=>$effective_date, 'end_date'=>$end_date]]);
        }
       }
        //check to see if any ommissions in file
    }
    public function removeDiffs(){
        $softDeletedIDs=array();
        $softDeletedIDs=array_diff($this->getSubscriberIDS(),$this->getPlexisMemberID());
        foreach($softDeletedIDs as $softDeletedID){
            //$sub=Subscriber::destroy($softDeletedID);
            $sub=Subscriber::find($softDeletedID);
            $sub->forceDelete(); 
        } 
    }
    private function makeSet($member_id,$plan_id){
        $id=$member_id.$plan_id;
        array_push($this->IdSet,$id); 
        $this->IdSet=array_unique($this->IdSet);
    } 
    private function inSet($id){
        if(array_search($id,$this->IdSet) != FALSE){
           return TRUE;
        }
        return FALSE;
    }
    private function lookupPlanId($groupid){
        echo "in lookup.";
        $row=\DB::table('plans')->where('group_code',$groupid)->first();
        return $row->id;

    }

    private function getSubscriberIDS(){
        $ids=array();
        $rows=\DB::table('subscribers')->get();
        foreach ($rows as $row){
            array_push($ids,$row->id);
        } 
        return $ids;
    }
    private function setPlexisMemberID($member_id){
      array_push($this->IdSubscriber,$member_id);
    }
    private function getPlexisMemberID(){
        return $this->IdSubscriber;
    }

    protected function validateHeaders($headers){
        $new_headers=array();
        for($i=0;$i<count($headers);++$i){
            $new_headers[$i]=$this->substituteHeaders($headers[$i]);
        }
        return $new_headers;
    }
    protected function substituteHeaders($name){
            $name=strtolower($name);
            switch($name){
                case 'address_1':
                    return 'address1';
                    break;
                case 'address_2':
                    return 'address2';
                    break;
                case 'patient_id':
                    return 'member_id';
                    break;
               case 'employee_id':
                   return 'secondary_id';
                   break;
              default:
                   return $name;
            }
    }
}



?>
