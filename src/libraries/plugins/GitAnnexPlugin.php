<?php
/**
 * GitAnnexPlugin
 *
 * @author Matthieu Prat <matthieuprat@gmail.com>
 */
class GitAnnexPlugin extends PluginBase
{
  private $annex;

  public function __construct()
  {
    parent::__construct();
    $repoPath = $this->config->localfs->fsRoot . '/original';
    $config = array(
      'user.name' => 'OpenPhoto',
      'user.email' => $this->config->user->email,
    );
    $this->annex = new GitAnnex($repoPath, $config);

    $pluginDir = sprintf('%s/plugins', $this->config->paths->userdata);
    if (!is_dir($pluginDir)) {
      mkdir($pluginDir);
    }
    $pluginInitFile = sprintf('%s/%s.%s.init', $pluginDir, $_SERVER['HTTP_HOST'], 'GitAnnex');
    if (!is_file($pluginInitFile)) {
      touch($pluginInitFile);
      $this->init();
    }
  }

  private function init()
  {
    
  }

  public function onPhotoUploaded()
  {
    $photo = $this->getPhotoPath();
    $this->annex->add($photo);
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
  }

  public function onDeactivate() {
    $this->annex->uninit();
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
