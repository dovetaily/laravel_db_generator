<?php

return [
	'path' => dirname(dirname(dirname(__DIR__))) . '\app\Models',
	'template_file' => dirname(dirname(dirname(__DIR__))) . '\app\Libs\Compio\templates-db\model.compio',
	'generate' => true,
	// 'convert_case' => 'camel',
	'convert_case' => ['camel', 'uf'],
	'change_file' => function(array $path_info){
		$path_info['filename'] = Str::singular($path_info['filename']);
		$path_info['basename'] = $path_info['filename'] . '.'. $path_info['extension'];
		$path_info['file'] = $path_info['dirname'] . '\\' . $path_info['basename'];
		$path_info['short'] = ($path_info['short_dirname'] == '' ? ('\\' . $path_info['filename']) : ($path_info['short_dirname'] . '\\' . $path_info['filename']));
		return $path_info;
	},



	'keywords' => [
		'@model_namespace' => function($default_value, $template_datas, $arguments, $callback_format_value, $file_content, $file_path, $all_keywords){
			return 'App' . preg_replace('/^'.preg_quote(app_path()).'(.*)/', '$1', pathinfo($file_path)['dirname']);
			// return 'App\Models' . (($n = end($template_datas['path'])['short_dirname']) != '' ? ('\\' . $n) : null);
		},
		'@model_class' => function($default_value, $template_datas, $arguments, $calback_format_value){
			return end($template_datas['path'])['filename'];
		},
		'@model_full_class' => function(...$args){
			return $args[1]['keywords']['@model_namespace']['result'] . '\\' . $args[1]['keywords']['@model_class']['result'];
		},
		'@model_fillable' => function(...$args){
			return config('compio-db.conf.helpers.property_model')(
				$args[2],
				["/**\n\t * The attributes that are mass assignable.\n\t *\n\t * @var array\n\t */\n\tprotected \$fillable = [---replace---];\n", true],
				['default' => [
					'exists' => [],
					'excepts' => ['id', 'created_at', 'updated_at', 'deleted_at', 'remember_token'],
				]],
				isset($args[2]['model']['fillable']) ? $args[2]['model']['fillable'] : []
			);
		},
		'@model_hidden' => function(...$args){
			$r = config('compio-db.conf.helpers.property_model')(
				$args[2],
				["/**\n\t * The attributes that should be hidden for arrays.\n\t *\n\t * @var array\n\t */\n\tprotected \$hidden = [---replace---];\n", true],
				['default' => [
					'exists' => ['password', 'remember_token'],
					'excepts' => [],
					'all' => false,
				]],
				isset($args[2]['model']['hidden']) ? $args[2]['model']['hidden'] : []
			);
			return empty($r) ? '' : "\n\t" . $r;
		},
		'@model_casts' => function(...$args){
			$r = config('compio-db.conf.helpers.property_model')(
				$args[2],
				["/**\n\t * The attributes that should be cast to native types.\n\t *\n\t * @var array\n\t */\n\tprotected \$casts = [---replace---];", true],
				['default' => [
					'exists' => ['/.*_at$/'],
					'excepts' => [],
					'all' => false,
				]],
				isset($args[2]['model']['casts']) ? $args[2]['model']['casts'] : [],
				function($datas){
					$dt = [];
					foreach ($datas as $key => $value) {
						if(($key == 'created_at' || $key == 'updated_at' || $key == 'deleted_at') && $value === null) continue;
						$dt[] = '"' . $key . '" => "' . (isset($value['cast']) ? $value['cast'] : 'datetime') . '"';
					}
					return empty($dt) ? null : str_replace("\n","\n\t\t", "\n" . implode(",\n", $dt)) . "\n\t";
				}
			);
			return empty($r) ? '' : "\n\t" . $r;
		},
		'@model_extends' => function(...$args){
			return config('compio-db.conf.helpers.extend')($args, 'model', '__eloquentModel','@model_import_class', '@model_extends');
		},
		'@model_implements' => function(...$args){
			return config('compio-db.conf.helpers.implement')($args, 'model', null, '@model_import_class', '@model_implements');
		},
		'@model_import_trait' => function(...$args){
			return config('compio-db.conf.helpers.import_trait')($args, 'model', [
				'Illuminate\Database\Eloquent\Factories\HasFactory',
			], '@model_import_class', '@model_import_trait');
		},
		'@model_belongs_to' => function(...$args){
			$call_col = config('compio-db.conf.helpers.colone');
			$conf = isset($args[2]['model']['belongs_to']) ? $args[2]['model']['belongs_to'] : [];
			$ret = config('compio-db.conf.helpers.function_model')(
				$args,
				$conf,
				$args[1]['keywords']['@model_class']['result'],
				'@model_belongs_to',
				$call_col((function($arguments, $call_back){
					$ret = [];
					$cols = $call_back($arguments['columns'])['cols'];
					foreach ($cols as $colone => $value) {
						if(preg_match('/(.*)_id$/i', $colone, $m)){
							$ret[end($m)] = [
								'type_returned' => ['__eloquentBelongsTo'],
								'args' => [
									// 'oste' => 'App\Kilo\Moli|tret',
									// 'test' => 'string',
									// 'derie' => null,
								],
								'access' => 'public',
								// 'description' => null,
								// 'code' => null,
								// 'return' => null,
							];
						}
					}
					return $ret;
				})($args[2], $call_col), false),
				$call_col(!empty($conf)
					? (is_string($conf)
						? [$conf => [
							'type_returned' => ['__eloquentBelongsTo'],
							'args' => null,
							// 'code' => null,
							// 'return' => null,
							'access' => 'public',
						]]
						: $conf
					)
					: []
				, false),
				function($function_name){
					return  '$this->belongsTo(' . ucfirst(Str::camel($function_name)) . '::class)';
				},
				function($function_name, $value, $model_class){
					return 'Get the ' . $function_name . " that owns the " . strtolower($model_class);
				},
			);
			return $ret;
		},
		'@model_has_one' => function(...$args){
			$call_col = config('compio-db.conf.helpers.colone');
			$conf = isset($args[2]['model']['has_one']) ? $args[2]['model']['has_one'] : [];
			$ret = config('compio-db.conf.helpers.function_model')(
				$args,
				$conf,
				$args[1]['keywords']['@model_class']['result'],
				'@model_has_one',
				$call_col((function($arguments, $call_back){
					$ret = [];
					// ...
					return $ret;
				})($args[2], $call_col), false),
				$call_col(!empty($conf)
					? (is_string($conf)
						? [$conf => [
							'type_returned' => ['__eloquentHasOne'],
							'args' => null,
							'access' => 'public',
						]]
						: $conf
					)
					: []
				, false, true, []),
				function($function_name){
					return  '$this->hasOne(' . ucfirst(Str::camel($function_name)) . '::class)';
				},
				function($function_name, $value){
					return 'Get ' . $function_name . " relationship";
				},
			);
			return $ret;
		},
		'@model_has_many' => function(...$args){
			$call_col = config('compio-db.conf.helpers.colone');
			$conf = isset($args[2]['model']['has_many']) ? $args[2]['model']['has_many'] : [];
			$ret = config('compio-db.conf.helpers.function_model')(
				$args,
				$conf,
				$args[1]['keywords']['@model_class']['result'],
				'@model_has_many',
				$call_col((function($arguments, $call_back){
					$ret = [];
					// ...
					return $ret;
				})($args[2], $call_col), false),
				(function($dts){
					if(!empty($dts))
						foreach ($dts['cols'] as $function_name => $value) {
							$dts['cols'][$function_name]['type_returned'] = array_key_exists('type_returned', $dts['cols'][$function_name]) ? $dts['cols'][$function_name]['type_returned'] : ['__eloquentHasMany'];
							$dts['cols'][$function_name]['access'] = array_key_exists('access', $dts['cols'][$function_name]) ? $dts['cols'][$function_name]['access'] : 'public';
						}
					return $dts;
				})($call_col(!empty($conf)
					? (is_string($conf)
						? [$conf => [
							'type_returned' => ['__eloquentHasMany'],
							'args' => null,
							'access' => 'public',
						]]
						: $conf
					)
					: []
				, false, true, [])),
				function($function_name){
					return  '$this->hasMany(' . ucfirst(Str::camel($function_name)) . '::class)';
				},
				function($function_name, $value){
					return 'Get ' . $function_name . " relationship";
				},
			);
			return $ret;
		},
		// '@model_belongs_to' => function(...$args){
		// },
		// '@model_belongs_to' => function(...$args){
		// },










		'@model_properties' => function($default_value, $template_datas, $arguments, $callback_format_value, $current_file_content, $file_path, $all_keywords){
			$is_api = (bool) preg_match('/api.*v[0-9]+.*|api.*v[0-9].*/i', $file_path);

			// $datas_main = isset($arguments['model']['properties']) ? $arguments['model']['properties'] : [];
			$datas = isset($arguments['model']['properties'])
				? $arguments['model']['properties']
				: []
			;
			// $conf_main = isset($arguments['model']['properties']['conf']) ? $arguments['model']['properties']['conf'] : [];
			$conf = isset($arguments['model']['properties']['__conf'])
				? $arguments['model']['properties']['__conf']
				: []
			;

			$call_col = config('compio-db.conf.helpers.colone');
			$cols = $call_col($arguments['columns'])['cols'];
			foreach(['created_at' => 'CREATED_AT', 'updated_at' => 'UPDATED_AT'] as $key => $prop)
				if(!array_key_exists($key, $cols)){
					$datas[$prop] = [
						'type_returned' => isset($datas[$prop]['type_returned']) ? $datas[$prop]['type_returned'] : null,
						'access' => isset($datas[$prop]['access']) ? $datas[$prop]['access'] : null,
						'description' => isset($datas[$prop]['description']) ? $datas[$prop]['description'] : 'Configuration of `' . $key . '` attribut',
						'value' => isset($datas[$prop]['value']) ? $datas[$prop]['value'] : null,
						'const' => true
					];
				}


			// $datas['test'] = [
			// 	'type_returned' => isset($datas['test']['type_returned']) ? $datas['test']['type_returned'] : null,
			// 	'access' => isset($datas['test']['access']) ? $datas['test']['access'] : null,
			// 	'description' => isset($datas['test']['description']) ? $datas['test']['description'] : null,
			// 	'value' => isset($datas['test']['value']) ? $datas['test']['value'] : ['dd'],
			// 	'const' => true
			// ];

			$prop_struct = config('compio-db.conf.helpers.property_structure');
			$functions = config('compio-db.conf.helpers.function_array')($datas/*, ['access' => 'protected']*/);
			$checked_class_import = config('compio-db.conf.helpers.checked_class_import');
			$render = '';
			$import_class = $functions['class'];
			$desc = function($description, $type_returned, $name, $is_const){
				$description = is_string($description) ? [$description] : $description;
				$type_returned = is_string($type_returned) ? [$type_returned] : $type_returned;
				return ((!empty($description) ? implode("\n", $description) . "\n" : null) . "\n" . "@var " . (!empty($type_returned)
						? implode('|', $type_returned) . ' ' 
						: null
					) . ($is_const ? '' : '$') . $name
				);
			};
				// dump($functions['datas']);
			foreach ($functions['datas'] as $property_name => $value) {
				if(!empty($value)){
					$value['type_returned'] = is_array($value['type_returned']) && !empty($value['type_returned'])
						? array_map(function($type){
							preg_match('/^.*\\\.* as (.*)|^.*\\\(.*)|.*$/i', $type, $m);
							return end($m);
						}, $value['type_returned'])
						: null
					;

					$value['description'] = is_array($value['description']) || empty($value['description'])
						? $desc($value['description'], $functions['datas'][$property_name]['type_returned'], $property_name, (isset($value['const']) && $value['const'] === true))
						: (is_callable($value['description']) && !is_string($value['description'])
							? $value['description'](...[$property_name, $value, $functions, $desc, $all_keywords, ['m_cs' => $repository_class, 'mt_prop' => $repository_this_property, 'mt_prop' => $repository_this_property]])
							: $value['description']
						)
					;

					if(!empty($value)){
						$render .= "\n" . $prop_struct(
							(isset($value['const']) && $value['const'] === true ? 'const ' : '$') . $property_name,
							$value['description'],
							$value['access'],
							// $value['args'],
							$value['type_returned'],
							$value['value']
							, "\t"
						) . "\n";
					}
				}
			}
			$import_class = $checked_class_import($current_file_content, $import_class);
			$render = !empty($render) ? "\n" . $render : $render;
			if(!empty($import_class)){
				$current_file_content = str_replace('@model_import_class', ('use ' . implode(";\nuse ", $import_class) . ";\n@model_import_class"), $current_file_content);
				$current_file_content = str_replace('@model_properties', $render, $current_file_content);
				file_put_contents($file_path, $current_file_content);
				return true;
			}
			return $render;
		},
		'@model_methods' => function($default_value, $template_datas, $arguments, $callback_format_value, $current_file_content, $file_path, $all_keywords){
			// dump($all_keywords['model']);
			$call_col = config('compio-db.conf.helpers.colone');
			// $datas = !$is_api && isset($arguments['model']['methods']) 
			// 	? $arguments['model']['methods']
			// 	: ($is_api && isset($arguments['model']['api']['methods']) ? $arguments['model']['api']['methods'] : [])
			// ;
			$datas_main = isset($arguments['model']['methods']) ? $arguments['model']['methods'] : [];
			$datas = isset($arguments['model']['methods'])
				? $arguments['model']['methods']
				: []
			;
			// $conf_main = isset($arguments['model']['methods']['conf']) ? $arguments['model']['methods']['conf'] : [];
			$conf = isset($arguments['model']['methods']['__conf'])
				? $arguments['model']['methods']['__conf']
				: []
			;
			// $conf = !$is_api && isset($arguments['model']['methods']['__conf']) ? $arguments['model']['methods']['__conf'] : ($is_api && isset($arguments['model']['api']['methods']['__conf']) ? $arguments['model']['api']['methods']['__conf'] : []);
			// $conf = isset($arguments['model']['methods']['__conf']) ? $arguments['model']['methods']['__conf'] : [];



			
			// // TEST  START  .......
			// 	$datas['test'] = [
			// 		'type_returned' => isset($datas['test']['type_returned']) ? $datas['test']['type_returned'] : null,
			// 		'args' => isset($datas['test']['args']) ? $datas['test']['args'] : [
			// 			// 'fields' => 'array'
			// 		],
			// 		'access' => isset($datas['test']['access']) ? $datas['test']['access'] : 'public',
			// 		'description' => isset($datas['test']['description']) ? $datas['test']['description'] : null,
			// 		'code' => isset($datas['test']['code']) ? (is_callable($cl = $datas['test']['code']) && !is_string($cl) ? $cl(...[$arguments, $all_keywords]) : $datas['test']['code']) : null,
			// 		'return' => isset($datas['test']['return']) ? $datas['test']['return'] : null
			// 	];
			// // TEST  STOP  .......

			// PERSONAL SORT START ....
				// $datas = array_merge([
				// 	'__construct' => null,
				// 	'index' => null,
				// 	'store' => null,
				// 	'show' => null,
				// 	'update' => null,
				// ], $datas);
			// PERSONAL SORT START ....

			$fun_struct = config('compio-db.conf.helpers.function_structure');
			$functions = config('compio-db.conf.helpers.function_array')($datas, ['access' => 'public']);
			$checked_class_import = config('compio-db.conf.helpers.checked_class_import');
			$render = '';
			$description_render = function($function_name, $value){
				return $function_name;
			};
			$import_class = array_merge(isset($import_class) ? $import_class : [], $functions['class']);
			$desc = function($description, $args, $type_returned){
				$description = is_string($description) ? [$description] : $description;
				$type_returned = is_string($type_returned) ? [$type_returned] : $type_returned;
				return ((!empty($description) ? implode("\n", $description) . "\n" : null) . (!empty($args) 
						? "\n@param " . (preg_replace('/(.*\\$.*) =.*|(.*\\$.*)=.*/i', '$1$2', implode("\n@param ", $args)))
						: null
					) . (!empty($type_returned)
						? "\n" . "@return " . implode('|', $type_returned) 
						: null
					)
				);
			};
				// dump($functions['datas']);
			foreach ($functions['datas'] as $function_name => $value) {
				if(!empty($value)){
					$value['type_returned'] = is_array($value['type_returned']) && !empty($value['type_returned'])
						? array_map(function($type){
							preg_match('/^.*\\\.* as (.*)|^.*\\\(.*)|.*$/i', $type, $m);
							return end($m);
						}, $value['type_returned'])
						: null
					;

					$value['args'] = is_array($value['args']) && !empty($value['args'])
						? array_map(function($arg_type, $arg_name){
							// dump(['name' => $arg_name, 'type' => $arg_type]);exit;
							$type = is_array($arg_type) && !empty($arg_type)
								? array_map(function($type){
									preg_match('/^.*\\\.* as (.*)|^.*\\\(.*)|.*$/i', $type, $m);
									return end($m);
								}, $arg_type)
								: null
							;
							return (!empty($type)
								? implode('|', $type) . " "
								: null
							) . "$" . $arg_name;
						}, $value['args'], array_keys($value['args']))
						: null
					;

					$value['description'] = is_array($value['description']) || empty($value['description'])
						? $desc($value['description'], $value['args'], $functions['datas'][$function_name]['type_returned'])
						: (is_callable($value['description']) && !is_string($value['description'])
							? $value['description'](...[$function_name, $value, $functions, $desc, $all_keywords, ['m_cs' => $repository_class, 'mt_prop' => $repository_this_property, 'mt_prop' => $repository_this_property, 'is_api' => (bool) $is_api]])
							: $value['description']
						)
					;

					if(!empty($value)){
						$render .= $fun_struct($function_name, $value['description'], $value['access'], $value['args'], $value['type_returned'], (!empty($value['code']) ? "\n\t". (is_array($value['code']) ? implode("\n", $value['code']) : $value['code']) : null), $value['return'], "\t") . "\n\n";
					}
				}
			}
			$import_class = $checked_class_import($current_file_content, $import_class);
			if(empty($import_class)) return $render;
			else{
				$current_file_content = str_replace('@model_import_class', ('use ' . implode(";\nuse ", $import_class) . ";\n@model_import_class"), $current_file_content);
				$current_file_content = str_replace('@model_methods', $render . "\n", $current_file_content);
				file_put_contents($file_path, $current_file_content);
				return true;
			}
			return $render;
		},















		'@model_import_class' => function(...$args){
			return config('compio-db.conf.helpers.import_class')($args, 'model', [
				// '__eloquentModel',
				// '__eloquentDBCollection',
				// 'Illuminate\Contracts\Pagination\LengthAwarePaginator',
			]);
		},
	],
];