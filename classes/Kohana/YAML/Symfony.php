<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Symfony YAML driver. Parses and dumps YAML in native PHP (no extensions needed).
 *
 * @package    YAML
 * @category   Drivers
 * @author     Gabriel Evans <gabriel@codeconcoction.com>
 * @copyright  (c) 2010-2012 Gabriel Evans
 * @license    http://www.opensource.org/licenses/mit-license.php  MIT License
 */
class Kohana_YAML_Symfony extends YAML {

	/**
	 * @var Symfony\Component\Yaml\Parser
	 */
	protected $_parser;

	/**
	 * @var Symfony\Component\Yaml\Dumper
	 */
	protected $_dumper;

	/**
	 * Loads required Symfony YAML libraries.
	 */
	public function __construct()
	{
        $this->_parser = new Symfony\Component\Yaml\Parser();

		// Instantiate and store dumper
		$this->_dumper = new Symfony\Component\Yaml\Dumper();
	}

	public function parse($string)
	{
        return $this->_parser->parse($string);
	}

	public function dump($data, $inline = 0)
	{
		return $this->_dumper->dump($data, $inline);
	}

}
