<?php
/**
 * GitAnnex adapter
 */
class FileSystemLocalGitAnnex extends FileSystemLocal implements FileSystemInterface
{
  private $root;
  private $urlBase;
  const GIT = '/var/www/open-photo/bin/git-annex/runshell git';

  public function __construct()
  {
    parent::__construct();
    $fsConfig = getConfig()->get('localfs');
    $this->root = $fsConfig->fsRoot;
    $this->host = $fsConfig->fsHost;
  }

  public function deletePhoto($photo)
  {
    foreach($photo as $key => $value)
    {
      if(strncmp($key, 'path', 4) === 0) {
        $remoteFile = $value;
        if(strpos($remoteFile, '/original/') !== false) {
          getLogger()->info("Removing $remoteFile from git-annex");

          exec(sprintf('cd %1$s && %2$s annex drop --force %3$s && %2$s rm %3$s', $this->root, self::GIT, substr($remoteFile, 1)), $output, $status);
          exec(sprintf('cd %s && %s commit -m \'Delete %s\'', $this->root, self::GIT, $remoteFile), $output, $status);
        }
      }
    }
    return parent::deletePhoto($photo);
  }

  public function downloadPhoto($photo)
  {
    //TODO
    return parent::downloadPhoto($photo);
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
    return parent::getPhoto($filename);
  }

  public function putPhoto($localFile, $remoteFile)
  {
    $status = parent::putPhoto($localFile, $remoteFile);

    if (!$status) {
      getLogger()->warn("Could not put {$localFile}");
      return false;
    }

    if(strpos($remoteFile, '/original/') !== false) // storing the original version of the photo
    {
      getLogger()->info("Adding $remoteFile to git-annex");

      $output = array();
      exec(sprintf('cd %s && %s annex add %s', $this->root, self::GIT, $this->normalizePath($remoteFile)), $output, $status);
      getLogger()->info(sprintf("git annex add (output: [%s]) (return: %d)", implode(", ", $output), $status));

      unset($output);
      exec(sprintf('cd %s && %s commit -m \'Add %s\'', $this->root, self::GIT, $remoteFile), $output, $status);
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

    if(!file_exists($this->root . '/.git')) {
      getLogger()->info("Initializing git-annex repository");

      $output = array();
      exec(sprintf('cd %1$s && %2$s init && %2$s annex init "%3$s"', $this->root, self::GIT, "open-photo web server"), $output, $status);
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
}
