<?php

return [
	'path' => dirname(dirname(dirname(__DIR__))) . '\database\\' . (version_compare(Illuminate\Foundation\Application::VERSION, '8', '>=') ? 'seeders' : 'seeds'),
	'template_file' => dirname(dirname(dirname(__DIR__))) . '\app\Libs\Compio\templates-db\seeder.compio',
	'generate' => true,
	'convert_case' => ['camel', 'uf'],
	'change_file' => function(array $path_info){
		$path_info['filename'] = Str::singular($path_info['filename']) . 'TableSeeder';
		$path_info['basename'] = $path_info['filename'] . '.'. $path_info['extension'];
		$path_info['file'] = $path_info['dirname'] . '\\' . $path_info['basename'];
		$path_info['short'] = ($path_info['short_dirname'] == '' ? ('\\' . $path_info['filename']) : ($path_info['short_dirname'] . '\\' . $path_info['filename']));
		return $path_info;
	},



	'keywords' => [
		'@seeder_namespace' => function($default_value, $template_datas, $arguments, $callback_format_value, $file_content, $file_path, $all_keywords){
			return version_compare(Illuminate\Foundation\Application::VERSION, '8', '>=') ? ("\nnamespace Database\\" . ucfirst(preg_replace('/^'.preg_quote(database_path()).'\\\\(.*)/', '$1', pathinfo($file_path)['dirname'])) . ";\n") : '';
			// return 'App\Repositories' . (($n = end($template_datas['path'])['short_dirname']) != '' ? ('\\' . $n) : null);
		},
		'@seeder_class' => function($default_value, $template_datas, $arguments, $calback_format_value){
			return end($template_datas['path'])['filename'];
		},
		'@seeder_full_class' => function(...$args){
			// dump($args[6]['model']['@model_full_class']);
			return $args[1]['keywords']['@seeder_namespace']['result'] . '\\' . $args[1]['keywords']['@seeder_class']['result'];
		},
		'@model_full_class' => function(...$args){
			return isset($args[6]['model']['@model_full_class']) ? $args[6]['model']['@model_full_class'] : '';
		},
		'@model_class' => function(...$args){
			return isset($args[6]['model']['@model_class']) ? $args[6]['model']['@model_class'] : '';
		},

		'@seeder_extends' => function(...$args){
			return config('compio-db.conf.helpers.extend')($args, 'seeder', 'Illuminate\Database\Seeder', '@seeder_import_class', '@seeder_extends');
		},
		'@seeder_implements' => function(...$args){
			return config('compio-db.conf.helpers.implement')($args, 'seeder', null, '@seeder_import_class', '@seeder_implements');
		},
		'@seeder_import_trait' => function(...$args){
			return config('compio-db.conf.helpers.import_trait')($args, 'seeder', [
				// 'Illuminate\Database\Eloquent\Factories\HasFactory',
			], '@seeder_import_class', '@seeder_import_trait');
		},

		'@seeder_seed' => function(...$args){
			$call_col = config('compio-db.conf.helpers.colone');
			$cols = $call_col($args[2]['columns']);
			$version = version_compare(Illuminate\Foundation\Application::VERSION, '8', '>=') ? true : false;
			$d = $version ? 'seeders' : 'seeds';
			$sm_path = preg_replace('/^'.preg_quote(database_path() . '\\' . $d).'\\\\(.*)$/', '$1', $args[5]);
			$factory_file = database_path('factories\\' . preg_replace('/(.*)TableSeeder(\\.php)$/', '$1Factory$2', $sm_path));
			$model = $args[6]['seeder']['@model_class'];
			$render = '';
			$conf = isset($args[2]['seeder']['conf']) ? $args[2]['seeder']['conf'] : [];
			if(is_file($factory_file) || isset($conf['useFactory'])) $render = $version ? ($model . '::factory()->count(' . (isset($conf['count']) && is_int($conf['count']) ? $conf['count'] : 20) . ')->create();') : "factory(" . $model . "::class, " . (isset($conf['count']) && is_int($conf['count']) ? $conf['count'] : 20) . ")->create();";
			else $render = "foreach ([\n\t\t\t[\n\t\t\t\t" . (!empty($cols['cols']) ? implode(",\n\t\t\t\t", (function($v){
					$t = [];
					foreach ($v as $value) if(!in_array($value, ['id', 'created_at', 'updated_at', 'deleted_at'])) $t[] = '"' . $value . '" => ""';
					return $t;
				})(array_keys($cols['cols']))) : null) . "\n\t\t\t],\n\t\t] as \$key => \$value){\n\t\t\t" . $model . "::firstOrCreate(\$value);\n\t\t}";
			return $render;
		},


		'@seeder_import_class' => function(...$args){
			return config('compio-db.conf.helpers.import_class')($args, 'seeder', [
			]);
		},
	]
];