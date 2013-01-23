#!/usr/bin/php
<?php
$path = "";
$output = Array();
$status = 1;
if(count($argv) != 2){
	echo "Usage: git-annex-setup.php path\nParameter 'path' refers to the path to your open-photo directory root";
	exit;
}
$path = "$argv[1]/src/html/photos/original";
exec("cd $path",$output, $status);
if ($status != 0){
	echo "Given path is not valid.\n Please make sure it points to the root of your open-photo installation\nand run git-annex-setup again.\n";
	exit;
}
unset($output);
$status = 1;
exec("cd $path; git config --global user.name 'openPhoto';git config --global user.email 'photo@openphoto.com'; git init; git commit -m 'Initial git commit' --allow-empty; git annex init open-photo; git branch photoView; git branch albumView; git checkout photoView",$output,$status);
if($status != 0){
	echo "Initialization failed.\n Please make sure git and git-annex are installed and run git-annex-setup again.\n";
	exit;
} 
else{
	echo "Initialization successfull.\n";
}
unset($output);
$status = 1;
exec("chown www-data:www-data $path", $output, $status);
if($status != 0){
	echo "www-data could not be set to be the owner.\nPlease check apache is installed.\n";
	exit;
} 
else{
	echo "Changed the owner to www-data.\n";
}
