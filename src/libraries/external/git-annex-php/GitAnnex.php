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
		$this->run('git annex init open-photo');
		foreach ($this->config as $key => $value) {
			$this->run(sprintf('git config %s "%s"', $key, $value));
		}
		$this->run('git commit -m "Initial git commit" --allow-empty');
	}
	
	public function branch($branchName)
	{
		if (!is_dir($this->repoPath . '/.git')) {
			$this->init();
		}
		$this->run("git branch $branchName");
	}
	
		public function checkout($branch)
	{
		$this->run(sprintf('git checkout %s', $branch));
	}

	public function add($file)
	{
		if (!is_dir($this->repoPath . '/.git')) {
			$this->init();
		}
		$this->run(sprintf('git annex add %s', $file));
		$this->commit(sprintf('Add %s', $file));
	}
	
	public function addToBranch($photo,$branch)
	{
		$this->run(sprintf('git reset %s', $branch));
		$this->run(sprintf('git annex add %s',$photo));
		$this->commit(sprintf('Add %s', $photo));
		$this->run(sprintf('git branch -f %s',$branch));
		$this->run('git reset HEAD@{2}');
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
	
	public function rmFromBranch($photo,$branch)
	{
		$this->run(sprintf('git reset %s', $branch));
		$this->run(sprintf('git rm %s',$photo));
		$this->commit(sprintf('Remove %s', $photo));
		$this->run(sprintf('git branch -f %s',$branch));
		$this->run('git reset HEAD@{2}');
	}

	public function uninit()
	{	// commented out all that to replace it with "Unlock". Still has to prove its efficiency.
		/*if (!$this->run('touch foo && ln foo bar')) { // hard links are disable so "git annex uninit" won't work
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
		*/
		if (!$this->run('git annex get .')) {
			throw new \RuntimeException('Couldn\'t retrieve the content of all annexed files. Abort.');
		}
		
		if (!$this->run('git annex unlock .')) {
			throw new \RuntimeException('Couldn\'t replace content of all symlinks with their actual content. Abort.');
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
