<?php

use Symfony\Component\Process\Process;

class GitAnnex
{
	private $repoPath;

	private $config;

	public function __construct($repoPath, $config = array())
	{
		$this->repoPath = $repoPath;
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
		$this->log();
	}

	private function log($string = '') {
		static $handle;
		if (!$handle) {
			$handle = fopen($this->repoPath . '/error.log', 'a');
		}
		fwrite($handle, $string . "\n");
	}
}
