<?php
/**
 * GitAnnex adapter
 */
class FileSystemLocalGitAnnex extends FileSystemLocal implements FileSystemInterface
{
  private $config;
  private $repo;
  private $name;
  private $urlBase;

  public function __construct($config = null)
  {
    parent::__construct();
    if(is_null($config))
      $this->config = getConfig()->get();
    else
      $this->config = $config;
      
    $fsConfig = getConfig()->get('gitannex');
    $this->repo = $fsConfig->gitAnnexRepoPath;
    $this->name = $fsConfig->gitAnnexRepoName;
    $this->root = getConfig()->get('localfs')->fsRoot;
  }

  public function deletePhoto($photo)
  {
/*
    foreach($photo as $key => $value)
	{
	    if(strncmp($key, 'path', 4) === 0) {
		    $remoteFile = $value;
		    $path = self::normalizePath($value);
		    
		    if(strpos($remoteFile, '/original/') !== false) {
			    $output = Array();
			    $status = 1;
			    getLogger()->info("Removing $remoteFile from git-annex");
			    exec(sprintf('cd %1$s ; git annex drop --force %2$s ',$this->repo, $path),$output, $status);
			    if(file_exists($path)){
				    return false;
			    }
			    $status = 1;
			    unset($output);
			    exec("cd $this->repo && git commit -m \'Delete $path\'", $output, $status);
			    if($status != 0){
				    return false;
			    }
		    }
		    else      
		    {
			    if(strncmp($key, 'path', 4) === 0) {
				    $path = self::normalizePath($value);
				    if(file_exists($path) && !@unlink($path))
					    return false;
			    }
		    }
	    }
	    
	}
    return true;
    */
	  $output = Array();
	  $status1 = 1;
	  $status2 = 1;
	  $status3 = 1;
	  $status4 = 1;
	  foreach($photo as $key => $value)
	  {
		  if(strncmp($key, 'path', 4) === 0) {
			  $path = self::normalizePath($value);
			  if(strpos($value, '/original/') !== false){
				  exec("cd $this->repo && git annex drop --force $path",$output, $status1);
				  exec("cd $this->repo && git rm $path",$output, $status2);
				  exec(sprintf('cd %s$1 && git commit -m "Deleted %s$2"',$this->repo,$path),$output, $status3);
				  
				  if( $status1 != 0 || $status2 != 0 || $status3 != 0 )
					  return false;
			  }
			  else{
				  if(file_exists($path) && !@unlink($path))
					  return false;
			  }
		  }
	  }
	  return true;
  }

  public function downloadPhoto($photo)
  {
  	$url = $photo['pathOriginal'];
  	$path = str_replace("http://" . $this->host, $this->repo, $url);
    $fp = fopen($path, 'r');
    return $fp;
  }

  /**
    * Gets diagnostic information for debugging.
    *
    * @return array
    */
  public function diagnostics()
  {
    throw new \Exception(sprintf("%s::%s not implemented.", __CLASS__, __METHOD__));
  }

  /**
    * Executes an upgrade script
    *
    * @return void
    */
  public function executeScript($file, $filesystem)
  {
    throw new \Exception(sprintf("%s::%s not implemented.", __CLASS__, __METHOD__));
  }

  /**
   * Get photo will copy the photo to a temporary file.
   *
   */
  public function getPhoto($filename)
  {
    $filename = self::normalizePath($filename);
    if(file_exists($filename)) {
      $tmpname = tempnam($this->config->paths->temp, 'opme');
      copy($filename, $tmpname);
      return $tmpname;
    }
    return false;
  }

  public function putPhoto($localFile, $remoteFile)
  {
  	
	$remoteFile = self::normalizePath($remoteFile);
    // create all the directories to the file
    $dirname = dirname($remoteFile);
    if(!file_exists($dirname)) {
      mkdir($dirname, 0775, true);
    }
    getLogger()->info(sprintf('Copying from %s to %s', $localFile, $remoteFile));
    $status = copy($localFile, $remoteFile);
    
    if (!$status) {
      getLogger()->warn("Could not put {$localFile}");
      return false;
    }

    if(strpos($remoteFile, '/original/') !== false) // storing the original version of the photo
    {
      
      getLogger()->info("Adding $remoteFile to git-annex");

      $output = array();
      exec(sprintf('cd %s && git annex add %s', $this->repo, $remoteFile), $output, $status);
      getLogger()->info(sprintf("git annex add (output: [%s]) (return: %d)", implode(", ", $output), $status));

      unset($output);
      exec(sprintf('cd %s && git commit -m \'Add %s\'', $this->repo, $remoteFile), $output, $status);
      getLogger()->info(sprintf("git commit (output: [%s]) (return: %d)", implode(", ", $output), $status));
    }

    return true;
  }

  public function putPhotos($files)
  {
    foreach($files as $file)
    {
      list($localFile, $remoteFile) = each($file);
      $res = self::putPhoto($localFile, $remoteFile);
      if(!$res)
        return false;
    }
    return true;
  }

  /**
    * Get the hostname for the remote filesystem to be used in constructing public URLs.
    * @return string
    */
  public function getHost()
  {
    return $this->host;
  }

  public function initialize($isEditMode)
  {
    $status = parent::initialize($isEditMode);
    
    if (!$status) {
      return false;
    }

    if(!file_exists($this->repo . '/.git')) {
      getLogger()->info("Initializing git-annex repository");

      $output = array();
      exec(sprintf('cd %1$s && git init && git annex init "%2$s"', $this->repo, $this->name), $output, $status);
      return $status === 0;
    }

    return true;
  }

  /**
    * Identification method to return array of strings.
    *
    * @return array
    */
  public function identity()
  {
    return array_push(parent::identity(), "gitannex");
  }

  
  public function normalizePath($path)
  {
  	if(strpos($path, '/original/') !== false)
  	{
  		return $this->repo . $path;
  	} 
  	return $this->root . $path;
  }
}
