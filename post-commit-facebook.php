<?php

# include configuration file
require_once(__DIR__.'/config.core.php');
require_once(__DIR__.'/config.user.php');

# Copy dir contents
function copyContents($source, $dest) {
	$sourceHandle = opendir($source);
	if (!is_dir($dest)) mkdir($dest, 0777, true);
	
	while($res = readdir($sourceHandle)) {
		if ($res == '.' || $res == '..') continue;
		
		if(is_dir($source . '/' . $res)) {
			copyContents($source . '/' . $res, $dest . '/' . $res);
		}
		else copy($source . '/' . $res, $dest . '/' . $res);
	}
}
function file_write($file, $payload)
{
    $fh = fopen($file, 'a') or die("can't open file");
    $payload = $payload."\n";
    fwrite($fh, $payload);
    fclose($fh);
}

/**
 * STEP 0: Preconfiguration
 * - check IP which request this script, 
 * - create temporary dir for repo caching
 */
$sync_task = array();
$error = '';
$log = '------------------------------------------------------------------------------------------'.PHP_EOL.date('Y-m-d H:i:s').PHP_EOL.'Sync started'.PHP_EOL.PHP_EOL;

$log .= 'REQUEST:'.PHP_EOL.print_r($_REQUEST, 1).PHP_EOL.PHP_EOL.PHP_EOL;

# Check requests by ip address
$log .= '------------------------------ IP check'.PHP_EOL;
if (empty($error))
{
    $log .= PHP_EOL.'------------------------------ Repo check'.PHP_EOL;
}
if (empty($error) && !empty($_REQUEST['payload']))
{
	$log .= 'Payload encoded:'.PHP_EOL.print_r($_REQUEST['payload'],1).PHP_EOL;
	$payload = json_decode($_REQUEST['payload'], true);
	$log .= PHP_EOL.'Payload decoded:'.PHP_EOL.print_r($payload,1).PHP_EOL;

	# check if specific variable is exists in loaded payload
	if (!empty($payload))
    {
		# check if we have a config file for current repository
		# from submited data
		# need to be like $repo_conf [ repo_url ];
		if (isset($repo_conf[@$payload['repository']['url']]))
        {
			if (strpos($payload['ref'], $repo_conf[$payload['repository']['url']]['branch']) !== false)
            {
				$log .= 'SYNC CONFIG FOUND'.PHP_EOL.PHP_EOL;
				$sync_conf = &$repo_conf[$payload['repository']['url']];
			}
			else
            {
                $error = 'Commit not to "'.$repo_conf[$repo_conf[$payload['repository']['url']]].'" branch. Ignore';
            }
		}
		else
        {
            $error = 'Post-commit webhook not configured for sync "'.$payload['repository']['url'].'" repository';
        }
        /*
            Adding Code to filter the JSON and setup for posting to facebook later.
        */
        $file1 = "/var/www/heihachi/saved/commit.log";
        $commit = $payload['commits']['0']['id'];
        $message = $payload['commits']['0']['message'];
        $time = $payload['commits']['0']['timestamp'];
        $commiter = $payload['commits']['0']['author']['username'];
        $added = 0;
        $modified = 0;
        $removed = 0;
        foreach($payload['head_commit']['added'] as $added)
        {
            $added += 1;
        }
        foreach($payload['head_commit']['modified'] as $modified)
        {
            $modified += 1;
        }
        foreach($payload['head_commit']['removed'] as $removed)
        {
            $removed += 1;
        }
        //$commiter = $payload['commits']['author']['name'];
        $commitstring = "Hash: ".$commit."\n";
        $commitstring .= "Pushed at ".$time."\n";
        $commitstring .= "User    : ".$commiter."\n";
        $commitstring .= "Message : ".$message."\n";
        if($added > 0)
        {
            $commitstring .= "Added   : ".$added."\n";
        }
        if($modified > 0)
        {
            $commitstring .= "Modified: ".$modified."\n";
        }
        if($removed > 0)
        {
            $commitstring .= "Removed : ".$removed."\n";
        }
        file_write($file1, $commitstring);
        //$debug = var_dump($payload);
        //file_write($file1, $debug);

	}
	else
    {
        $error = 'Can\'t decode payload variable';
    }
}
else
{
    if (empty($error))
    {
        $error = 'Payload variable does not exist or empty';
    }
}

# Log and report
if (!empty($error)) $log .= '**ERROR** '.$error.PHP_EOL;
else $log .= '** SYNC FINISHED **'.PHP_EOL;

$file2 = "/var/www/heihachi/github-webhook/payload.txt";
file_write($file2, $log);
echo $log;
?>
<form method="post" action="post-commit-facebook.php">
<input type="text" name="payload"/><br/>
<input type="submit" name="submit"/>
</form>
