<?php

return [
	'path' => [dirname(dirname(dirname(__DIR__))) . '\app\Http\Controllers\Api\V1', dirname(dirname(dirname(__DIR__))) . '\app\Http\Controllers'],
	'template_file' => dirname(dirname(dirname(__DIR__))) . '\app\Libs\Compio\templates-db\controller.compio',
	'generate' => true,
	'convert_case' => ['camel', 'uf'],
	'change_file' => function(array $path_info){
		$path_info['filename'] = Str::singular($path_info['filename']) . 'Controller';
		$path_info['basename'] = $path_info['filename'] . '.'. $path_info['extension'];
		$path_info['file'] = $path_info['dirname'] . '\\' . $path_info['basename'];
		$path_info['short'] = ($path_info['short_dirname'] == '' ? ('\\' . $path_info['filename']) : ($path_info['short_dirname'] . '\\' . $path_info['filename']));
		return $path_info;
	},

	'keywords' => [
		'@model_full_class' => function(...$args){
			return isset($args[6]['model']['@model_full_class']) ? $args[6]['model']['@model_full_class'] : '';
		},
		'@model_class' => function(...$args){
			return isset($args[6]['model']['@model_class']) ? $args[6]['model']['@model_class'] : '';
		},
				'@repository_full_class' => function(...$args){
					return isset($args[6]['repository']['@repository_full_class']) ? $args[6]['repository']['@repository_full_class'] : '';
				},
				'@repository_class' => function(...$args){
					return isset($args[6]['repository']['@repository_class']) ? $args[6]['repository']['@repository_class'] : '';
				},
						'@resource_namespace' => function(...$args){
							return isset($args[6]['resource']['@resource_namespace']) ? $args[6]['resource']['@resource_namespace'] : '';
						},
						'@resource_full_class' => function(...$args){
							return isset($args[6]['resource']['@resource_full_class']) ? $args[6]['resource']['@resource_full_class'] : '';
						},
						'@resource_class' => function(...$args){
							return isset($args[6]['resource']['@resource_class']) ? $args[6]['resource']['@resource_class'] : '';
						},
								'@request_namespace' => function(...$args){
									return isset($args[6]['request']['@request_namespace']) ? $args[6]['request']['@request_namespace'] : '';
								},
								'@request_full_class' => function(...$args){
									return isset($args[6]['request']['@request_full_class']) ? $args[6]['request']['@request_full_class'] : '';
								},
								'@request_class' => function(...$args){
									return isset($args[6]['request']['@request_class']) ? $args[6]['request']['@request_class'] : '';
								},


		'@controller_namespace' => function($default_value, $template_datas, $arguments, $callback_format_value, $file_content, $file_path, $all_keywords){
			return 'App' . preg_replace('/^'.preg_quote(app_path()).'(.*)/', '$1', pathinfo($file_path)['dirname']);
			// return 'App\Repositories' . (($n = end($template_datas['path'])['short_dirname']) != '' ? ('\\' . $n) : null);
		},
		'@controller_class' => function($default_value, $template_datas, $arguments, $calback_format_value){
			return end($template_datas['path'])['filename'];
		},
		'@controller_full_class' => function(...$args){
			// dump($args[6]['model']['@model_full_class']);
			return $args[1]['keywords']['@controller_namespace']['result'] . '\\' . $args[1]['keywords']['@controller_class']['result'];
		},
		'@controller_extends' => function(...$args){
			return config('compio-db.conf.helpers.extend')($args, 'controller', 'App\Http\Controllers\Controller', '@controller_import_class', '@controller_extends');
		},
		'@controller_implements' => function(...$args){
			return config('compio-db.conf.helpers.implement')($args, 'controller', null,'@controller_import_class', '@controller_implements');
		},
		'@controller_import_trait' => function(...$args){
			return config('compio-db.conf.helpers.import_trait')($args, 'controller', [
				// 'Illuminate\Database\Eloquent\Factories\HasFactory',
			], '@controller_import_class', '@controller_import_trait');
		},
		// '@controller_methods' => function(...$args){
		'@controller_properties' => function($default_value, $template_datas, $arguments, $callback_format_value, $current_file_content, $file_path, $all_keywords){
			$is_api = (bool) preg_match('/api.*v[0-9]+.*|api.*v[0-9].*/i', $file_path);

			$datas_main = isset($arguments['controller']['properties']) ? $arguments['controller']['properties'] : [];
			$datas = $is_api && isset($arguments['controller']['api']['properties']) 
				? $arguments['controller']['api']['properties']
				: (!$is_api && isset($arguments['controller']['view']['properties'])
					? $arguments['controller']['view']['properties']
					: (isset($arguments['controller']['properties'])
						? $arguments['controller']['properties']
						: []
					)
				)
			;
			$conf_main = isset($arguments['controller']['properties']['conf']) ? $arguments['controller']['properties']['conf'] : [];
			$conf = $is_api && isset($arguments['controller']['api']['properties']['__conf']) 
				? !$is_api && $arguments['controller']['api']['properties']['__conf']
				: (isset($arguments['controller']['view']['properties']['__conf'])
					? $arguments['controller']['view']['properties']['__conf']
					: (isset($arguments['controller']['properties']['__conf'])
						? $arguments['controller']['properties']['__conf']
						: []
					)
				)
			;
			// $datas = !$is_api && isset($arguments['controller']['properties']) ? $arguments['controller']['properties'] : ($is_api && isset($arguments['controller']['api']['properties']) ? $arguments['controller']['api']['properties'] : []);
			// $datas = isset($arguments['controller']['properties']) ? $arguments['controller']['properties'] : [];
			// $conf = !$is_api && isset($arguments['controller']['properties']['__conf']) ? $arguments['controller']['properties']['__conf'] : ($is_api && isset($arguments['controller']['api']['properties']['__conf']) ? $arguments['controller']['api']['properties']['__conf'] : []);
			// $conf = isset($arguments['controller']['properties']['__conf']) ? $arguments['controller']['properties']['__conf'] : [];

			$repository_class = $all_keywords['controller']['@repository_class'];
			$repository_full_class = $all_keywords['controller']['@repository_full_class'];
			$repository_property = Str::snake($all_keywords['controller']['@repository_class']);
			$repository_this_property = '$this->' . $repository_property;

			$datas[$repository_property] = array_key_exists($repository_property, $datas) && is_null($datas[$repository_property]) ? null : [
				'type_returned' => isset($datas[$repository_property]['type_returned']) ? $datas[$repository_property]['type_returned'] : $repository_full_class,
				'access' => isset($datas[$repository_property]['access']) ? $datas[$repository_property]['access'] : null,
				'description' => isset($datas[$repository_property]['description']) ? $datas[$repository_property]['description'] : $all_keywords['controller']['@controller_class'] . ' constructor',
				'value' => isset($datas[$repository_property]) && array_key_exists('value', $datas[$repository_property]) ? $datas[$repository_property]['value'] : '#be!!null!!'
			];


			$prop_struct = config('compio-db.conf.helpers.property_structure');
			$functions = config('compio-db.conf.helpers.function_array')($datas, ['access' => 'protected']);
			$checked_class_import = config('compio-db.conf.helpers.checked_class_import');
			$render = '';
			$import_class = $functions['class'];
			$desc = function($description, $type_returned, $name){
				$description = is_string($description) ? [$description] : $description;
				$type_returned = is_string($type_returned) ? [$type_returned] : $type_returned;
				return ((!empty($description) ? implode("\n", $description) . "\n" : null) . "\n" . "@var " . (!empty($type_returned)
						? implode('|', $type_returned) . ' ' 
						: null
					) . '$' . $name
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
						? $desc($value['description'], $functions['datas'][$property_name]['type_returned'], $property_name)
						: (is_callable($value['description']) && !is_string($value['description'])
							? $value['description'](...[$property_name, $value, $functions, $desc, $all_keywords, ['m_cs' => $repository_class, 'mt_prop' => $repository_this_property, 'mt_prop' => $repository_this_property]])
							: $value['description']
						)
					;

					if(!empty($value)){
						$render .= $prop_struct(
							'$' . $property_name,
							$value['description'],
							$value['access'],
							// $value['args'],
							$value['type_returned'],
							$value['value']
							, "\t"
						);
					}
				}
			}
			$import_class = $checked_class_import($current_file_content, $import_class);
			$render = !empty($render) ? "\n\n" . $render : $render;
			if(!empty($import_class)){
				$current_file_content = str_replace('@controller_import_class', ('use ' . implode(";\nuse ", $import_class) . ";\n@controller_import_class"), $current_file_content);
				$current_file_content = str_replace('@controller_properties', $render, $current_file_content);
				file_put_contents($file_path, $current_file_content);
				return true;
			}
			return $render;
		},
		'@controller_methods' => function($default_value, $template_datas, $arguments, $callback_format_value, $current_file_content, $file_path, $all_keywords){
			// dump($all_keywords['model']);
			$call_col = config('compio-db.conf.helpers.colone');
			$is_api = (bool) preg_match('/api.*v[0-9]+.*|api.*v[0-9].*/i', $file_path);
			// $datas = !$is_api && isset($arguments['controller']['methods']) 
			// 	? $arguments['controller']['methods']
			// 	: ($is_api && isset($arguments['controller']['api']['methods']) ? $arguments['controller']['api']['methods'] : [])
			// ;
			$datas_main = isset($arguments['controller']['methods']) ? $arguments['controller']['methods'] : [];
			$datas = $is_api && isset($arguments['controller']['api']['methods']) 
				? $arguments['controller']['api']['methods']
				: (!$is_api && isset($arguments['controller']['view']['methods'])
					? $arguments['controller']['view']['methods']
					: (isset($arguments['controller']['methods'])
						? $arguments['controller']['methods']
						: []
					)
				)
			;
			$conf_main = isset($arguments['controller']['methods']['conf']) ? $arguments['controller']['methods']['conf'] : [];
			$conf = $is_api && isset($arguments['controller']['api']['methods']['__conf']) 
				? $arguments['controller']['api']['methods']['__conf']
				: (!$is_api && isset($arguments['controller']['view']['methods']['__conf'])
					? $arguments['controller']['view']['methods']['__conf']
					: (isset($arguments['controller']['methods']['__conf'])
						? $arguments['controller']['methods']['__conf']
						: []
					)
				)
			;
			// $conf = !$is_api && isset($arguments['controller']['methods']['__conf']) ? $arguments['controller']['methods']['__conf'] : ($is_api && isset($arguments['controller']['api']['methods']['__conf']) ? $arguments['controller']['api']['methods']['__conf'] : []);
			// $conf = isset($arguments['controller']['methods']['__conf']) ? $arguments['controller']['methods']['__conf'] : [];

			$model_namespace = $all_keywords['model']['@model_namespace'];
			$model_class = $all_keywords['controller']['@model_class'];
			$model_full_class = $all_keywords['controller']['@model_full_class'];
			$model_property = Str::snake($all_keywords['controller']['@model_class']);
			$model_this_property = '$this->' . $model_property;

			$repository_namespace = $all_keywords['repository']['@repository_namespace'];
			$repository_class = $all_keywords['controller']['@repository_class'];
			$repository_full_class = $all_keywords['controller']['@repository_full_class'];
			$repository_property = Str::snake($all_keywords['controller']['@repository_class']);
			$repository_this_property = '$this->' . $repository_property;

			$resource_class = $all_keywords['controller']['@resource_class'];
			$resource_full_class = $all_keywords['controller']['@resource_full_class'];
			$resource_property = Str::snake($all_keywords['controller']['@resource_class']);
			$resource_this_property = '$this->' . $repository_property;

			$request_namespace = $all_keywords['controller']['@request_namespace'];
			$request_store_class = $model_class . 'StoreRequest';
			$request_update_class = $model_class . 'UpdateRequest';
			$request_full_store_class = $request_namespace . '\\' . $request_store_class;
			$request_full_update_class = $request_namespace . '\\' . $request_update_class;

			// CONSTRUCT  START  .......
				$datas['__construct'] = array_key_exists('__construct', $datas) && is_null($datas['__construct']) ? null : [
					'type_returned' => isset($datas['__construct']['type_returned']) ? $datas['__construct']['type_returned'] : null,
					'args' => isset($datas['__construct']['args']) ? $datas['__construct']['args'] : [
						$repository_property => $repository_class
					],
					'access' => isset($datas['__construct']['access']) ? $datas['__construct']['access'] : 'public',
					'description' => isset($datas['__construct']['description']) ? $datas['__construct']['description'] : 'Create a new constructor for this controller.',
					// 'description' => isset($datas['__construct']['description']) ? $datas['__construct']['description'] : function($function_name, $value, $functions, $desc, $all_keywords, $datas = []){
					// 	if(!empty($datas) && is_array($datas)) extract($datas);
					// 	return $desc ($all_keywords['controller']['@controller_class'] . ' constructor', $value['args'], [$repository_class]);
					// },
					'code' => isset($datas['__construct']['code']) ? (is_callable($cl = $datas['__construct']['code']) && !is_string($cl) ? $cl(...[$arguments, $all_keywords]) : $datas['__construct']['code']) : $repository_this_property . " = $" . $repository_property . ";",
					'return' => isset($datas['__construct']['return']) ? $datas['__construct']['return'] : null
				];
			// CONSTRUCT  STOP  .......
			
			// INDEX  START  .......
				$datas['index'] = [
					'type_returned' => isset($datas['index']['type_returned']) ? $datas['index']['type_returned'] : ($is_api ? (!isset($datas['index']['collection']) || $datas['index']['collection'] === true
						? 'Illuminate\Http\Resources\Json\AnonymousResourceCollection'
						: (is_string($datas['index']['collection'])
							? null
							: $resource_full_class
						)
					) : null),
					'args' => isset($datas['index']['args']) ? $datas['index']['args'] : [
						// 'fields' => 'array'
					],
					'access' => isset($datas['index']['access']) ? $datas['index']['access'] : 'public',
					'description' => isset($datas['index']['description']) ? $datas['index']['description'] : ('Display a listing of the resource.' . ($is_api && !isset($datas['index']['type_returned']) ? null : "\n\n@return \Illuminate\Http\Response|string")),
					// 'description' => isset($datas['index']['description']) ? $datas['index']['description'] : function($function_name, $value, $functions, $desc, $all_keywords, $datas = []){
					// 	if(!empty($datas) && is_array($datas)) extract($datas);
					// 	return $desc ('Create ' . $repository_class, $value['args'], [$repository_class]);
					// },
					'code' => isset($datas['index']['code']) ? (is_callable($cl = $datas['index']['code']) && !is_string($cl) ? $cl(...[$arguments, $all_keywords]) : $datas['index']['code']) : ($is_api 
						? null
						: '$' . $model_property . ' = ' . $repository_this_property . '->getPaginate(30);'
					),
					'return' => isset($datas['index']['return']) ? $datas['index']['return'] : ($is_api ? ((!isset($datas['index']['collection']) || (is_bool($datas['index']['collection']) && $datas['index']['collection'] === true)
						? ($resource_class . "::collection")
						: (is_string($datas['index']['collection'])
							? $datas['index']['collection']
							: ('new ' . $resource_class)
						)
					) . "(\n\t\t" . $repository_this_property . "->getPaginate(30)\n\t)") : 'view(' . (isset($datas['index']['view']) ? $datas['index']['view'] : '"' . Str::plural($model_property) . '.index"') . ", " . (isset($datas['index']['view.datas']) ? $datas['index']['view.datas'] : '$'.Str::plural($model_property)) . ')')
				];	
			// INDEX  STOP  .......
			
			// CREATE  START  .......
				if(!$is_api || ($is_api && isset($datas['create'])))
					$datas['create'] = [
						'type_returned' => isset($datas['create']['type_returned']) ? $datas['create']['type_returned'] : ($is_api  ? null : null),
						'args' => isset($datas['create']['args']) ? $datas['create']['args'] : [
							$model_property => $model_full_class
						],
						'access' => isset($datas['create']['access']) ? $datas['create']['access'] : 'public',
						'description' => isset($datas['create']['description']) ? $datas['create']['description'] : ($is_api ? 'Show the form for creating a new resource.' : function(...$r){extract(end($r)); $dts = ['Show the form for creating a new resource.', '']; if(!empty($r[1]['args'])){$r[1]['args'] = array_map(function($el){preg_match('/(.*) =.*|(.*)=.*|.*/i', $el, $m); return end($m);}, $r[1]['args']); $dts = array_merge($dts, explode("\n", "@param " . implode("\n@param ", $r[1]['args']))); } $dts[] = "@return Illuminate\Http\Response|Illuminate\Contracts\View\View|Closure|string"; return $dts;}),
						// 'description' => isset($datas['create']['description']) ? $datas['create']['description'] : ('Display the specified resource.' . (!$is_api ? "\n\n@return \Illuminate\Http\Response|string" : null)),
						'code' => isset($datas['create']['code']) ? (is_callable($cl = $datas['create']['code']) && !is_string($cl) ? $cl(...[$arguments, $all_keywords]) : $datas['create']['code']) : null,
						'return' => isset($datas['create']['return']) ? $datas['create']['return'] : ($is_api ? null : 'view(' . (isset($datas['create']['view']) ? $datas['create']['view'] : '"' . Str::plural($model_property) . '.create"') . ", " . (isset($datas['create']['view.datas']) ? $datas['create']['view.datas'] : '$' . $model_property) . ')')
					];
			// CREATE  STOP  .......
			
			// STORE  START  .......
				$datas['store'] = [
					// 'type_returned' => isset($datas['store']['type_returned']) ? $datas['store']['type_returned'] : $resource_full_class,
					'type_returned' => isset($datas['store']['type_returned']) ? $datas['store']['type_returned'] : ($is_api ? (isset($datas['store']['collection']) && $datas['store']['collection'] === true
						? 'Illuminate\Http\Resources\Json\AnonymousResourceCollection'
						: (isset($datas['store']['collection']) && is_string($datas['store']['collection'])
							? null
							: $resource_full_class
						)
					) : 'Illuminate\Http\Response'),
					'args' => isset($datas['store']['args']) ? $datas['store']['args'] : [
						'request' => $request_full_store_class,
					],
					'request' => isset($datas['store']['request']) && is_string($datas['store']['request']) ? $datas['store']['request'] : null,
					'with' => isset($datas['store']['with']) ? $datas['store']['with'] : null,
					'access' => isset($datas['store']['access']) ? $datas['store']['access'] : 'public',
					'description' => isset($datas['store']['description']) ? $datas['store']['description'] : 'Store a newly created resource in storage.',
					// 'code' => ,
					// 'return' => isset($datas['store']['return']) ? $datas['store']['return'] : 'new ' . $resource_class . "(\n\t\t" . $repository_this_property . "->store(\$request->all())\n\t)"
				];
				if(((!isset($datas['store']['return']) && $is_api) || (!$is_api && !isset($datas['store']['code']))) && isset($datas['store']['with']) && (is_string($datas['store']['with']) || is_array($datas['store']['with']) && !empty($datas['store']['with'])) ){
					$with = is_string($datas['store']['with']) ? [$datas['store']['with'] => []] : $call_col($datas['store']['with'], false, false, []);
					$rec = [];
					foreach ($with as $model => $value) {
						// code...
						$model = Str::snake(Str::singular($model));
						$value['local_key'] = isset($value['local_key']) && is_string($value['local_key']) ? $value['local_key'] : $model . '_id';
						$value['primary_key'] = isset($value['primary_key']) && is_string($value['primary_key']) ? $value['primary_key'] : 'id';
						$value['controller'] = isset($value['controller']) && is_string($value['controller']) ? $value['controller'] : ucfirst(Str::camel($model)) . 'Controller';
						$value['request'] = isset($value['request']) && is_string($value['request']) ? $value['request'] : $request_namespace . '\\' . ucfirst(Str::camel($model)) . 'StoreRequest';
						$arg_r = Str::snake((function($class_){preg_match('/.* as (.*)$|.*\\\(.*)$|.*/i', $class_, $m); return end($m);})($value['request']));

						$rec[] = "'" . $value['local_key'] . "' => app()\n\t\t\t\t->make(" . $value['controller'] . "::class)\n\t\t\t\t->store($" . $arg_r . ")\n\t\t\t\t->" . $value['primary_key'];
						$datas['store']['args'][$arg_r] = $value['request'];
					}
					$ret = [];
					foreach ($call_col($arguments['columns'], false, false) as $key => $value) {
						if($key != 'id' && !preg_match('/__.*/', $key)) $ret[] = $key;
					}
					$datas['store']['request'] = !isset($datas['store']['request']) ? ('only([' . implode(', ', array_map(function($v){return var_export($v, true);}, $ret)) . '])') : $datas['store']['request'];

					if(!$is_api){
						$datas['store']['code'] = $repository_this_property . "->store(\$request\n\t\t->merge([" . (!empty($rec) ? "\n\t\t\t" . str_replace("\n", "\n", implode("\n\t\t, \n\t\t", $rec)) . "\n\t\t" : null) . "])\n\t\t->" . (!isset($datas['store']['request']) ? 'all()' : $datas['store']['request']) . "\n\t);"
						;
					}
					else $datas['store']['return'] = (!isset($datas['store']['collection']) || (is_bool($datas['store']['collection']) && $datas['store']['collection'] === false)
						? ('new ' . $resource_class)
						: (is_string($datas['store']['collection'])
							? $datas['store']['collection']
							: ($resource_class . "::collection")
						)
					) . "(\n\t\t" . $repository_this_property . "->store(\$request\n\t\t\t->merge([" . (!empty($rec) ? "\n\t\t\t\t" . str_replace("\n", "\n\t", implode("\n\t\t\t, \n\t\t\t", $rec)) . "\n\t\t\t" : null) . "])" . "\n\t\t\t->" . $datas['store']['request'] . "\n\t\t)\n\t)"
					;
					// $datas['store']['args'][$arg_r] = $with['request'];
				}
				$datas['store']['code'] = isset($datas['store']['code'])
					? (is_callable($cl = $datas['store']['code']) && !is_string($cl)
						? $cl(...[$arguments, $all_keywords]) 
						: $datas['store']['code']
					) 
					: ($is_api
						? null
						: ($repository_this_property . "->store(\$request->" . (!isset($datas['store']['request']) ? 'all()' : $datas['store']['request']) . ");")
					)
					// : ["\$" . strtolower($repository_class) . "_model_instance = \$this->getById(\$id);\n", "\tforeach(\$fields as \$property => \$value)", "\t\t\$" . strtolower($repository_class) . "_model_instance->\$property = \$value;\n", "\t\$" . strtolower($repository_class) . "_model_instance->save();\n"],
				;
				$datas['store']['return'] = isset($datas['store']['return']) ? $datas['store']['return'] : ($is_api ? ((!isset($datas['store']['collection']) || (is_bool($datas['store']['collection']) && $datas['store']['collection'] === false)
						? ('new ' . $resource_class)
						: (is_string($datas['store']['collection'])
							? $datas['store']['collection']
							: ($resource_class . "::collection")
						)
					) . "(\n\t\t" . $repository_this_property . "->store(\$request->" . (!isset($datas['store']['request']) ? 'all()' : $datas['store']['request']) . ")\n\t)") : "back()->with('message', '" . $model_class . " Created Successfully')")
				;
			// STORE  STOP  .......
			
			// SHOW  START  .......
				$datas['show'] = [
					'type_returned' => isset($datas['show']['type_returned']) ? $datas['show']['type_returned'] : ($is_api  ? (isset($datas['show']['collection']) && $datas['show']['collection'] === true
						? 'Illuminate\Http\Resources\Json\AnonymousResourceCollection'
						: (isset($datas['show']['collection']) && is_string($datas['show']['collection'])
							? null
							: $resource_full_class
						)
					) : null),
					'args' => isset($datas['show']['args']) ? $datas['show']['args'] : [
						$model_property => $model_full_class
					],
					'access' => isset($datas['show']['access']) ? $datas['show']['access'] : 'public',
					'description' => isset($datas['show']['description']) ? $datas['show']['description'] : ($is_api ? 'Display the specified resource.' : function(...$r){extract(end($r)); $dts = ['Display the specified resource.', '']; if(!empty($r[1]['args'])){$r[1]['args'] = array_map(function($el){preg_match('/(.*) =.*|(.*)=.*|.*/i', $el, $m); return end($m);}, $r[1]['args']); $dts = array_merge($dts, explode("\n", "@param " . implode("\n@param ", $r[1]['args']))); } $dts[] = "@return Illuminate\Http\Response|Illuminate\Contracts\View\View|Closure|string"; return $dts;}),
					// 'description' => isset($datas['show']['description']) ? $datas['show']['description'] : ('Display the specified resource.' . (!$is_api ? "\n\n@return \Illuminate\Http\Response|string" : null)),
					'code' => isset($datas['show']['code']) ? (is_callable($cl = $datas['show']['code']) && !is_string($cl) ? $cl(...[$arguments, $all_keywords]) : $datas['show']['code']) : null,
					'return' => isset($datas['show']['return']) ? $datas['show']['return'] : ($is_api ? ((!isset($datas['show']['collection']) || (is_bool($datas['show']['collection']) && $datas['show']['collection'] === false)
						? ('new ' . $resource_class)
						: (is_string($datas['show']['collection'])
							? $datas['show']['collection']
							: ($resource_class . "::collection")
						)
					) . "(\n\t\t" . $repository_this_property . "->getById($" . $model_property . "->id)\n\t)") : 'view(' . (isset($datas['show']['view']) ? $datas['show']['view'] : '"' . Str::plural($model_property) . '.show"') . ", " . (isset($datas['show']['view.datas']) ? $datas['show']['view.datas'] : '$' . $model_property) . ')')
				];
			// SHOW  STOP  .......
			
			// EDIT  START  .......
				if(!$is_api || ($is_api && isset($datas['edit'])))
					$datas['edit'] = [
						'type_returned' => isset($datas['edit']['type_returned']) ? $datas['edit']['type_returned'] : ($is_api  ? null : null),
						'args' => isset($datas['edit']['args']) ? $datas['edit']['args'] : [
							$model_property => $model_full_class
						],
						'access' => isset($datas['edit']['access']) ? $datas['edit']['access'] : 'public',
						'description' => isset($datas['edit']['description']) ? $datas['edit']['description'] : ($is_api ? 'Show the form for editing the specified resource.' : function(...$r){extract(end($r)); $dts = ['Show the form for editing the specified resource.', '']; if(!empty($r[1]['args'])){$r[1]['args'] = array_map(function($el){preg_match('/(.*) =.*|(.*)=.*|.*/i', $el, $m); return end($m);}, $r[1]['args']); $dts = array_merge($dts, explode("\n", "@param " . implode("\n@param ", $r[1]['args']))); } $dts[] = "@return Illuminate\Http\Response|Illuminate\Contracts\View\View|Closure|string"; return $dts;}),
						// 'description' => isset($datas['edit']['description']) ? $datas['edit']['description'] : ('Display the specified resource.' . (!$is_api ? "\n\n@return \Illuminate\Http\Response|string" : null)),
						'code' => isset($datas['edit']['code']) ? (is_callable($cl = $datas['edit']['code']) && !is_string($cl) ? $cl(...[$arguments, $all_keywords]) : $datas['edit']['code']) : null,
						'return' => isset($datas['edit']['return']) ? $datas['edit']['return'] : ($is_api ? null : 'view(' . (isset($datas['edit']['view']) ? $datas['edit']['view'] : '"' . Str::plural($model_property) . '.edit"') . ", " . (isset($datas['edit']['view.datas']) ? $datas['edit']['view.datas'] : '$' . $model_property) . ')')
					];
			// EDIT  STOP  .......
			
			// UPDATE  START  .......
				$datas['update'] = [
					'type_returned' => isset($datas['update']['type_returned']) ? $datas['update']['type_returned'] : ($is_api ? (isset($datas['update']['collection']) && $datas['update']['collection'] === true
						? 'Illuminate\Http\Resources\Json\AnonymousResourceCollection'
						: (isset($datas['update']['collection']) && is_string($datas['update']['collection'])
							? null
							: $resource_full_class
						)
					) : 'Illuminate\Http\Response'),
					'args' => isset($datas['update']['args']) ? $datas['update']['args'] : [
						// 'field', 'value', 'condition =\'=\'' => 'string'
						'request' => $request_full_update_class,
						$model_property => $model_full_class,
					],
					'access' => isset($datas['update']['access']) ? $datas['update']['access'] : 'public',
					'description' => isset($datas['update']['description']) ? $datas['update']['description'] : 'Update the specified resource in storage.',
					'code' => isset($datas['update']['code'])
						? (is_callable($cl = $datas['update']['code']) && !is_string($cl) ? $cl(...[$arguments, $all_keywords]) : $datas['update']['code'])
						: ($is_api
							? null
							: $repository_this_property . "->update(\$request->" . (!isset($datas['update']['request']) ? 'validated()' : $datas['update']['request']) . ", $" . $model_property . "->id);"
						)
					,
					'return' => isset($datas['update']['return']) ? $datas['update']['return'] : ($is_api ? ((!isset($datas['update']['collection']) || (is_bool($datas['update']['collection']) && $datas['update']['collection'] === false)
						? ('new ' . $resource_class)
						: (is_string($datas['update']['collection'])
							? $datas['update']['collection']
							: ($resource_class . "::collection")
						)
					) . "(\n\t\t" . $repository_this_property . "->update(\$request->" . (!isset($datas['update']['request']) ? 'validated()' : $datas['update']['request']) . ", $" . $model_property . "->id)\n\t)") : "back()->with('message', '" . $model_class . " Updated Successfully')")
				];
			// UPDATE  STOP  .......

			// ALL  GET FIND START .......
				$dt = config('compio-db.conf.helpers.get_migration_datas')($arguments, $call_col, config('compio-db.conf.foreign_default'));
				$search_code = [];
				foreach ($dt as $function_name => $value) {
					if(is_array($value['type']) && !empty($value['type']) && (function($types, $patterns){
						foreach ($types as $type)
							foreach($patterns as $pattern)
								if(preg_match('/' . $pattern . '/i', $type)) return true;
						return false;
					})(array_keys($value['type']), ['string', '.*text']) === true){
						$f_name = 'findBy'. ucfirst(Str::camel($function_name));
						$search_repo = 'getSearchBy'. ucfirst(Str::camel($function_name));
						if($is_api){
							$arg_ = Str::snake($function_name);
							$datas[$f_name] = [
								'type_returned' => isset($datas[$f_name]['type_returned']) ? $datas[$f_name]['type_returned'] : (!isset($datas[$f_name]['collection']) || $datas[$f_name]['collection'] === true
									? 'Illuminate\Http\Resources\Json\AnonymousResourceCollection'
									: (is_string($datas[$f_name]['collection'])
										? null
										: $resource_full_class
									)
								),
								'args' => isset($datas[$f_name]['args']) ? $datas[$f_name]['args'] : [
									'request' => 'Illuminate\Http\Request'
									// 'field', 'value', 'condition =\'=\'' => 'string'
								],
								'access' => isset($datas[$f_name]['access']) ? $datas[$f_name]['access'] : 'public',
								'description' => isset($datas[$f_name]['description']) ? $datas[$f_name]['description'] : 'Find by `' . $function_name . '`'/*'Get `' . $repository_class . '` by `' . $function_name . '`'*/,
								'return' => isset($datas[$f_name]['return']) ? $datas[$f_name]['return'] : (!isset($datas[$f_name]['collection']) || (is_bool($datas[$f_name]['collection']) && $datas[$f_name]['collection'] === true)
									? ($resource_class . "::collection")
									: (is_string($datas[$f_name]['collection'])
										? $datas[$f_name]['collection']
										: ('new ' . $resource_class)
									)
								) . "(\n\t\t" . $repository_this_property . "->" . $search_repo . "(\$request->" . (isset($datas[$f_name]['request']) ? $datas[$f_name]['request'] : ("input('" . $model_property . "_" . $function_name . "')")) . ")\n\t)"
								// 'return' => isset($datas[$f_name]['return']) ? $datas['exists']['return'] : null//"$" . $repository_property . "->get()"
								// 'return' => isset($datas[$f_name]['return']) ? $datas['exists']['return'] : "$" . $repository_property . "->orderBy('created_at','asc')->get()"
							];
							// $datas[$f_name]['code'] = isset($datas[$f_name]['code'])
							// 	? (is_callable($cl = $datas[$f_name]['code']) && !is_string($cl) ? $cl(...[$arguments, $all_keywords]) : $datas[$f_name]['code']) 
							// 	: '$' . $repository_property . " = " . $repository_class . "::query();\n\tif(!empty($" . $arg_ . "){\n\t\t$" . $repository_property . "->where('".$function_name."', 'LIKE', '%' . $" . $arg_ . " . '%');\n\t}";
						}
						else $search_code[] = "if(\$type == '*' || \$type == '" . $function_name . "') \$find['" . $function_name . "'] = " . $repository_this_property . "->" . $search_repo . "(\$search_" . $model_property . ");";
					}
				}
				if(!$is_api){
					$datas['find'] = [
						'type_returned' => isset($datas['find']['type_returned']) ? $datas['find']['type_returned'] : null,
						'args' => isset($datas['find']['args']) ? $datas['find']['args'] : ['search_' . $model_property, 'type = \'*\'' => 'string'],
						'access' => isset($datas['find']['access']) ? $datas['find']['access'] : 'public',
						'code' => isset($datas['find']['code']) ? $datas['find']['code'] : "\$find = [];\n\t". (!empty($search_code) ? implode("\n\t", $search_code) : null),
						'description' => isset($datas['find']['description']) ? $datas['find']['description'] : function(...$r){extract(end($r)); $dts = ['Search and display the resource found.', '']; if(!empty($r[1]['args'])){$r[1]['args'] = array_map(function($el){preg_match('/(.*) =.*|(.*)=.*|.*/i', $el, $m); return end($m);}, $r[1]['args']); $dts = array_merge($dts, explode("\n", "@param " . implode("\n@param ", $r[1]['args']))); } $dts[] = "@return Illuminate\Http\Response|Illuminate\Contracts\View\View|Closure|string"; return $dts;},
						'return' => isset($datas['find']['return']) ? $datas['find']['return'] : 'view(' . (isset($datas['find']['view']) ? $datas['find']['view'] : '"' . Str::plural($model_property) . '.find"') . ", ['find' => \$find, 'type' => \$type])",
					];
				}
			// ALL  GET FIND STOP .......
			
			// DESTROY  START  .......
				// if(isset($datas['destroy']) && $datas['destroy'] === true)
				$datas['destroy'] = [
					'type_returned' => isset($datas['destroy']['type_returned']) ? $datas['destroy']['type_returned'] : ($is_api ? 'Illuminate\Http\JsonResponse' : 'Illuminate\Http\Response'),
					'args' => isset($datas['destroy']['args']) ? $datas['destroy']['args'] : [
						$model_property => $model_full_class,
						// 'id',
					],
					'with' => isset($datas['destroy']['with']) ? $datas['destroy']['with'] : null,
					'access' => isset($datas['destroy']['access']) ? $datas['destroy']['access'] : 'public',
					'description' => isset($datas['destroy']['description']) ? $datas['destroy']['description'] : 'Remove the specified resource from storage.',
					// 'code' => isset($datas['destroy']['code']) ? (is_callable($cl = $datas['destroy']['code']) && !is_string($cl) ? $cl(...[$arguments, $all_keywords]) : $datas['destroy']['code']) : '$' . $model_property . '->delete();',
					'return' => isset($datas['destroy']['return']) ? $datas['destroy']['return'] : ($is_api ? 'response()->json(null)' : "back()->with('message', '" . $model_class . " Updated Successfully')")
				];
				if(!isset($datas['destroy']['code']) && isset($datas['destroy']['with']) && (is_string($datas['destroy']['with']) || is_array($datas['destroy']['with']) && !empty($datas['destroy']['with'])) ){
					$with = is_string($datas['destroy']['with']) ? [$datas['destroy']['with'] => []] : $call_col($datas['destroy']['with'], false, false, []);
					$rec = [];
					$import_class = isset($import_class) ? $import_class : [];
					// repos__ $args_ = ['id'];
					foreach ($with as $model => $value) {
						// repos__ $value['repository'] = isset($value['repository']) && is_string($value['repository']) ? $value['repository'] : $repository_namespace . '\\'. ucfirst(Str::camel($model)) . 'Repository';
						// repos__ $args_[Str::snake($model) . '_repository'] = $value['repository'];
						// repos__ $repository_ = (function($class_){preg_match('/.* as (.*)$|.*\\\(.*)$|.*/i', $class_, $m); return end($m);})($value['repository']);
						// repos__ $value['local_key'] = isset($value['local_key']) && is_string($value['local_key']) ? $value['local_key'] : Str::snake($model) . '_id';
						// repos__ $rec[] = '$' . Str::snake($model) . "_repository->delete(" . "$" . $model_property . '->' . $value['local_key'] . ")";
						// repos__ dump($args_);

						$value['model'] = isset($value['model']) && is_string($value['model']) ? $value['model'] : $model_namespace . '\\'. ucfirst(Str::camel($model));
						$model_ = (function($class_){preg_match('/.* as (.*)$|.*\\\(.*)$|.*/i', $class_, $m); return end($m);})($value['model']);
						$value['local_key'] = isset($value['local_key']) && is_string($value['local_key']) ? $value['local_key'] : Str::snake($model) . '_id';
						$rec[] = 'if($model = '. $model_ . '::find($' . $model_property . '->' . $value['local_key'] . ')) $model->delete();';
						$import_class[] = $value['model'];
					}
					// repos__ if(!empty($args_))
					// repos__ 	$datas['destroy']['args'] = isset($datas['destroy']['args']) ? $datas['destroy']['args'] : $args_;
					// repos__ $datas['destroy']['code'] = (!empty($rec) ? implode("\n\t", $rec) . "\n\t" : null) . $repository_this_property . "->delete(\$id);";
					$datas['destroy']['code'] = (!empty($rec) ? implode("\n\t", $rec) . "\n\t" : null) . "$" . $model_property . "->delete();";
				}
				// repos__ $datas['destroy']['args'] = isset($datas['destroy']['args']) ? $datas['destroy']['args'] : ['id'];
				// repos__ $datas['destroy']['code'] = isset($datas['destroy']['code']) ? (is_callable($cl = $datas['destroy']['code']) && !is_string($cl) ? $cl(...[$arguments, $all_keywords]) : $datas['destroy']['code']) : $repository_this_property . '->delete($id);';
				$datas['destroy']['code'] = isset($datas['destroy']['code']) ? (is_callable($cl = $datas['destroy']['code']) && !is_string($cl) ? $cl(...[$arguments, $all_keywords]) : $datas['destroy']['code']) : '$' . $model_property . '->delete();';
			// DESTROY  STOP  .......

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
				$current_file_content = str_replace('@controller_import_class', ('use ' . implode(";\nuse ", $import_class) . ";\n@controller_import_class"), $current_file_content);
				$current_file_content = str_replace('@controller_methods', $render . "\n", $current_file_content);
				file_put_contents($file_path, $current_file_content);
				return true;
			}
			return $render;
		},
		'@controller_import_class' => function(...$args){
			return config('compio-db.conf.helpers.import_class')($args, 'controller', [
				// '__eloquentModel',
				// '__eloquentDBCollection',
				// 'Illuminate\Contracts\Pagination\LengthAwarePaginator',
			]);
		},
	]
];