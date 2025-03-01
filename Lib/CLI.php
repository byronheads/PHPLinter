<?php
/**
----------------------------------------------------------------------+
*  @desc			CLI version.
----------------------------------------------------------------------+
*  @file 			Lint_class.php
*  @author 			Jóhann T. Maríusson <jtm@robot.is>
*  @since 		    Oct 29, 2011
*  @package 		PHPLinter
*  @copyright     
*    phplinter is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    This program is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with this program.  If not, see <http://www.gnu.org/licenses/>.
----------------------------------------------------------------------+
*/
namespace PHPLinter; 
class CLI {
	/* @var Array */
	protected $options;
	/* @var String */
	protected $output_dir;
	/* @var String */
	protected $target;
	/* @var Array */
	protected $conf;
	/* @var Array */
	protected $use_rules;
	/* @var Array */
	protected $extensions;
	/* @var Array */
	protected $ignore;
	/* @var float */
	protected $penalty;
	/* @var Array */
	protected $stats;
	/* @var String */
	protected $settings_file;
	/* @var String */
	protected $source_view_url;
	/**
	----------------------------------------------------------------------+
	* @desc 	__construct. Defaults to use color.
	----------------------------------------------------------------------+
	*/
	public function __construct() {
		$this->options |= OPT_USE_COLOR;
		$this->extensions = 'php';
		$this->stats = array();
	}
	/**
	----------------------------------------------------------------------+
	* @desc 	Output help.
	----------------------------------------------------------------------+
	*/
	protected function help() {
		echo "PHPLinter. Lint and score PHP files.\n";
		echo "Usage phplinter -[ICWRESHMUvVTwc] -i[PATTERN] -e[PATTERN] -o[directory] -L[Source View URL] [file|directory]\n";
		echo "\t-U: Use rules-file FILE.\n";
		echo "\t-I: Report extra information (default off).\n";
		echo "\t-C: Dont report conventions.\n";
		echo "\t-F: Report formatting errors.\n";
		echo "\t-W: Dont report warnings.\n";
		echo "\t-R: Dont report refactor warnings.\n";
		echo "\t-E: Dont report errors.\n";
		echo "\t-D: Dont report documentation warnings.\n";
		echo "\t-X: Dont report security warnings.\n";
		echo "\t-O: Security report only.\n";
		echo "\t-M: View scope map.\n";
//		echo "\t-F: Try to locate unused functions/methods (Experimental).\n";
//		echo "\t-t: Number of invocations threshold (default 0).\n";
		echo "\t-S: Score only.\n";
		echo "\t-r: Use following rules (| delimited).\n";
		echo "\t-v: Verbose mode\n";
		echo "\t-q: Quiet mode (Surpress output)\n";
		echo "\t-V: Debug mode. Again for extra debug info.\n";
		echo "\t-T: Time execution. Again for extra time info.\n";
		echo "\t-c: Turn off color output.\n";
		echo "\t-e: Add extensions to valid list delimited by '|' (default 'php')\n";
		echo "\t-o: Output directory (with -H)\n";
		echo "\t-H: HTML report.\n";
		echo "\t-w: Overwrite output directory. (Warning: Will empty directory)\n";
		echo "\t-i: ignore PATTERN. (Ignore files in directory mode)\n";
		echo "\t-L: Create links to source view from URL (##FILE##, ##LINE##)(with -H)\n";
		echo "<jtm@robot.is>\n";
		exit;
	}
	/**
	----------------------------------------------------------------------+
	* @desc 	Process commandline options
	* @param	Array
	* @param	Int
	----------------------------------------------------------------------+
	*/
	public function process_options($argv, $argc) {
		for($i = 1;$i < $argc; $i++) {
			if($argv[$i][0] == '-') {
				$l = mb_strlen($argv[$i]);
				for($j=1; $j < $l; $j++) {
					switch($argv[$i][$j]) {
						case 'I':
							$this->options |= OPT_INFORMATION;
							break;
						case 'D':
							$this->options |= OPT_NO_DEPRICATED;
							break;
						case 'C':
							$this->options |= OPT_NO_CONVENTION;
							break;
						case 'F':
							$this->options |= OPT_FORMATTING;
							break;
						case 'W':
							$this->options |= OPT_NO_WARNING;
							break;
						case 'R':
							$this->options |= OPT_NO_REFACTOR;
							break;
						case 'E':
							$this->options |= OPT_NO_ERROR;
							break;
						case 'X':
							$this->options |= OPT_NO_SECURITY;
							break;
						case 'O':
							$this->options |= OPT_ONLY_SECURITY;
							break;
						case 'v':
							$this->options |= OPT_VERBOSE;
							break;
						case 'q':
							$this->options |= OPT_QUIET;
							break;
						case 'M':
							$this->options |= OPT_SCOPE_MAP;
							break;
						case 'V':
							if($this->options & OPT_DEBUG) {
								$this->options |= OPT_DEBUG_EXTRA;
							} else {
								$this->options |= OPT_DEBUG;
							}
							break;
						case 'T':
							if($this->options & OPT_DEBUG_TIME) {
								$this->options |= OPT_DEBUG_TIME_EXTRA;
							}
							else $this->options |= OPT_DEBUG_TIME;
							break;
						case 'S':
							$this->options |= OPT_SCORE_ONLY;
							break;
						case 'c':
							$this->options &= ~OPT_USE_COLOR;
							break;
						case 'H':
							$this->options |= OPT_HTML_REPORT;
							break;
						case 'F':
							$this->options |= OPT_FIND_FUNC;
							break;
						case 'w':
							$this->options |= OPT_OVERWRITE_REPORT;
							break;
						case 'r':
							$this->use_rules = $this->consume($argv, $i);
							continue 3;
						case 'i':
							$ignore = $this->consume($argv, $i);
							$this->ignore = $ignore;
							continue 3;
						case 't':
							$this->threshold = intval($argv[++$i]);
							continue 3;
						case 'o':
							$this->output_dir = $this->consume($argv, $i);
							continue 3;
						case 'U':
							$this->settings_file = $this->consume($argv, $i);
							continue 3;
						case 'L':
							$this->source_view_url = $this->consume($argv, $i);
							continue 3;
						case 'e':
							$ext = $this->consume($argv, $i);
							if(preg_match('/[a-z0-9\|]+/iu', $ext)) {
								$this->extensions .= '|'.$ext;
							} elseif(!empty($ext)) {
								$this->error('Extensions must include only letters and numbers');
							}
							continue 3;
						case 'h':
							$this->help();
						default:
							$this->error("Unrecognized option `{$argv[$i][$j]}`");
					}
				}
			} else {
				$this->target = $argv[$i];
			}
		}
	}
	/**
	----------------------------------------------------------------------+
	* @desc 	Consume next cli argument
	* @param	String
	----------------------------------------------------------------------+
	*/
	protected function consume($argv, &$i) {
		if(mb_strlen($argv[$i]) > 2) {
			return mb_substr($argv[$i], 2);
		}
		if(isset($argv[++$i])) return trim($argv[$i]);
		$i--;
		return false;
	}
	/**
	----------------------------------------------------------------------+
	* @desc 	Die with error message. Outputs help.
	* @param	String
	----------------------------------------------------------------------+
	*/
	protected function error($msg) {
		exit("$msg Try -h\n"); 
	}
	/**
	----------------------------------------------------------------------+
	* @desc 	Output message
	* @param	String
	----------------------------------------------------------------------+
	*/
	protected function msg($msg, $flag=OPT_VERBOSE) {
		if(!($this->options & OPT_QUIET)) {
			if($flag && !($this->options & $flag))
				return;
			echo $msg;
		}
	}
	/**
	----------------------------------------------------------------------+
	* @desc 	Analyse directory
	----------------------------------------------------------------------+
	*/
	protected function lint_directory() {
		$this->options |= OPT_SCORE_ONLY;
		$files = (isset($this->ignore))
			? Path::find($this->target, "/^.*?\.$this->extensions$/", $this->ignore)
			: Path::find($this->target, "/^.*?\.$this->extensions$/");
			
		$verbose = ($this->options & OPT_VERBOSE);	
		$this->penalty = 0;
		$numfiles = count($files);
		foreach($files as $_) {
			$this->msg("Linting file: $_\n");
			if($this->options & OPT_DEBUG_TIME_EXTRA) 
				$time = microtime(true);
			$linter = new PHPLinter($_, $this->options, $this->use_rules, 
									null, $this->settings_file);
			$report = $linter->lint();
			$penalty = $linter->penalty();
			$stats = array($_, $linter->score());
			if($this->options & OPT_HTML_REPORT) {
				if($_[0] !== '/') {
					$href = (preg_match('/^\.\//', $_)) 
							? $_ : "./$_";
				} else $href = $_;
				$reports[$href] = $report;
				$penaltys[$href] = $penalty;
			}
			$this->penalty += $penalty;
			$this->msg($this->reporter->score($penalty));
			if($this->options & OPT_DEBUG_TIME_EXTRA) {
				$x = microtime(true) - $time;
				$stats[] = $x;
				$this->msg("Time for file: $x seconds\n");	
			}
			$this->stats[] = $stats;
		}
		if($this->options & OPT_HTML_REPORT) {
			$this->reporter->toHtml($this->target, $reports, $penaltys, $this->source_view_url);
		}
		$cnt = count($this->stats);
		$this->msg("$cnt files, ", 0);	
		$this->msg($this->reporter->average($this->penalty, $numfiles), 0);
		$arr = array();
		foreach($this->stats as $_) $arr[] = $_[1];
		array_multisort($this->stats, SORT_NUMERIC, $arr);
		$this->msg("Worst: {$this->stats[0][0]} with {$this->stats[0][1]}\n", 0);
		if($this->options & OPT_DEBUG_TIME_EXTRA) {
			$arr = array();
			foreach($this->stats as $_) $arr[] = $_[2];
			$avg = array_sum($arr) / $cnt;
			echo "Avarage time per file: $avg seconds\n";	
		}
	}
	/**
	----------------------------------------------------------------------+
	* @desc 	Analyse single file
	* @param	String	Filename
	----------------------------------------------------------------------+
	*/
	protected function lint_file($file) {
		$linter = new PHPLinter($file, $this->options, $this->use_rules, 
								null, $this->settings_file);
		$report = $linter->lint();
		if(!($this->options & OPT_SCORE_ONLY) && !($this->options & OPT_QUIET)) {
			$this->reporter->toCli($report);
		}
		$this->msg($this->reporter->score($linter->penalty()), 0);
	}
	/**
	----------------------------------------------------------------------+
	* @desc 	Lint target
	----------------------------------------------------------------------+
	*/
	public function lint() {
		if(!isset($this->target) || !file_exists($this->target)) {
			$this->error('Need valid target...');
		}

		$this->reporter = new Report($this->output_dir, $this->options);
		if($this->options & OPT_DEBUG_TIME) 
			$time = microtime(true);

		if(is_dir($this->target)) {
			if($this->options & OPT_HTML_REPORT) {
				if(empty($this->output_dir)) {
					$this->error('No output directory selected...');
				}
				if($this->output_dir == $this->target) {
					$this->error('Output directory same as target, aborting...');
				}
				if(file_exists($this->output_dir) 
					&& !($this->options & OPT_OVERWRITE_REPORT)) 
				{
					$this->error('Output directory not empty, will not overwrite...');
				}
			}
			$this->lint_directory();
		} else {
			$this->lint_file($this->target);
		}
		
		if($this->options & OPT_DEBUG_TIME) {
			$x = microtime(true) - $time;
			$this->msg("Total time: $x seconds\n", OPT_DEBUG_TIME);	
		}
		if($this->options & OPT_DEBUG) {
			$mem =  memory_get_peak_usage(true);
			echo 'Peak memory use: ' . round($mem/1048576,2) . " MiB\n";
		}
	}
}
