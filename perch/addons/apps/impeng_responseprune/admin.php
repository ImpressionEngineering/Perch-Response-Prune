<?php

   	$this->register_app('impeng_responseprune', 'Form Response Pruning ( WARNING: This will permanently DELETE your form responses )', 9, 'Perch addon to prune form responses from the database', '0.2', true);
    $this->require_version('impeng_responseprune', '3.0');
	
	$API  = new PerchAPI(1.0, 'impeng_responseprune');
	$Lang = $API->get('Lang');

	spl_autoload_register(function($class_name){
		if (strpos($class_name, 'PerchForms_')===0) {
	        include(PERCH_PATH.'/addons/apps/perch_forms/'.$class_name.'.class.php');
	        return true;
	    }
	    return false;
	});

	$pruneOpts = array();
		$pruneOpts[] = array('label'=>$Lang->get('Never') , 'value'=>'');
		$pruneOpts[] = array('label'=>$Lang->get('One Day') , 'value'=> 1);
		$pruneOpts[] = array('label'=>$Lang->get('One Week') , 'value'=>7);
        $pruneOpts[] = array('label'=>$Lang->get('Two Weeks') , 'value'=>14);
		$pruneOpts[] = array('label'=>$Lang->get('One Month') , 'value'=>30);
        $pruneOpts[] = array('label'=>$Lang->get('Two Months') , 'value'=>60);
        $pruneOpts[] = array('label'=>$Lang->get('Three Months') , 'value'=>90);
        $pruneOpts[] = array('label'=>$Lang->get('Six Months') , 'value'=>180);
		$pruneOpts[] = array('label'=>$Lang->get('One Year') , 'value'=>365);
		$pruneOpts[] = array('label'=>$Lang->get('Two Years') , 'value'=>730);

	if (class_exists('PerchForms_Forms')) {
		$Forms = new PerchForms_Forms($API);
		$forms = $Forms->all();
		foreach($forms as $Form) {
			if (json_decode($Form->formOptions())->store == 1) {
				$this->add_setting('impeng_responseprune_purgeDays_'.$Form->formID(), $Form->formTitle().$Lang->get(' - delete form responses older than:'), 'select', '', $pruneOpts);
			}
		}
	} else {
		error_log("ImpEng Response Prune requires Perch Forms to be installed");
	} 


