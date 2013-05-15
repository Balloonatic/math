<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

$plugin_info = array (
	'pi_name' => 'Math',
	'pi_version' => '1.3.0',
	'pi_author' => 'Michael Leigeber',
	'pi_author_url' => 'http://www.caddis.co',
	'pi_description' => 'Use Math to execute PHP supported math formulas.',
	'pi_usage' => Math::usage()
);

class Math {

	public $return_data = '';

	public function __construct()
	{
		$this->EE =& get_instance();

		// Get formula
		$formula = $this->EE->TMPL->fetch_param('formula');

		$error = false;
		$result = '';

		if ($formula)
		{
			// Convert html entities to math characters
			$formula = html_entity_decode($formula);

			// Replace parameters
			$params = $this->EE->TMPL->fetch_param('params');
			$numeric_error = $this->EE->TMPL->fetch_param('numeric_error', 'Invalid input');

			if ($params)
			{
				$params = explode('|', $params);
				$i = 1;

				foreach ($params as $param)
				{
					if (! is_numeric($param))
					{
						$param = preg_replace('/\D/', '', $param);

						if (! is_numeric($param))
						{
							$this->return_data = $numeric_error;

							return;
						}
					}

					$formula = str_replace('[' . $i . ']', $param, $formula);

					$i++;
				}
			}

			if (! $error)
			{
				// Evaluate math
				@eval("\$result = $formula;");

				// Get settings
				$round = $this->EE->TMPL->fetch_param('round', false);
				$decimals = $this->EE->TMPL->fetch_param('decimals', false);
				$decimal_point = $this->EE->TMPL->fetch_param('dec_point', '.');
				$thousands_seperator = $this->EE->TMPL->fetch_param('thousands_seperator', ',');
				$absolute = $this->EE->TMPL->fetch_param('absolute');
				$trailing_zeros = $this->EE->TMPL->fetch_param('trailing_zeros', false);

				$decimal_digits = 0;

				// Absolute value
				if ($absolute)
				{
					$result = abs($result);
				}

				// Rounding
				if ($decimals !== false)
				{
					$mult = pow(10, $decimals);

					switch ($round)
					{
						case 'up':
							$result = round($result, $decimals, PHP_ROUND_HALF_UP);
							break;
						case 'ceil':
	   						$result = ceil($result * $mult) / $mult;
							break;
	   					default:
	   						$result = intval($result * $mult) / $mult;
					} 
				}

				$parts = explode('.', $result);

				$decimal_value = count($parts) > 1 ? $parts[1] : false;
				$decimal_digits = strlen($decimal_value);

				// Format response
				if ($decimals !== false)
				{
					$result = number_format((int)$parts[0], 0, $decimal_point, $thousands_seperator);

					if ($decimals > 0) 
					{
						if ($decimal_digits < $decimals && $trailing_zeros)
						{
							$result .= '.' . str_pad($decimal_value, $decimals, 0);
						}
						else
						{
							$result .= $decimal_value ? '.' . $decimal_value : '';
						}
					}
				}
				else
				{
					$decimals = $decimal_digits;

					$result = number_format((float)$result, $decimals, $decimal_point, $thousands_seperator);
				}
			}
		}

		$this->return_data = $result;
	}

	function usage()
	{
		ob_start();
?>
Parameters:

formula = '(5 * 2) / [1]'		// math formula (required) supports the following operators as well as bitwise + - * / % ++ -- < > <= => != <> ==
params = '{var}|{var2}'		// pipe delimited list of numeric parameters to be replaced into formula, recommended due to use of PHP eval (default: null)
decimals = '2' 					// sets the number of decimal points (default: "0")
decimal_point = '.' 				// sets the separator for the decimal point (default: ".")
thousands_seperator = ','		// sets the thousands separator; (default: ",")
absolute = 'yes'					// return the absolute number of the result (defaults: "no")
round = 'up|down|ceil'				// whether to round the result up or down, where up is standard rounding (defaults: no rounding)
numeric_error = 'Error'			// message returned when non-numeric parameters are provided (default: "Invalid input")
trailing_zeros = 'yes'			// include trailing 0 decimal places (defaults: "no")

Usage:

{exp:math formula="10 - 12" absolute="yes"} outputs 2
{exp:math formula="((4 * 5) / 2)" decimals="2"} outputs 10.00
{exp:math formula="([1] + 1) / [2]" params="{total_results}|2" round="down"} outputs 5 where {total_results} is 10
{exp:math formula="2/3" decimals="2" round="up"} outputs 0.67
<?php
		$buffer = ob_get_contents();

		ob_end_clean();

		return $buffer;
	}
}
?>