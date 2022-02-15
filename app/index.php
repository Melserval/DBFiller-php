<?php declare(strict_types=1);

$start = new DateTime();
require './FileDataInfo.php';
require './PDOInstance.php';

$credentials = file_get_contents('./.credentials.json') OR die("файл конфигурации не доступен");
$file_config = json_decode($credentials)->configFile;

// --- программа ---

if (is_readable($file_config) && !is_dir($file_config)) {
	$datafileinfo_set = [];
	$files_params = json_decode(file_get_contents($file_config), true);
	foreach ($files_params as $config) {
		
		$pdo = PDOInstance::get($config['host'], $config['database'], $config['user'], $config['pass']);

		foreach ($config['tables'] as $table) {
			foreach ($table['files'] as $file) {
				$datafileinfo_set[] = new FileDataInfo(
					$pdo, $table['tablename'], $file['filename'], $file['startrow'], $file['columns']
				);
			}
		}
	}
	foreach ($datafileinfo_set as $fileObject) {
		$fileObject->run();
	}
	print "Завершено. Время: " . $start->diff(new DateTime())->format("h:%H m:%I s:%S ms:%f");
} else {
	print "Неудалось прочитать файл {$file_config}";
}
