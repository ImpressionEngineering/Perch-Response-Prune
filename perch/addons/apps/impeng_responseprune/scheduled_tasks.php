<?php
    // run once every 24 hours (1440 mins)
    PerchScheduledTasks::register_task('impeng_responseprune', 'Form Response Pruning', 1440, 'impeng_response_prune');

    function impeng_response_prune($last_run_date)
    {
        $API  = new PerchAPI(1.0, 'impeng_responseprune');
        $Settings = $API->get('Settings');
        $Lang = $API->get('Lang');

        // load Perch Forms classes
        spl_autoload_register(function($class_name){
            if (strpos($class_name, 'PerchForms_')===0) {
                include(PERCH_PATH.'/addons/apps/perch_forms/'.$class_name.'.class.php');
                return true;
            }
            return false;
        });

        $Forms = new PerchForms_Forms($API);
        $forms = $Forms->all();

        $count = 0;
        $deletedIds = "";

        foreach($forms as $Form) {
            if (json_decode($Form->formOptions())->store == 1) {
                $purgeDays = $Settings->get('impeng_responseprune_purgeDays_'.$Form->formID())->val();
                if ($purgeDays != "") {
                    $now = new DateTime();
                    $Responses = new PerchForms_Responses($API);
                    $responses = $Responses->get_by('formID',$Form->formID(),'responseCreated');
                    foreach ($responses as $Response) {
                        $responseDate = new DateTime($Response->responseCreated());
                        if ($responseDate->diff($now)->days > $purgeDays) {
                            $count ++;
                            //add separator if not first item
                            if ($count != 1) {
                                $deletedIds .= ", ";
                            }
                            // add this ID to list
                            $deletedIds .= $Response->responseID();
                            // delete the response
                            $Response->delete();
                        }
                    }
                }
            }
        }
        if ($count != 0) {
            // Add summary to start of result message
            $messageIntro = $Lang->get('Deleted %s responses with IDs: ', $count);
            $message = $messageIntro.$deletedIds;

            // Result message is restricted to 256 characters, if longer truncate and add ellipsis.
            if (strlen($message) > 255) {
                $message = substr($message,0,250);
                $lastComma = strrpos($message, ",");
                $message = substr($message,0,$lastComma)." ... ";
            }
            return array(
                'result'=>'OK',
                'message'=> $message
            );
        } else {
            //nothing deleted
            return array(
                'result'=>'OK',
                'message'=> $Lang->get('Nothing deleted.')
            );
        }
    }
