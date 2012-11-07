<?php
date_default_timezone_set("America/Los_Angeles");



include_once "php_serial.class.php";

class Enps extends phpSerial
{

	function send_and_close($msg)
	{
		$this->deviceOpen();
		$this->sendMessage($msg);
		$this->sendMessage("\r\n\r\n");
		$this->deviceClose();
	}

}

class Output
{



	public $crlf;
	public $handshake;
	public $sepNumRace;
	public $race;
	public $raceTrunk;
	public $raceNumber;
	public $out;

	function __construct($wire_slug)
	{
	 $this->crlf = chr(13).chr(10);
	 $this->handshake=$this->crlf;
	 $this->handshake .= chr(12).$this->crlf;
	 $this->handshake .= chr(4).chr(22).chr(22).chr(1).chr(111);
	 $this->sepNumRace=chr(31)."-----".chr(10)."u p".chr(20).chr(18)." ";
	 $this->race= $wire_slug;
	 $this->raceTrunk=substr($this->race,0,24);
	 $this->raceNumber="1250";
	 $this->out=$this->handshake."1033".$this->sepNumRace.$this->raceTrunk."     ".date("m-d")." ".$this->raceNumber;
	 $this->out.=$this->crlf.chr(2)."^".$this->race.",".$this->raceNumber."<".$this->crlf."^(AP) ".date("m-d-y H:i").",,";
	}
	
/*	function Add($race)
	{
	$this->out .= "<
^{$race->slug}
	   
	   357 of 1,000 precincts - 36 percent
	   
	   Yes ".number_format($race->yes())." - 50 percent
	   No ".number_format($race->no())." - 50 percent
	   <";	
	
	}
*/
function Add($race)
	{
	$this->out .= "<
^{$race->slug}
	   
	   357 of 1,000 precincts - 36 percent
	   \r\n";
	   foreach ($race->choices as $choice)
	   {
	   $this->out .= "	   {$choice->option} ".number_format($choice->value)." - 50 percent\r\n";
	   }
$this->out .= "	   <";	
	
	}
	
	function Finalize()
	{
		$this->out .= "\n\n\t".chr(3)."AP-NWE-".date("m-d-y")." 1500EDT<";
	}


	


	
}

class source
{
	public $url;
	public $RAW;
	public $useRAW = true;
	
	function __construct($arg1)
	{
		$this->url = $arg1;
		$this->load();
		
	}
	
	function load()
	{
		$this->RAW="";
		$R_elections = fopen($this->url, "r");
		while (!feof($R_elections))
		{
			$this->RAW .= str_replace(array("\n", "\r"), "", fread($R_elections, 8192));
		}
		fclose($R_elections);
		return TRUE;
	}
	
	function ToString()
	{
		return strip_tags($this->RAW);
	}
	
}

class race
{
	public $slug; 			//ENPS input string
	public $source_ref;		//object = source
	public $choices;
	public $total;
	
	function __construct($source)
	{
		//echo $source->url;
		$this->source_ref = $source;
		$choices = Array();
	}

	
	function parse()
	{
		if ($this->source_ref->useRAW)
			$hay = $this->source_ref->RAW;
		else
			$hay = $this->source_ref->ToString();
			
		$temp=0;
		foreach ($this->choices as $choice)
		{
			preg_match($choice->regex, $hay , $temp);
			//print_r($temp);
			//print_r($choice->regex);
			if (isset($temp[1]))
			$choice->Set($temp[1]);
			else
			{
			$choice->Set(0);
			//echo $choice->regex;
			}
		}
	
	}

}

class choice
{
	public $option;
	public $regex;
	public $value;
	
	function __construct($option, $regex)
	{
		//echo "$option \t $regex \n\r";
		$this->option = $option;
		$this->regex = $regex;
	}
	
	function Set($value)
	{
		$this->value = intval(str_replace(",","",$value));
		
	}

}	


class crawl
{
	public $name;
	public $total;
	public $choices;
	
	function __construct($race, $name)
	{
		$this->name = $name;
		
		$this->choices = Array();
		
		foreach ($race->choices as $choice)
		{
			$this->choices[] = clone $choice;
			$this->total = $choice->value + $this->total;
		}
		
	}
	
	function Combine($race)
	{
		for ($i=0; $i<(count($race->choices));$i++)
		{
			$this->choices[$i]->value =+ $race->choices[$i]->value;
			//echo "%i = ". $i. " value = " . $race->choices[$i]->value;
			$this->total =+  $race->choices[$i]->value;
			
		}
		//echo "total " . $this->total;
	}
	
	function output()
	{
		$return = $this->name . "   ";
		foreach ($this->choices as $choice)
		{
			$return .= $choice->option . "  -  " . 
			number_format($choice->value)."   " . 
			(($this->total) ? 
			round($choice->value / $this->total * 100, 0, PHP_ROUND_HALF_EVEN) : 
			(round(100/count($this->choices)) )). "%   ";
		}
		return $return;
	}

	function fight_to_the_death($race)
	{
		$_total = 0;//I want to declare the type here.
		foreach ($race->choices as $choice)
		{
			 $_total += $choice->value;

		}
		if ($this->total < $_total)
		{

			$this->total = $_total;
			$this->choices = null;
			foreach ($race->choices as $choice)
			{
				$this->choices[] = $choice;
			}
		}

	}
}
	
class raceSum extends race
{
	public $races = array();

	function addRace($race)
	{
		$this->races[] = $race;
	}

	function parse()
	{
		$options = array();
		
		$temp=0;
		foreach ($this->choices as $choice)
		{

			$options[$choice->option] = 0;
		}
		foreach ($this->races as $race)
		{
			if ($race->source_ref->useRAW)
				$hay = $race->source_ref->RAW;
			else
				$hay = $race->source_ref->ToString();
			foreach ($race->choices as $choice)
			{
				preg_match($choice->regex, $hay , $temp);
				if (isset($temp[1]))
				$options[$choice->option] += intval(str_replace(",","",$temp[1]));
			}
		}
		foreach ($this->choices as $choice)
		{
			$choice->value = $options[$choice->option];
		}
	}
}



class regexRace extends race
{
	public $regex;

//call new regexRace(source, universal regex)
	function __construct($arg1, $arg2)
	{
		$this->source_ref = $arg1;
		$this->regex = $arg2;
		$choices = Array();
	}

	function newCandidate($strName)
	{
		$this->choices[] = new choice($strName, "/" . $strName . $this->regex . "/");
	}
}


class raceLoader
{
	function txt_candidate($source, $slug, $candidates, $regex)  //overload candidates as multi-dimensional
	{
		$race_temp = new race($source);
		$race_temp->slug = $slug;
		foreach ($candidates as $candidate)
		{
			$race_temp->choices[] = new choice((is_array($candidate) ? $candidate[1] : $candidate), '/' . $regex .'.+?'. (is_array($candidate) ? $candidate[0] : $candidate) . '.+?([0-9,]+)/');
		}
		$race_temp->parse();
		return $race_temp;
	}

	function txt_measure($source, $slug, $search)
	{
		$empty = true;
		$i = 0;
		$match = array();
		

		$yes = '/' . $search .'.+?\WYes\W.+?([0-9,]+)/i';
		$no = '/' . $search .'.+?\WNo\W.+?([0-9,]+)/i';

		$race_temp = new race($source);
		
		$race_temp->choices[] = new choice ("Yes", $yes);
		$race_temp->choices[] = new choice ("No" , $no);
		$race_temp->slug = $slug;
		$race_temp->parse();
		return $race_temp;
	}

	function txt_candidate_add($array, $slug, $candidates) //array is multi dimensional collection of [$source, $regex]
	{
		//candidates overloaded as array
		$race_temp = new raceSum($array[0][0]);
		foreach ($candidates as $candidate)
		{
			$race_temp->choices[] = new choice((is_array($candidate) ? $candidate[1] : $candidate), (is_array($candidate) ? $candidate[0] : $candidate) . ".+?([0-9,]");
		}
		foreach ($array as $dim)
		{
			
			$race_temp->addRace($this->txt_candidate($dim[0], $slug, $candidates, $dim[1]));
		}
		$race_temp->slug = $slug;
		$race_temp->parse();
		return $race_temp;
	}
	function txt_measure_add($array, $slug, $search = "") //overloaded array as either source or [source,search]
	{
		if (is_array($array[0]))
		{
			$race_temp = new raceSum($array[0][0]);
			$race_temp->choices = $this->choice_loader_measure();
			foreach ($array as $dim) 
			{
				$race_temp->addRace($this->txt_measure($dim[0], $slug, $dim[1]));
			}
		}
		else
		{
			$race_temp = new raceSum($array[0]);
			foreach ($array as $source) 
			{
				$race_temp->addRace($this->txt_measure($source, $slug, $search));
			}	
		}
		$race_temp->slug = $slug;
		$race_temp->parse();
		return $race_temp;
		
	}

	function choice_loader_measure()
	{
		$choice_temp = array();
		$choice_temp[] = new choice("Yes", "/Yes/");
		$choice_temp[] = new choice("No", "/No/");
		return $choice_temp;
	}
		
			//######Washington Secretary of State methods#####\\

	function csv_candidate($source, $line_identifier, $slug, $candidates)
	{
		$race_temp = new race($source);
		$race_temp->slug = $slug;
		foreach ($candidates as $candidate)
		{
			$race_temp->choices[] = new choice((is_array($candidate) ? $candidate[0] . ', ' . $candidate[1] : $candidate), '/' . $line_identifier . '.+?' . (is_array($candidate) ? $candidate[0] : $candidate) . ".+?([0-9,]+)/");
		}
		$race_temp->parse();
		return $race_temp;
	}
	function csv_measure($source, $line_identifier, $slug)
	{
		$race_temp = new race($source);
		$race_temp->slug = $slug;
		$race_temp->choices[] = new choice("Yes", '/' . $line_identifier . ".+?Yes.+?([0-9,]+)/");
		$race_temp->choices[] = new choice("No", '/' . $line_identifier .  ".+?No\t([0-9,]+)/");
		$race_temp->parse();
		return $race_temp;
	}
	function csv_resolution($source, $line_identifier, $slug)
	{
		$race_temp = new race($source);
		$race_temp->slug = $slug;
		$race_temp->choices[] = new choice("Approved", '/' . $line_identifier . ".+?Approved.+?([0-9,]+)/");
		$race_temp->choices[] = new choice("Rejected", '/' . $line_identifier . ".+?Rejected.+?([0-9,]+)/");
		$race_temp->parse();
		return $race_temp;
	}
	function csv_revocation($source, $line_identifier, $slug)
	{
		$race_temp = new race($source);
		$race_temp->slug = $slug;
		$race_temp->choices[] = new choice("Repealed", '/'   . $line_identifier . ".+?Repealed.+?([0-9,]+)/");
		$race_temp->choices[] = new choice("Maintained", '/' . $line_identifier . ".+?Maintained.+?([0-9,]+)/");
		$race_temp->parse();
		return $race_temp;
	}

	function output_loader($slug, $array)
	{
		$output_temp = new output($slug);

		foreach($array as $race)
		{
			$output_temp->Add($race);
		}
		$output_temp->Finalize();
		return $output_temp;
	}

	function compare_races($race1, $race2)
	{
		$race1_total = 0;
		$race2_total = 0;
		foreach ($race1->choices as $choice)
		{
			$race1_total += $choice->value;
		}
		foreach ($race2->choices as $choice)
		{
			$race2_total += $choice->value;
		}	
		return ($race1_total > $race2_total);
	}
}

		

?>		