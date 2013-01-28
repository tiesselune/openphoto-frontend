<?php
/**
 * GitAnnexPlugin
 *
 * @author Matthieu Prat <matthieuprat@gmail.com>
 */
class GitAnnexPlugin extends PluginBase
{
  private $annex;
  private $pluginInitFile;

  public function __construct()
  {
    parent::__construct();
    $repoPath = $this->config->localfs->fsRoot . '/original';
    
    if (!is_dir($repoPath)) {
      mkdir($repoPath);
    }
    
    $config = array(
      'user.name' => 'OpenPhoto',
      'user.email' => $this->config->user->email
    );
    $this->annex = new GitAnnex($repoPath, $config);

    $pluginDir = sprintf('%s/plugins', $this->config->paths->userdata);
    if (!is_dir($pluginDir)) {
      mkdir($pluginDir);
    }
    $this->pluginInitFile = sprintf('%s/%s.%s.init', $pluginDir, $_SERVER['HTTP_HOST'], 'GitAnnex');
    if (!is_file($this->pluginInitFile)) {
      touch($this->pluginInitFile);  
      $this->init($repoPath);
    }
  }

  private function init($repoPath)
  {
    $this->annex->init();
    $this->annex->branch('photoView');
    //for future use
    $this->annex->branch('albumView');
    $this->annex->checkout('photoView');
    $this->annex->add('.');
    
    $opOriginalDir = $this->config->paths->external . '/git-annex-php';
    $gitHooksDir = $repoPath . '/.git/hooks';
    $gitHooksAddDir = $gitHooksDir . '/openphoto-hook';
    $opOriginalAddDir = $opOriginalDir . '/openphoto-hook';
      
    if(!copy($opOriginalDir . '/post-receive',$gitHooksDir . '/post-receive'))
    {
    	throw new \RuntimeException('Couldn\'t copy post-receive hook from external/git-annex-php');
    }
    
    if(!chmod($gitHooksDir . '/post-receive', 0755))
    {
    	throw new \RuntimeException('Couldn\'t make the hook executable.');
    }
    
    if (!is_dir($gitHooksAddDir)) {
      mkdir($gitHooksAddDir);
    }
    
    if(!copy($opOriginalAddDir . '/OAuthSimple.php', $gitHooksAddDir . '/OAuthSimple.php'))
    {
    	throw new \RuntimeException('Couldn\'t copy OAuthSimple.php from external/git-annex-php/openphoto-hook');
    }
    
    if(!copy($opOriginalAddDir . '/OpenPhotoOAuth.php', $gitHooksAddDir . '/OpenPhotoOAuth.php'))
    {
    	throw new \RuntimeException('Couldn\'t copy OpenPhotoOAuth.php from external/git-annex-php/openphoto-hook');
    }
    $consumerKey = getCredential()->add('Git-annex', array('read','write','delete'));
    getCredential()->convertToken($consumerKey, Credential::typeAccess);
    $credentialsFile = fopen($gitHooksAddDir . '/credentials.php', 'w');
    $message = $this->api->invoke("/v1/oauth/$consumerKey/view.json");
    $credentials = $message['result'];
    
    fwrite($credentialsFile, "<?php\n\$repo = \"../\";\n\$host=\"localhost\";\n");
    fwrite($credentialsFile, "\$consumerKey=\"$consumerKey\";\n");
    fwrite($credentialsFile, sprintf('$consumerSecret="%s";'."\n",$credentials['clientSecret']));
    fwrite($credentialsFile, sprintf('$token="%s";'."\n",$credentials['userToken']));
    fwrite($credentialsFile, sprintf('$tokenSecret="%s";'."\n",$credentials['userSecret']));
    fwrite($credentialsFile, "?>");
    
    fclose($keyFile);
  }

  public function onPhotoUploaded()
  {
    $photo = $this->getPhotoPath();
    $this->annex->add($photo);
    $this->annex->addToBranch($photo,'master');
  }

  public function onPhotoDownload()
  {
    $photo = $this->getPhotoPath();
    $this->annex->get($photo);
  }

  public function onPhotoDeleted()
  {
    $photo = $this->getPhotoPath();
    $this->annex->drop($photo);
    $this->annex->rm($photo);
    $this->annex->rmFromBranch('master');
  }

  public function onDeactivate() {
    $this->annex->uninit();
    unlink($this->pluginInitFile);
  }

  private function getPhotoPath()
  {
    $host = $this->config->localfs->fsHost;
    $photo = $this->plugin->getData('photo');

    if (!$photo) {
      throw new \RuntimeException('Couldn\'t retrieve the photo object associated to the plugin.');
    }

    $photoUrl = $photo['pathOriginal'];

    if (!preg_match('%' . preg_quote($host) . '/original/(?P<path>[^?]+)%', $photoUrl, $matches)) {
      throw new \RuntimeException(sprintf('Couldn\'t guess the relative photo path from the photo URL (%s).', $photoUrl));
    }

    return $matches['path'];
  }
}
