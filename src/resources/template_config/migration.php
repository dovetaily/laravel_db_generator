<?php

return [
	'path' => dirname(dirname(dirname(__DIR__))) . '\database\migrations',
	'template_file' => dirname(dirname(dirname(__DIR__))) . '\app\Libs\Compio\templates-db\migration.compio',
	'generate' => true,
	'convert_case' => 'd',
	// 'convert_case' => ['camel', 'uf'],
	'change_file' => function(array $path_info){
		// $path_info['filename'] = '2023_03_26_212151_create_' . Str::snake(Str::plural($path_info['filename'])) .'_table';
		$path_info['filename'] = date('Y_m_d_His') . '_create_' . Str::snake(Str::plural($path_info['filename'])) .'_table';
		sleep(1);
		$path_info['basename'] = $path_info['filename'] . '.'. $path_info['extension'];
		$path_info['file'] = $path_info['dirname'] . '\\' . $path_info['basename'];
		$path_info['short'] = ($path_info['short_dirname'] == '' ? ('\\' . $path_info['filename']) : ($path_info['short_dirname'] . '\\' . $path_info['filename']));
		return $path_info;
	},



	'keywords' => [
		'@class_name' => function(...$args){
			preg_match('/.*_create_(.*)_table.*$/i', $args[5], $table);
			return 'Create'. ucfirst(Str::camel(end($table))) . 'Table';
		},
		'@migration_extends' => function(...$args){
			return config('compio-db.conf.helpers.extend')($args, 'migration', 'Illuminate\Database\Migrations\Migration','@migration_import_class', '@migration_extends');
		},
		'@migration_implements' => function(...$args){
			return config('compio-db.conf.helpers.implement')($args, 'migration', null, '@migration_import_class', '@migration_implements');
		},
		'@migration_import_trait' => function(...$args){
			return config('compio-db.conf.helpers.import_trait')($args, 'migration', [
				// 'Illuminate\Database\Eloquent\Factories\HasFactory',
			], '@migration_import_class', '@migration_import_trait');
		},
		'@migration_table' => function(...$args){
			preg_match('/.*_create_(.*)_table.*$/i', $args[5], $table);
			return end($table);
		},
		'@migration_column' => function(...$args){

			$call_col = config('compio-db.conf.helpers.colone');
			$foreign_default = config('compio-db.conf.foreign_default');
			$datas = config('compio-db.conf.helpers.get_migration_datas')($args[2], $call_col, $foreign_default, isset($args[2]['migration']['modifiers']) ? $args[2]['migration']['modifiers'] : null);
			$import_class = [];
			$render = '';
			if(isset($args[2]['migration']['#content']) && (is_string($args[2]['migration']['#content']) || is_callable($args[2]['migration']['#content'])))
				return !is_string($args[2]['migration']['#content']) ? $args[2]['migration']['#content'](...$args[2]) : $args[2]['migration']['#content'];
			elseif(!isset($args[2]['migration']['#content'])){
				// dump($datas);exit;
				$ret = [];
				foreach ($datas as $column => $value) {
					$rr = '$table';
					foreach (['type', 'modifiers'] as $t){
						// if($column == 'id' && $t == 'type') $value[$t] = ['bigIncrements' => array_key_exists('bigIncrements', empty($value[$t]) ? [] : $value[$t]) ? $value[$t]['bigIncrements'] : '#'];
						if($column == 'id' && $t == 'type') $value[$t]['bigIncrements'] = array_key_exists('bigIncrements', empty($value[$t]) ? [] : $value[$t]) ? $value[$t]['bigIncrements'] : '#';
						if(!empty($value[$t])){
							foreach ($value[$t] as $type => $val) {
								$rr .= "->" . $type . "(" . (($r = $val) === null
									? var_export($column, true)
									: ($r === false
										? null
										: ($r !== '#'
											? (is_array($val) ? implode(', ', (function($val, $col){
												foreach ($val as $k => $value)
													$val[$k] = $value == '#' ? var_export($col, true) : $value;
												return $val;
											})($val, $column)) : $val)
											: var_export($column, true)
										)
									)
								) . ")";
							}
						}
					}

					$ret[] = ($rr == '$table' ? '// ' . $rr . "->type('" . $column . "')" : $rr) . ';';
				}
				return !empty($ret) ? "\n\t\t\t". implode("\n\t\t\t", $ret) : '';

			}
			return '';
		},
		'@migration_foreign' => function(...$args){

			$call_col = config('compio-db.conf.helpers.colone');
			$datas = [
				'default' => (function($args, $call_back){
					$ret = [];
					$cols = $call_back($args['columns'], false, false);
					foreach ($cols as $column => $value) {
						if(preg_match('/(.*)_id$/i', $column, $m)){
							$ret[current($m)] = [
								'table' => Str::plural(end($m)),
								'primary_key' => 'id',
								// 'type' => isset($value['type']) ? (is_string($value['type']) ? [$value['type'] => 'id'] : $value['type']) : ['unsignedBigInteger' => '#'],
							];
						}
					}
					// dump($cols);
					return $call_back($ret, false, false);
				})($args[2], $call_col),
				'new' => isset($args[2]['migration']['foreign']) && !empty($args[2]['migration']['foreign']) && is_array($args[2]['migration']['foreign'])
					? (function($dts){
						$ret = [];
						foreach ($dts as $key => $value) {
							$prg = preg_match('/(.*)_id$/i', $key, $m);
							$ret[$key] = is_array($value) ? $value : [];
							if($key == '#content')
								$ret[$key] = $value;
							else{
								$ret[$key]['table'] = isset($value['table']) ? $value['table'] : ($prg ? Str::plural(end($m)) : null);
								$ret[$key]['primary_key'] = isset($value['primary_key']) ? $value['primary_key'] : 'id';
							}
						}
						return $ret;
					})($call_col($args[2]['migration']['foreign'], false, false))
					: null
				,
			];
			$import_class = [];
			$render = '';
			if(isset($datas['new']['#content']) && (is_string($datas['new']['#content']) || is_callable($datas['new']['#content'])))
				return !is_string($datas['new']['#content']) ? $datas['new']['#content'](...$args[2]) : $datas['new']['#content'];
			elseif(!isset($datas['new']['#content'])){
				$datas = is_array($datas['new']) ? $datas['new'] : $datas['default'];
				$dts = [];
				foreach ($datas as $column => $value) {
					$dts[] = '$table->foreign(\'' . $column . '\')->references(\'' . $value['primary_key'] . '\')->on(\'' . $value['table'] . '\');';
				}
				$render = implode("\n\t\t\t", $dts);
					// dump($datas['new']);exit;
				// $dts = ;
			}
			return empty($render) ? '' : "\n\n\t\t\t". $render;
		},
		'@migration_properties' => function(...$args){
			$call_col = config('compio-db.conf.helpers.colone');
			$datas = isset($args[2]['migration']['properties']) ? $args[2]['migration']['properties'] : [];
			$render = '';
			$dts = [];
			foreach ($datas as $method => $value) {
				$dts[] = '$table->' . $method . '(' . (is_array($value) 
					? implode(',', $value)
					: (is_string($value)
						? $value
						: (is_string($rr = $value(...[$method, $args[2]]))
							? $rr
							: null
						)
					)
				) . ');';
			}
			return empty($dts) ? '' : "\n\t\t\t" . implode("\n\t\t\t", $dts);
		},
		'@migration_import_class' => function(...$args){
			return config('compio-db.conf.helpers.import_class')($args, 'migration', [
				'Illuminate\Database\Schema\Blueprint',
				'Illuminate\Support\Facades\Schema',
			]);
		},
	]
];