<?php

use Symfony\Component\Process\Process;

class GitAnnex
{
	private $repoPath;

	private $config;

	public function __construct($repoPath, $config = array())
	{
		$this->repoPath = realpath($repoPath);
		if (!is_dir($this->repoPath)) {
			throw new \RuntimeException(sprintf('"%s" is not a directory.', $repoPath));
		}
		$this->config = $config;
	}

	public function init()
	{
		$this->run('git init');
		$this->run('git annex init');
		foreach ($this->config as $key => $value) {
			$this->run(sprintf('git config %s "%s"', $key, $value));
		}
	}

	public function add($file)
	{
		if (!is_dir($this->repoPath . '/.git')) {
			$this->init();
		}
		$this->run(sprintf('git annex add %s', $file));
		$this->commit(sprintf('Add %s', $file));
	}

	public function get($file)
	{
		$this->run(sprintf('git annex get %s', $file));
	}

	public function drop($file)
	{
		$this->run(sprintf('git annex drop --force %s', $file));
	}

	public function rm($file)
	{
		$this->run(sprintf('git rm %s', $file));
		$this->commit(sprintf('Remove %s', $file));
	}

	public function uninit()
	{
		if (!$this->run('touch foo && ln foo bar')) { // hard links are disable so "git annex uninit" won't work
			// the following is a hack to achieve what "git annex uninit" does
			$tmpRepo = sys_get_temp_dir() . '/' . uniqid('git-annex-');
			mkdir($tmpRepo);
			
			if (!is_dir($tmpRepo)) {
				throw new \RuntimeException('Couldn\'t create temporary directory. Abort.');
			}

			$this->run('rm -rf .git');
			
			if (!$this->run("cp -RL * $tmpRepo")) {
				throw new \RuntimeException('Copy of repo failed. Abort.');
			}
			
			if (!$this->run("mv $this->repoPath {$this->repoPath}.bak && mv $tmpRepo $this->repoPath")) {
				throw new \RuntimeException('Switch of original repo and copy failed. Abort.');
			}
			
			$this->run("rm {$this->repoPath}.bak");
			
			return true;
		}

		if (!$this->run('git annex get .')) {
			throw new \RuntimeException('Couldn\'t retrieve the content of all annexed files. Abort.');
		}
		
		if (!$this->run('git annex uninit')) {
			throw new \RuntimeException('Command "git annex uninit" failed. Abort.');
		}
		
		$this->run('rm -rf .git');

		return true;
	}

	private function commit($message)
	{
		$this->run(sprintf('git commit -m \'%s\'', str_replace("'", "\\'", $message)));
	}

	private function run($command)
	{
		$process = new Process($command, $this->repoPath);
		$process->run();

		$this->log('$ ' . $command);
		$this->log($process->getOutput());
		if ($process->getErrorOutput()) {
			$this->log("ERROR:");
			$this->log($process->getErrorOutput());
		}
		$this->log("RETURN:\n" . var_export($process->isSuccessful(), true));
		$this->log();
		return $process->isSuccessful();
	}

	private function log($string = '') {
		static $handle;
		if (!$handle) {
			$handle = fopen($this->repoPath . '/error.log', 'a');
		}
		fwrite($handle, $string . "\n");
	}
}
