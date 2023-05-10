<?php

return [
	'path' => dirname(dirname(dirname(__DIR__))) . '\app\Http\Resources',
	'template_file' => dirname(dirname(dirname(__DIR__))) . '\app\Libs\Compio\templates-db\resource.compio',
	'generate' => true,
	'convert_case' => ['camel', 'uf'],
	'change_file' => function(array $path_info){
		$path_info['filename'] = Str::singular($path_info['filename']) . 'Resource';
		$path_info['basename'] = $path_info['filename'] . '.'. $path_info['extension'];
		$path_info['file'] = $path_info['dirname'] . '\\' . $path_info['basename'];
		$path_info['short'] = ($path_info['short_dirname'] == '' ? ('\\' . $path_info['filename']) : ($path_info['short_dirname'] . '\\' . $path_info['filename']));
		return $path_info;
	},

	'keywords' => [
		'@resource_namespace' => function($default_value, $template_datas, $arguments, $callback_format_value, $file_content, $file_path, $all_keywords){
			return 'App' . preg_replace('/^'.preg_quote(app_path()).'(.*)/', '$1', pathinfo($file_path)['dirname']);
		},
		'@resource_class' => function($default_value, $template_datas, $arguments, $calback_format_value){
			return end($template_datas['path'])['filename'];
		},
		'@resource_full_class' => function(...$args){
			return $args[1]['keywords']['@resource_namespace']['result'] . '\\' . $args[1]['keywords']['@resource_class']['result'];
		},
		'@resource_extends' => function(...$args){
			return config('compio-db.conf.helpers.extend')($args, 'resource', 'Illuminate\Http\Resources\Json\JsonResource','@resource_import_class', '@resource_extends');
		},
		'@resource_implements' => function(...$args){
			return config('compio-db.conf.helpers.implement')($args, 'resource', null, '@resource_import_class', '@resource_implements');
		},
		'@resource_import_trait' => function(...$args){
			return config('compio-db.conf.helpers.import_trait')($args, 'resource', [/*--traits--*/], '@resource_import_class', '@resource_import_trait');
		},
		'@resource_collects' => function(...$args){
			if(isset($args[2]['resource']['#content']) && (is_string($args[2]['resource']['#content']) || is_callable($args[2]['resource']['#content'])))
				return !is_string($args[2]['resource']['#content']) ? $args[2]['resource']['#content'](...$args[2]) : $args[2]['resource']['#content'];
			elseif(!isset($args[2]['resource']['#content']) && isset($args[2]['resource']['collects']) && is_string($args[2]['resource']['collects']) && !empty($args[2]['resource']['collects'])){
				if(preg_match('/^[a-z0-9_\\\]+\\\(.*)/i', ($class_ = $args[2]['resource']['collects']), $m)){
					$file_content = str_replace('@resource_import_class', ('use ' . $class_ . ";\n@resource_import_class"), $args[4]);
					$file_content = str_replace('@resource_collects', "\n\n\t/**\n\t * The resource that this resource collects.\n\t *\n\t * @var string\n\t */\n\tpublic \$collects = " . end($m) . "::class;\n", $file_content);
					file_put_contents($args[5], $file_content);
					return true;
				}
			}
			return '';
		},
		'@resource_wrap' => function(...$args){
			$render = '';
			if(isset($args[2]['resource']['#content']) && (is_string($args[2]['resource']['#content']) || is_callable($args[2]['resource']['#content'])))
				return !is_string($args[2]['resource']['#content']) ? $args[2]['resource']['#content'](...$args[2]) : $args[2]['resource']['#content'];
			elseif(!isset($args[2]['resource']['#content']) && isset($args[2]['resource']['wrap']) && is_string($args[2]['resource']['wrap']) && !empty($args[2]['resource']['wrap']))
				$render = "\n\n\t/**\n\t * The \"data\" wrapper that should be applied.\n\t *\n\t * @var string|null\n\t */\n\tpublic \$wrap = " . $args[2]['resource']['wrap'] . ";\n";
			return $render;
		},
		'@resource_datas' => function(...$args){
			$call_col = config('compio-db.conf.helpers.colone');
			$datas = (function($args, $call_back){
				$ret = [];
				$cols = $call_back($args['columns']);
				foreach ((isset($cols['cols']) ? $cols['cols'] : []) as $column => $value) {
					if(preg_match('/(.*)_id$/i', $column, $m)){
						$ret[$column][] = 'new ' . ucfirst(Str::camel(end($m))) . 'Resource($this->' . $column . ')';
					}
					else $ret[$column][] = '$this->' . $column;
				}
				return $ret;
			})($args[2], $call_col);
			$render = '';
			if(isset($args[2]['resource']['#content']) && (is_string($args[2]['resource']['#content']) || is_callable($args[2]['resource']['#content'])))
				return !is_string($args[2]['resource']['#content']) ? $args[2]['resource']['#content'](...$args[2]) : $args[2]['resource']['#content'];
			elseif(!isset($args[2]['resource']['#content'])){
				$datas = array_merge_recursive($datas, isset($args[2]['resource']['datas']) ? $call_col($args[2]['resource']['datas'], false, false) : []);
				$ret = [];
				foreach ($datas as $column => $value){
					if(is_array($value) && !is_null(end($value)) && is_string(end($value)))
						$ret[] = "'" . $column . "' => " . end($value);
				}
				$r = "\n" . implode(",\n", $ret);
				$render = empty($ret) ? '' : (str_replace("\n", "\n\t\t\t", $r) . "\n\t\t");
			}
			return $render;
		},

		'@resource_import_class' => function(...$args){
			return config('compio-db.conf.helpers.import_class')($args, 'resource', [
			]);
		},
	]
];