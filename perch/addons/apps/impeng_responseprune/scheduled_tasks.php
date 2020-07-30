<?php
use impeng\responseprune\Purge;

// load Perch Forms classes
spl_autoload_register(function($class_name){
    if (strpos($class_name, 'PerchForms_')===0) {
        include(PERCH_PATH.'/addons/apps/perch_forms/'.$class_name.'.class.php');
        return true;
    }
    return false;
});
// load  impeng_responseprune classes
include(__DIR__.'/lib/'.'Purge.php');

$API  = new PerchAPI(1.0, 'impeng_responseprune');
$Settings = $API->get('Settings');
$runMinutes = $Settings->get('impeng_responseprune_runDays')->val() * 1440;
if ($runMinutes != "") {
    // For testing uncomment the following line
    // $runMinutes = 1;

    PerchScheduledTasks::register_task('impeng_responseprune', 'Form Response Pruning', $runMinutes, 'impeng_response_prune');
    function impeng_response_prune($last_run_date)
    {
        $API  = new PerchAPI(1.0, 'impeng_responseprune');
        $Settings = $API->get('Settings');
        $Lang = $API->get('Lang');

        $Forms = new PerchForms_Forms($API);
        $forms = $Forms->all();
        $purgeList = new Purge();

        foreach($forms as $Form) {
            if (json_decode($Form->formOptions())->store == 1) {
                $purgeDaysSpam = $Settings->get('impeng_responseprune_purgeDays_spam_'.$Form->formID())->val();
                $purgeDaysNotSpam = $Settings->get('impeng_responseprune_purgeDays_notspam_'.$Form->formID())->val();
                if ($purgeDaysSpam != "" || $purgeDaysNotSpam != "") {
                    $now = new DateTime();
                    $Responses = new PerchForms_Responses($API);
                    $responses = $Responses->get_by('formID',$Form->formID(),'responseCreated');
                    if (!empty($responses)) {
                        foreach ($responses as $Response) {
                            $responseDate = new DateTime($Response->responseCreated());
                            $isSpam = $Response->responseSpam();
                            if ($isSpam == "1" && $purgeDaysSpam != "") {
                                if ($responseDate->diff($now)->days > $purgeDaysSpam) {
                                    $purgeList->deleteResponse($Response);
                                }
                            }
                            if ($isSpam == "0" && $purgeDaysNotSpam != "") {
                                if ($responseDate->diff($now)->days > $purgeDaysNotSpam) {
                                    $purgeList->deleteResponse($Response);
                                }
                            }                  
                        }
                    }
                }
            }
        }

        \PerchUtil::debug("impeng_responseprune: ".$purgeList->getTextSummary(), 'log');

        // If we have deleted any responses
        if ($purgeList->getDeletedCount() != 0 ) {
            // Get notification settings
            $notify = $Settings->get('impeng_responseprune_notify')->val();
            $notifyEmail = $Settings->get('impeng_responseprune_notify_email')->val();

            // Send Email
            if ($notify != "" && $notifyEmail != "") {
                $email = New PerchAPI_Email(1.0, 'impeng_responseprune', $Lang); 
                $email->set_template("report.html", "email");
                $email->set("domain", htmlspecialchars(@$_SERVER['SERVER_NAME']) );
                $email->set("today", date('l jS \of F Y'));
                $email->set("deletedCount", $purgeList->getDeletedCount());
                $email->set("firstDate", $purgeList->getFistDate());
                $email->set("lastDate", $purgeList->getLastDate());
                $email->set("fullList", $purgeList->getFullList());
                $email->subject("Form Responses Prune Results for" . htmlspecialchars(@$_SERVER['SERVER_NAME']) );
                $email->recipientEmail($notifyEmail);
                $email->senderName(PERCH_EMAIL_FROM_NAME);
                $email->senderEmail(PERCH_EMAIL_FROM);
                $email->send();
            }
        }

        return array(
            'result'=>'OK',
            'message'=> $purgeList->getAdminMessage()
        );
    }
}