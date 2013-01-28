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

    if (!is_dir($repoPath)) {
      mkdir($repoPath);
    }

    $this->annex = new GitAnnex($repoPath);

    if (!$this->isInitialized()) {
      $this->init();
    }
  }

  private function init()
  {
    $config = array(
      'user.name' => 'OpenPhoto',
      'user.email' => $this->config->user->email,
    );
    $this->annex->init('openphoto', $config);
    $this->annex->add('.');
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

  public function onDeactivate()
  {
    $this->annex->uninit();
    unlink($this->getPluginInitFileName());
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

  private function isInitialized()
  {
    $initFileName = $this->getPluginInitFileName();

    if (is_file($initFileName)) {
      return true;
    }

    if (!is_dir(dirname($initFileName))) {
      mkdir(dirname($initFileName));
    }

    touch($initFileName);

    return false;
  }

  private function getPluginInitFileName()
  {
    return sprintf('%s/plugins/%s.%s.init', $this->config->paths->userdata, $_SERVER['HTTP_HOST'], 'GitAnnex');
  }
}
