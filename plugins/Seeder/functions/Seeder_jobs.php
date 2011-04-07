<?php
//========================================================================================================================
//Seeder_jobs.php
//by N1n9u3m
//========================================================================================================================
//Seeder_before_harvest
//========================================================================================================================
function Seeder_end_job()//added v1.1.2
{

global $Seeder_settings, $Seeder_info;

$ActiveMission = Seeder_Read("ActiveMission");

 if ($ActiveMission['isComplete'] == 1)
 {
 $res = 0;
 $px_time = time();
 $amf = new AMFObject("");
 $amf->_bodys[0] = new MessageBody();
 $amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
 $amf->_bodys[0]->responseURI = '/1/onStatus';
 $amf->_bodys[0]->responseIndex = '/1';
 $amf->_bodys[0]->_value[0] = GetAMFHeaders();
 $amf->_bodys[0]->_value[2] = 0;

 $amf->_bodys[0]->_value[1][0]['params'][0] = false;
 $amf->_bodys[0]->_value[1][0]['params'][1] = Null;
 $amf->_bodys[0]->_value[1][0]['sequence'] = GetSequense();
 $amf->_bodys[0]->_value[1][0]['functionName'] = "SocialMissionService.onGetMissionComplete";

 $res = RequestAMF($amf);
 AddLog2("Seeder_end_job> Ending Complete Job result: ".$res);
 #SaveAuthParams();

 } else {//if ($ActiveMission['isComplete'] == 1)
 AddLog2("Seeder_end_job> no Job Complete");
 }

}
//========================================================================================================================
