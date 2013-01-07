#!/usr/bin/php
<?php
$repo = "/var/www/gitphotos";
$output = Array();
$output2 = Array();
$status = 1;
$status2 = 1;
$i = 0;
$fileok = false;

exec("cd $repo && git status", $output, $status);
if($status == 128){
        exec("cd $repo && git init", $output, $status);
}
if($status == 0){
	for($i = 0, $size = count($output); $i < $size; ++$i) {
		if(strpos($output[$i],"Untracked files:")){
			$fileok = true;
		}
		if(strpos($output[$i],"nothing")){
			$fileok = false;
			break;
		}
		if((strpos($output[$i],".jpg") || strpos($output[$i],".png")) && $fileok){
			exec(sprintf('cd %s$1 && git annex add %s$2',$repo, $output[$i]),$output2, $status2);
			exec(sprintf('cd %s$1 && git commit -m "Added %s$2"',$repo,$output[$i]),$output2, $status2);
			
		}

	}

}

