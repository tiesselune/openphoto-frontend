<?php

use Symfony\Component\Process\Process;

/**
 * GitAnnex
 *
 * @author Matthieu Prat <matthieuprat@gmail.com>
 */
class GitAnnex
{
	private $repoPath;

	public function __construct($repoPath)
	{
		$this->repoPath = realpath($repoPath);
		if (!is_dir($this->repoPath)) {
			throw new \RuntimeException(sprintf('"%s" is not a directory.', $repoPath));
		}
	}

	public function getPath()
	{
		return $this->repoPath;
	}

	public function init($repoName = '', $config = array())
	{
		$this->run('git init --shared=0777');
		$this->run('git annex init open-photo');
		foreach ($this->config as $key => $value) {
			$this->run(sprintf('git config %s "%s"', $key, $value));
		}
		$this->run('git commit -m "Initial git commit" --allow-empty');
		return $status;
	}
	
	public function branch($branchName)
	{
		$this->run("git branch $branchName");
	}
	
	public function checkout($branch)
	{
		$this->run(sprintf('git checkout %s', $branch));
	}

	public function add($file)
	{
		return $this->run(sprintf('git annex add %s', $file))
			&& $this->commit(sprintf('Add %s', $file));
	}
	
	public function addToBranch($photo,$branch)
	{
		/*$this->run(sprintf('git reset %s', $branch));
		$this->run(sprintf('git annex add %s',$photo));
		$this->commit(sprintf('Add %s', $photo));
		$this->run(sprintf('git branch -f %s',$branch));
		$this->run('git reset HEAD@{2}');*/
	}
	

	public function get($file)
	{
		return $this->run(sprintf('git annex get %s', $file));
	}

	public function drop($file)
	{
		return $this->run(sprintf('git annex drop --force %s', $file));
	}

	public function rm($file)
	{
		return $this->run(sprintf('git rm %s', $file))
			&& $this->commit(sprintf('Remove %s', $file));
	}
	
	public function rmFromBranch($photo,$branch)
	{
		/*$this->run(sprintf('git reset %s', $branch));
		$this->run(sprintf('git rm %s',$photo));
		$this->commit(sprintf('Remove %s', $photo));
		$this->run(sprintf('git branch -f %s',$branch));
		$this->run('git reset HEAD@{2}');*/
	}

	public function uninit()
	{
		if (!$this->get('.')) {
			throw new \RuntimeException('Couldn\'t retrieve the content of all annexed files. Abort.');
		}
		if (!$this->run('git annex unlock .')) {
			throw new \RuntimeException('Command "git annex unlock" failed. Abort.');
		}
		$this->run('rm -rf .git');

		return true;
	}

	private function commit($message)
	{
		return $this->run(sprintf('git commit -m \'%s\'', str_replace("'", "\\'", $message)));
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
			$handle = fopen($this->repoPath . '/.git/error.log', 'a');
		}
		fwrite($handle, $string . "\n");
	}
}
