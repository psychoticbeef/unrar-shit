<?php

class DoThings {
	private $tmp = '/Volumes/Users/tmp/';
	private $dir_processed = '/Volumes/Users/serve/unpacked/';
	private $failures = array();
	private $processed = array();
	private $working_dir = '/Users/da/foobar/';
	private $log_dir = '/Users/da/foobar/log/';

	/** @returns true on success, null on skip, false on error */
	private function copy_file($file, $check_size = null) {
		if (null === $check_size) {
			$check_size = true;
		}
		echo 'Doing file ' . $file . PHP_EOL;
		if ($check_size && filesize($file) < 200*1000*1000) {
			echo 'skipping due to size' . PHP_EOL;
			// probably sample or trailer. skip.
			return null;
		}
		return copy($file, $this->dir_processed . basename($file));
	}

	private function unpack_file($file) {
		echo 'Doing file ' . $file . PHP_EOL;
		exec('/usr/local/bin/unrar e ' . escapeshellarg($file) . ' ' . $this->dir_processed, $output, $retvar);
		echo join(PHP_EOL, $output) . PHP_EOL;
		return ($retvar === 0);
	}

	private function run($file, $ext) {
		$processed = null;
		if ($ext === 'mkv') {
			$processed = $this->copy_file($file);
		}
		if ($ext === 'mov') {
			$processed = $this->copy_file($file);
		}
		if ($ext === 'm4v') {
			$processed = $this->copy_file($file);
		}
		if ($ext === 'rar') {
			$processed = $this->unpack_file($file);
		}
		if (false === $processed) {
			$this->failures []= $file;
		}
		if ($processed) {
			$this->processed []= $file;
		}
	}


	private function report_failures() {
		if (sizeof($this->failures) === 0) {
			return;
		}
		file_put_contents($this->log_dir . (new DateTime())->format('Ymd_His') . '.log', join(PHP_EOL, $this->failures));
	}

	private function report_processed() {
		if (sizeof($this->processed) === 0) {
			return;
		}
		array_unshift($this->processed, '--- ' . (new DateTime())->format('Y-m-d H:i:s') . ' ---');
		file_put_contents($this->working_dir . 'processed.log', join(PHP_EOL, $this->processed) . PHP_EOL, FILE_APPEND);
	}

	private function already_processed($file) {
		$content = file_get_contents($this->working_dir . 'processed.log');
		return strpos($content, $file . PHP_EOL) !== false;
	}


	public function process(array $files) {
		foreach ($files as $file) {
			if (strpos($file, '.') === 0) {
				continue;
			}
			if (is_dir($file)) {
				$this->process(glob($file . '/*'));
				continue;
			}
			if ($this->already_processed($file)) {
				continue;
			}
			$ext = pathinfo($file, PATHINFO_EXTENSION);
			$this->run($file, $ext);
		}
	}

	public function report() {
		$this->report_failures();
		$this->report_processed();
	}

}

$running = '/Volumes/Users/serve/.running';
$incoming = '/Volumes/Users/serve/incoming/';
if (file_exists($running)) exit;
$files = glob($incoming . '*');
$dt = new DoThings();
$dt->process($files);
$dt->report();


