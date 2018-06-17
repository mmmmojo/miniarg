<?php

class ArgumentException extends \Exception {}
class MiniArg {
	public $flags = Array();
	public $posargs = Array();
	public $usage = '';

	public function __construct($options, $rules, $usage = '[OPTION]...') {
		$this->rules = $rules;
	$this->usage = $usage;
		foreach($options as $option) {
			if(!isset($option['takesValue'])) { $option['takesValue'] = false; }
			if(!isset($option['valName'])) { $option['valName'] = 'value'; }
			if(substr($option['id'], 0, 1) == '-') {
				$this->flags[$option['id']] = $option;
			} else {
				$this->posargs[] = $option;
			}
		}
	}
			
	public function parse($in) {
		$reading = false;
		$pos = 0;
		foreach($this->flags as $arg) { $out[$arg['id']] = false; }
		foreach($this->posargs as $arg) { $out[$arg['id']] = false; }
		foreach($in as $i => $arg) {
			if($arg == '-h' || $arg == '--help') {
				$this->printHelp();
				exit(0);
			}
			if($i == 0) { continue; }
			if($reading) {
				$out[$reading] = $arg;
				$reading = false;
			} else {
				if(substr($arg, 0, 1) == '-') {
					if(!isset($this->flags[$arg])) {
						throw new ArgumentException('Invalid argument: '.$arg);
					}
					$out[$arg] = true;
					if($this->flags[$arg]['takesValue']) {
						$reading = $arg;
					}
				} else {
					if($pos < count($this->posargs)) {
						$out[$this->posargs[$pos]['id']] = $arg;
						$pos++;
					} else {
						throw new ArgumentException('Too many positional arguments given');
					}
				}
			}
		}
		if($reading) { throw new ArgumentException('Missing value for '.$reading); }
		foreach($this->rules as $rule) {
			$count = 0;
			foreach($rule['args'] as $arg) {
				if($out[$arg]) { $count++; };
			}
			switch($rule['constraint']) {
			  case 'mutex':
				if($count > 1) {
					throw new ArgumentException('The following options are mutually exclusive: '.implode($rule['args'], ', '));
				}
				break;
			  case 'requireAtLeastOne':
				if($count < 1) {
					throw new ArgumentException('Must specify one of: '.implode($rule['args'], ', '));
				}
				break;
			  case 'requireAll':
				if($count < count($rule['args'])) {
					throw new ArgumentException('Must speficy all of: '.implode($rule['args'], ', '));
				}
				break;
			}
		}
		return $out;
	}


	function printHelp() {
		global $argv;
		//TODO: generate usage string based on the conditionals.	;-)
		echo('Usage: ' . $argv[0] . ' ' . $this->usage . "\n");
		echo("Options:\n");
		//TODO: pad output with spaces to guarantee alignment
		foreach($this->flags as $option) {
			echo("  " . $option['id']);
			if($option['takesValue']) {
					echo ' <' . $option['valName'] . '>';
			}
			if(isset($option['help'])) {
				echo("\t: " . $option['help']);
			}
			echo("\n");
		}
	}
}

$options = Array(
  Array('id' => '-t', 'takesValue' => false, 'help' => 'do nothing'),
  Array('id' => '--test', 'takesValue' => true),
  Array('id' => 'dest', 'takesValue' => false),
  Array('id' => '--this', 'takesValue' => false),
  Array('id' => '--that', 'takesValue' => false),
  Array('id' => '--m1', 'takesValue' => false),
  Array('id' => '--m2', 'takesValue' => false),
 );

$rules = Array(
  Array('constraint' => 'mutex', 'args' => Array('--m1', '--m2')),
  Array('constraint' => 'requireAtLeastOne', 'args' => Array('--m1', '--m2')),
  Array('constraint' => 'requireAll', 'args' => Array('--test', 'dest')),
 );

$arg = new MiniArg($options, $rules);
try {
	var_dump($arg->parse($argv));
} catch(ArgumentException $e) {
	echo($e->getMessage()."\n");
	$arg->printHelp();
	exit(1);
}

