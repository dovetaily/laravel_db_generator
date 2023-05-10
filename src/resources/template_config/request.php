<?php

return [
	'path' => [dirname(dirname(dirname(__DIR__))) . '\app\Http\Requests', dirname(dirname(dirname(__DIR__))) . '\app\Http\Requests'],
	'template_file' => dirname(dirname(dirname(__DIR__))) . '\app\Libs\Compio\templates-db\request.compio',
	'generate' => true,
	'convert_case' => ['camel', 'uf'],
	'change_file' => function(array $path_info, $file_index, $all_template_path){
		// $path_info['filename'] = Str::singular($path_info['filename']) . ($file_index === 0 ? 'Update' : ($file_index === 1 ? 'Store' : null)) . 'Request';
		$path_info['filename'] = Str::singular($path_info['filename']) . ($file_index === 0 ? 'Store' : ($file_index === 1 ? 'Update' : null)) . 'Request';
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
		'@model_namespace' => function(...$args){
			return isset($args[6]['model']['@model_namespace']) ? $args[6]['model']['@model_namespace'] : '';
		},
		'@request_namespace' => function($default_value, $template_datas, $arguments, $callback_format_value, $file_content, $file_path, $all_keywords){
			return 'App' . preg_replace('/^'.preg_quote(app_path()).'(.*)/', '$1', pathinfo($file_path)['dirname']);
		},
		'@request_class' => function(...$args){
			return pathinfo($args[5])['filename'];
			// return end($args[1]['path'])['filename'];
		},
		'@request_full_class' => function(...$args){
			return $args[1]['keywords']['@request_namespace']['result'] . '\\' . $args[1]['keywords']['@request_class']['result'];
		},
		'@request_extends' => function(...$args){
			return config('compio-db.conf.helpers.extend')($args, 'request', 'Illuminate\Http\Resources\Json\JsonResource','@request_import_class', '@request_extends');
		},
		'@request_implements' => function(...$args){
			return config('compio-db.conf.helpers.implement')($args, 'request', null, '@request_import_class', '@request_implements');
		},
		'@request_import_trait' => function(...$args){
			return config('compio-db.conf.helpers.import_trait')($args, 'request', [/*--traits--*/], '@request_import_class', '@request_import_trait');
		},
		'@request_datas' => function(...$args){
			$request_type = preg_match('/\\\.*' . preg_quote('storerequest.php') . '$/i', $args[5]) ? 'store' : 'update';
			$call_col = config('compio-db.conf.helpers.colone');

			$m_nsp = $args[6]['request']['@model_namespace'];
			$m_cs = $args[6]['request']['@model_class'];
			$m_f_cs = $args[6]['request']['@model_full_class'];
			$m_prop = Str::snake($args[6]['request']['@model_class']);
			$mt_prop = '$this->' . $m_prop;

			$datas = (function($args, $call_back, $request_type, $m_nsp){
				$ret = [];
				$cols = $call_back($args['columns']);
				foreach ((isset($cols['cols']) ? $cols['cols'] : []) as $column => $value) {
					$primary_key = isset($args['model']['primary_key']) ? $args['model']['primary_key'] : (isset($value['primary_key']) ? $value['primary_key'] : 'id');
					if($column != $primary_key && $column != 'created_at' && $column != 'updated_at' && $column != 'deleted_at' && $column != 'remember_token'){
						if($request_type == 'store'){
							$ret[$request_type]['rules'][$column][] = 'required';
							$ret[$request_type]['messages'][$column . '.required'] = '`' . $column . '` is required';
							if(preg_match('/(.*)_id$/i', $column, $m)){
								$ret[$request_type]['rules'][$column][] = 'exists:' . $m_nsp . '\\' . ucfirst(Str::camel(end($m))) . ',id';
								$ret[$request_type]['messages'][$column . '.required'] = '`' . end($m) . '` is required';
								$ret[$request_type]['messages'][$column . '.exists'] = '`' . end($m) . '` doesn\'t exists !';
							}
							// elseif(preg_match('/(.*)_id$/i', $column, $m)){
							// }
						}
						elseif($request_type == 'update'){
							$ret[$request_type]['rules'][$column] = null;
							$ret[$request_type]['messages'][$column] = null;
						}
						// elseif(!in_array($request_type, ['store', 'update'])){}
					}
				}
				return $ret;
			})($args[2], $call_col, $request_type, $m_nsp);
			$render = '';
			// dump($datas);exit;
			if(isset($args[2]['request'][$request_type]['#content']) && (is_string($args[2]['request'][$request_type]['#content']) || is_callable($args[2]['request'][$request_type]['#content'])))
				return !is_string($args[2]['request'][$request_type]['#content']) ? $args[2]['request'][$request_type]['#content'](...$args[2]) : $args[2]['request'][$request_type]['#content'];
			elseif(!isset($args[2]['request'][$request_type]['#content'])){
				$datas[$request_type]['rules'] = array_key_exists('rules', $rt = (isset($args[2]['request'][$request_type]) && ($rr = $args[2]['request'][$request_type]) ? $rr : []))
					? (!empty($rt['rules'])
						? $call_col($rt['rules'], false, false)
						: null
					)
					: (isset($datas[$request_type]['rules']) ? $datas[$request_type]['rules'] : '')
				;
				$datas[$request_type]['messages'] = array_key_exists('messages', $rt = (isset($args[2]['request'][$request_type]) && ($rr = $args[2]['request'][$request_type]) ? $rr : []))
					? (!empty($rt['messages'])
						? $call_col($rt['messages'], false, false)
						: null
					)
					: (isset($datas[$request_type]['messages']) ? $datas[$request_type]['messages'] : '')
				;
				$ret = [];
				$file_content = $args[4];
				foreach ($datas[$request_type] as $method => $value){
					$k_word = '@request_' . $method;
					$render = '[]';
					if(!empty($value)) $render = str_replace(["  ", "\n"], ["\t", "\n\t\t"], var_export($value, true));
					$file_content = str_replace($k_word, $render, $file_content);
				}
				file_put_contents($args[5], $file_content);
			}
			return true;
		},
		'@request_authorize' => function(...$args){
			$request_type = preg_match('/\\\.*' . preg_quote('storerequest.php') . '$/i', $args[5]) ? 'store' : 'update';
			$datas = !isset($args[2][$request_type]) || !array_key_exists('authorize', $args[2][$request_type]['request']) || $args[2][$request_type]['request']['authorize'] === true
				? 'return true;'
				: $args[2][$request_type]['request']['authorize']
			;
			return is_string($datas) ? str_replace("\n", "\n\t\t", $datas) : 'return false;';
		},

		'@request_import_class' => function(...$args){
			return config('compio-db.conf.helpers.import_class')($args, 'request', [
			]);
		},
	]
];