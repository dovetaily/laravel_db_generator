<?php

return [
	'path' => dirname(dirname(dirname(__DIR__))) . '\database\factories',
	'template_file' => dirname(dirname(dirname(__DIR__))) . '\app\Libs\Compio\templates-db\\' . (version_compare(Illuminate\Foundation\Application::VERSION, '8', '>=') ? 'factory.v.sup.8.compio' : 'factory.compio'),
	'generate' => true,
	'convert_case' => ['camel', 'uf'],
	'change_file' => function(array $path_info){
		$path_info['filename'] = Str::singular($path_info['filename']) . 'Factory';
		$path_info['basename'] = $path_info['filename'] . '.'. $path_info['extension'];
		$path_info['file'] = $path_info['dirname'] . '\\' . $path_info['basename'];
		$path_info['short'] = ($path_info['short_dirname'] == '' ? ('\\' . $path_info['filename']) : ($path_info['short_dirname'] . '\\' . $path_info['filename']));
		return $path_info;
	},



	'keywords' => [
		'@factory_namespace' => function($default_value, $template_datas, $arguments, $callback_format_value, $file_content, $file_path, $all_keywords){
			return 'App' . preg_replace('/^'.preg_quote(app_path()).'(.*)/', '$1', pathinfo($file_path)['dirname']);
			// return 'App\Repositories' . (($n = end($template_datas['path'])['short_dirname']) != '' ? ('\\' . $n) : null);
		},
		'@factory_class' => function($default_value, $template_datas, $arguments, $calback_format_value){
			return end($template_datas['path'])['filename'];
		},
		'@factory_full_class' => function(...$args){
			// dump($args[6]['model']['@model_full_class']);
			return $args[1]['keywords']['@factory_namespace']['result'] . '\\' . $args[1]['keywords']['@factory_class']['result'];
		},
		'@model_full_class' => function(...$args){
			return isset($args[6]['model']['@model_full_class']) ? $args[6]['model']['@model_full_class'] : '';
		},
		'@model_class' => function(...$args){
			return isset($args[6]['model']['@model_class']) ? $args[6]['model']['@model_class'] : '';
		},

		'@factory_implements' => function(...$args){
			return config('compio-db.conf.helpers.implement')($args, 'factory', null, '@factory_import_class', '@factory_implements');
		},
		'@factory_import_trait' => function(...$args){
			return config('compio-db.conf.helpers.import_trait')($args, 'factory', [
				'Illuminate\Database\Eloquent\Factories\HasFactory',
			], '@factory_import_class', '@factory_import_trait');
		},

		// '@factory_definition' => function(...$args){
		// 	return '\'dd\'';
		// },
		'@factory_definition' => function(...$args){
			$call_col = config('compio-db.conf.helpers.colone');
			$version = version_compare(Illuminate\Foundation\Application::VERSION, '8', '>=') ? true : false;
			$datas = (function($args, $call_back, $version){
				$ret = [];
				$cols = $call_back($args['columns']);
				$ff = $version ? '$this->faker' : '$faker';
				foreach ((isset($cols['cols']) ? $cols['cols'] : []) as $column => $value) {
					if(!in_array($column, ['id', 'created_at', 'deleted_at', 'updated_at'])){

						$type = ['string' => null];
						if(preg_match('/(.*)_id$/i', $column, $m)){
							$type = ['#model' => end($m)];
							// $ret[$column][] = 'new ' . ucfirst(Str::camel(end($m))) . 'Resource($this->' . $column . ')';
						}
						elseif(!isset($value['type']) && $column == 'id') $type = ['integer' => null];
						elseif(isset($value['type'])){
							$type = $call_back($value['type'], false, false);
							// dump('');
							// $ret[$column][] = '$this->' . $column;
						}
						$t = array_keys($type)[0];
						$modifiers = isset($value['modifiers']) ? $call_back($value['modifiers'], false, false) : [];
						$f = array_key_exists('unique', $modifiers) ? $ff . '->unique()' : $ff;


						if($t == '#model'){
							$ret[$column][] = '\App\Models\\' . ($c = ucfirst(Str::camel($type['#model']))) . '::inRandomOrder()->take(1)->first()->id';
						}
						elseif(($column == 'email' || $column == 'mail')) $ret[$column][] = $f . '->unique()->safeEmail';
						elseif($column == 'password') $ret[$column][] = '\Illuminate\Support\Facades\Hash::make(\'12345678\')';
						elseif($column == 'username' || $column == 'userName' || $column == 'user_name') $ret[$column][] = $f . '->userName';
						elseif($column == ($k = 'tld') || $column == ($k = 'ipv4') || $column == ($k = 'ipv6') || $column == ($k = 'name') || $column == ($k = 'emoji')) $ret[$column][] = $f . '->' . $k;
						elseif($column == 'phone_number' || $column == 'phone_numbers') $ret[$column][] = $f . '->e164PhoneNumber';
						elseif($column == 'remember_token' || $column == 'rememberToken') 
							$ret[$column][] = '\Illuminate\Support\Str::random(10)';
						elseif($t == 'string' || $t == 'tinyText' || $t == 'tiny') $ret[$column][] = $f . '->text(255)';
						elseif($t == 'longText') $ret[$column][] = $f . '->text(4294967295)';
						elseif($t == 'mediumText') $ret[$column][] = $f . '->text(16777215)';
						elseif($t == 'text') $ret[$column][] = $f . '->text(65535)';
						elseif($t == 'char'){
							if(is_array($type['char']) && count($type['char']) == 2 && is_numeric($v = end($type['char'])))
								$ret[$column][] = $f . '->asciify("'.(function(int $v){$r = '';for($i = 0; $i < $v;$i++) $r .= '*';return $r;})($v).'")';
							else $ret[$column][] = $f . '->asciify("*")';
						}
						elseif($t == ($k = 'decimal') || $t == ($k = 'float')){
							if(is_array($type[$k]) && count($type[$k]) >= 2){
								$int = $type[$k][1];
								$decimal = isset($type[$k][2]) ? $type[$k][2] : 0;
								$ret[$column][] = $f . '->randomFloat(/*$nbMaxDecimals = */' . $decimal . ', /*$min = */0, /*$max = */' . $f . '->regexify(\'[9]{' . $int . '}\'))';
							}
							else $ret[$column][] = $f . '->randomFloat';
						}
						elseif($t == 'double' || $t == 'bigInteger' || $t == 'bigIncrements' || $t == 'unsignedBigInteger') $ret[$column][] = $f . '->randomNumber';
						elseif($t == 'tinyInteger' || $t == 'tinyIncrements' || $t == 'unsignedTinyInteger') $ret[$column][] = 'random_int(0, 127)';
						elseif($t == 'smallInteger' || $t == 'smallIncrements' || $t == 'unsignedSmallInteger') $ret[$column][] = 'random_int(0, 65535)';
						elseif($t == 'mediumInteger' || $t == 'mediumIncrements' || $t == 'unsignedMediumInteger') $ret[$column][] = 'random_int(0, 16777215)';
						elseif($t == 'integer' || $t == 'increments' || $t == 'unsignedInteger') $ret[$column][] = 'random_int(0, 4294967295)';
						elseif($t == 'enum' && is_array($type['enum']) && count($type['enum']) == 2 && (is_string(end($type['enum'])) || is_array(end($type['enum'])))){
							$v = end($type['enum']);
							if(is_string($v)) eval('$v = ' . $v . ';');
							$ret[$column][] = str_replace(["\n", "\r", "\t", "  ", "( ", ",)"], ["", "", "", " ", "(", ")"], var_export($v, true)) . '[random_int(0, ' . (count($v) - 1) . ')]';
						}
						elseif($t == 'boolean') $ret[$column][] = 'random_int(0, 1)';
						elseif($t == 'json') $ret[$column][] = '"[]"';
						elseif($t == 'timestamp') $ret[$column][] = 'now()';
						elseif($t == ($k = 'year') || $t == ($k = 'time') || $t == ($k = 'date')) $ret[$column][] = $f . '->' . $k;
						else{ $ret[$column][] = $f . '->'; }
					}
				}
				return $ret;
			})($args[2], $call_col, $version);
			// $import_class = $datas['class'];
			// $datas = $datas['datas'];
			$render = '';
			if(isset($args[2]['factory']['#content']) && (is_string($args[2]['factory']['#content']) || is_callable($args[2]['factory']['#content'])))
				return !is_string($args[2]['factory']['#content']) ? $args[2]['factory']['#content'](...$args[2]) : $args[2]['factory']['#content'];
			elseif(!isset($args[2]['factory']['#content'])){
				$datas = array_merge_recursive($datas, isset($args[2]['factory']['datas']) ? $call_col($args[2]['factory']['datas'], false, false) : []);
				$ret = [];
				foreach ($datas as $column => $value){
					if(is_array($value) && !is_null(end($value)) && is_string(end($value)))
						$ret[] = "'" . $column . "' => " . end($value);
				}
				$r = "\n" . implode(",\n", $ret);
				$render = empty($ret) ? '' : (str_replace("\n", "\n\t\t" . ($version ? "\t" : null), $r) . "\n\t" . ($version ? "\t" : null));
			}
			return $render;
		},


		'@factory_import_class' => function(...$args){
			return config('compio-db.conf.helpers.import_class')($args, 'factory', [
			]);
		},
	]
];