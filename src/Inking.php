<?php

namespace Dovetaily\LaravelDbGenerator;

use Composer\Script\Event;

class Inking {
	/**
	 * Handle the post-autoload-dump Composer event.
	 *
	 * @param  \Composer\Script\Event|null  $event
	 * @return void
	 */
	public static function postAutoloadDump($event = null){

		$datas = [
			[
				'gen_path' => __DIR__ . '\..\..\..\\app\Libs\Compio\templates-db\\',
				'files_path' => __DIR__ . '\resources\model\\',
			],
			[
				'gen_path' => __DIR__ . '\..\..\..\\config\compio-db\\',
				'files_path' => __DIR__ . '\resources\\',
			],
			[
				'gen_path' => __DIR__ . '\..\..\..\\config\compio-db\template\\',
				'files_path' => __DIR__ . '\resources\template_config\\',
			],
		];

		foreach ($datas as $model) {
			foreach (scandir($model['files_path']) as $file) {
				if(file_exists($ff = $model['gen_path'] . $file) && !is_dir($ff)){
					echo (isset($model['exists']) 
						? $model['exists']($ff)
						: "\n\tALREADY EXIST -> \"". preg_replace('/.*\\.\\.\\\\(.*)$/', '$1', $ff) . "\"")
					;
					continue; 
				}
				$fg = $model['files_path'] . $file;
				if(!in_array($file, ['.', '..']) && is_file($fg)){
					if((is_dir($model['gen_path']) || mkdir($model['gen_path'], 0777, true)) && is_writable($model['gen_path'])){
						copy($fg, $model['gen_path'] . $file);
					}
					else {
						echo isset($model['error']) 
							? $model['error']
							: "\n\tERROR !!\n\t-->This \"" . preg_replace('/.*\\.\\.\\\\(.*)$/', '$1', $model['gen_path']) . "\" folder cannot be created (access prohibited)\n"; 
						break;
					}
				}
			}
		}
		echo "\n\n  `Dovetaily\LaravelDbGenerator` has been loaded\n\n";

	}
}