<?php
define("DEBUG", false);
define("SERIAL", false);


ini_set("display_errors", 1);
error_reporting(-1);

include_once "objects.php";


$crawl = "";
if (SERIAL)
{
	$serial=new Enps();
	$serial->deviceSet("COM2");
}


if (DEBUG)
{
	$WA = new source("MediaResults.txt");
	$multnomah = new source("multnomah.htm");
	$washington = new source("washington.htm");
	$clackamas = new source("clackamas.htm");
	$sos = new source("sos_zero.htm");
}
else
{
	$WA = new source("http://vote.wa.gov/results/current/export/MediaResults.txt");
	$multnomah = new source("http://web.multco.us/elections/november-2012-general-election-election-results");
	$washington = new source("http://www.washingtoncountyelectionresults.com/");
	$sos = new source("http://oregonvotes.org");
	$clackamas = new source("http://www.clackamas.us/elections/results/Results20121106.html");
}

$raceLoader = new raceLoader();


//State of Washington, single source.
$output = $raceLoader->output_loader("AT-LocalRaces-WA", array(
	$raceLoader->csv_measure(	$WA, "106833	", "Initiative 1185 Tax Increase Approval"),
	$raceLoader->csv_measure(	$WA, "106837	", "Initiative 1240 Public Funded Charter Schools"),
	$raceLoader->csv_resolution($WA, "106813	", "Referendum 74 Same Sex Marriage"),
	$raceLoader->csv_measure(	$WA, "106465	", "Initiative 502 Decriminalizing Marijuana"),
	$raceLoader->csv_resolution($WA, "106705	", "Resolution 8221 State Debt Limit"),
	$raceLoader->csv_resolution($WA, "106701	", "Resolution 8223 University Investments"),
	$raceLoader->csv_revocation($WA, "107001	", "Advisory 1 Financial Institutions Tax"),
	$raceLoader->csv_revocation($WA, "107109	", "Advisory 2 Petroleum Tax Rate"),

	$raceLoader->csv_candidate($WA, '2', "U.S. Senator Washington", array(array('Maria Cantwell', 'Dem (i)'), array('Michael Baumgartner', 'GOP'))),
	$raceLoader->csv_candidate($WA, '5', "WA U.S. House Dist. 3", array(array('Jaime Herrera Beutler', 'GOP (i)'), array('Jon T. Haugen', 'Dem'))),
	$raceLoader->csv_candidate($WA, '13', "Washington Governor", array(array('Jay Inslee', 'Dem'), array('Rob McKenna', 'GOP (i)'))),
	$raceLoader->csv_candidate($WA, '14', "Washington Lt. Governor", array(array('Brad Owen', 'Dem (i)'), array('Bill Finkbeiner', "GOP"))),
	$raceLoader->csv_candidate($WA, '15', "WA Secretary of State", array(array('Kim Wyman', 'GOP'), array('Kathleen Drew', 'Dem'))),
	$raceLoader->csv_candidate($WA, '16', "WA State Treasurer", array(array('Jim McIntire', 'Dem (i)'), array('Sharon Hanek', 'GOP'))),
	$raceLoader->csv_candidate($WA, '18', "WA Attorney General ", array(array('Bob Ferguson', 'Dem'), array('Reagan Dunn', 'GOP'))),
	$raceLoader->csv_candidate($WA, '19', "Commissioner of Public Lands", array(array('Peter J. Goldmark', 'Dem (i)'), array('Clint Didier', 'GOP'))),
	$raceLoader->csv_candidate($WA, '21', "Insurance Commissioner", array(array('Mike Kreidler', 'Dem (i)'), array('John R. Adams', 'GOP'))),
	$raceLoader->csv_candidate($WA, '17', "State Auditor", array(array('Troy Kelley', 'Dem'), array('James Watkins', 'GOP')))

	));
	

echo "<PRE>" . $output->out . "</pre>";

if( SERIAL )
	$serial->send_and_close($output->out);


//Oregon - State wide
//If Secretary of state has results for Measure 80 that is greater than the combined counties, override with Secretary of state values.
$counties = $raceLoader->txt_measure_add(array(array($multnomah, "State Measure 80"), array($washington, "State Measure 80"), 
	array($clackamas, "<strong>80 Allows personal marijuana")), "Measure 80 - Legalize Marijuana");
$state = $raceLoader->txt_measure($sos, "Measure 80 - Legalize Marijuana", "<strong> State Ballot Measure No. 80");
$use_state = $raceLoader->compare_races($state, $counties);

$output = $raceLoader->output_loader("AT-OR-TopRaces-Glance-Sum",
	($use_state) 
	? array($state,
		$raceLoader->txt_measure($sos, "Measure 82 - Private Casinos", "<strong> State Ballot Measure No. 82"),
		$raceLoader->txt_measure($sos, "Measure 83 - Wood Village Casino", "<strong> State Ballot Measure No. 83"),
		$raceLoader->txt_candidate($sos, "Secretary of State", array(array('Seth Woolley', 'Seth Woolley, PAG'), array('Robert Wolfe', 'Robert Wolfe, Prg'), array('Bruce Alexander Knight', 'Bruce Knight, Lib'), array('Kate Brown', 'Kate Brown, Dem (i)'), array('Knute Buehler', 'Knute Buehler, GOP')), "<strong>Secretary of State"))
	: array($counties,
		$raceLoader->txt_measure_add(array(array($multnomah, "State Measure 82"), array($washington, "State Measure 82"), 
			array($clackamas, "82 Amends Constitution: Authorizes")), "Measure 82 - Private Casinos"),
		$raceLoader->txt_measure_add(array(array($multnomah, "State Measure 83"), array($washington, "State Measure 83"), 
			array($clackamas, "83 Authorizes privately-owned Wood Village")), "Measure 83 - Wood Village Casino"),
		$raceLoader->txt_candidate_add(array(array($multnomah, "Secretary of State"), array($washington, "Secretary of State"),
			array($clackamas, "<strong>Secretary of State")), "Secretary of State", array(array('Seth Woolley', 'Seth Woolley, PAG'), array('Robert Wolfe', 'Robert Wolfe, Prg'), array('Bruce Alexander Knight', 'Bruce Knight, Lib'), array('Kate Brown', 'Kate Brown, Dem (i)'), array('Knute Buehler', 'Knute Buehler, GOP')))
		));

echo "<PRE>" . $output->out . "</pre>";

if( SERIAL )
	$serial->send_and_close($output->out);

$output = $raceLoader->output_loader("AT-OR-Statewid-Glance-Sum",
	($use_state)
	? array($raceLoader->txt_candidate($sos, "Treasurer", array(array("Ted Wheeler", 'Ted Wheeler, Dem (i)'), array("Cameron Whitten", 'Cameron Whitten, Prg'), array("John F Mahler", 'John Mahler, Lib'), array("Michael Paul Marsh", 'Michael Marsh, CST'), array("Tom Cox", 'Tom Cox, GOP')), "<strong>State Treasurer"),
		$raceLoader->txt_candidate($sos, "Attorney General", array(array("Chris Henry", 'Chris Henry, PAG'), array("James L Buchal", 'James Buchal, GOP'), array("James E Leuenberger", 'James Leuenberger, CST'), array("Ellen Rosenblum", 'Ellen Rosenblum, Dem')), "<strong>Attorney General"),
		$raceLoader->txt_candidate($sos, "Labor Commissioner", array(array('Bruce Starr', 'Bruce Starr, NP'), array('Brad Avakian', 'Brad Avakian, NP')), "<strong> Commissioner of the Bureau of Labor and Industries")
		)
	: array($raceLoader->txt_candidate_add(array(array($multnomah, "State Treasurer"), array($washington, "State Treasurer"),
			array($clackamas, "<strong>State Treasurer")), "Treasurer", array(array("Ted Wheeler", 'Ted Wheeler, Dem (i)'), array("Cameron Whitten", 'Cameron Whitten, Prg'), array("John F Mahler", 'John Mahler, Lib'), array("Michael Paul Marsh", 'Michael Marsh, CST'), array("Tom Cox", 'Tom Cox, GOP'))),
		$raceLoader->txt_candidate_add(array(array($multnomah, "Attorney General"), array($washington, "Attorney General"),
			array($clackamas, "<strong>State Attorney General")), "Attorney General", array(array("Chris Henry", 'Chris Henry, PAG'), array("James L Buchal", 'James Buchal, GOP'), array("James E Leuenberger", 'James Leuenberger, CST'), array("Ellen Rosenblum", 'Ellen Rosenblum, Dem'))),
		$raceLoader->txt_candidate_add(array(array($multnomah, "Commissioner of the Bureau of Labor"), array($washington, "Commissioner of the Bureau of Labor"),
			array($clackamas, "<strong>State Commissioner of the Bureau of Labor")), "Labor Commissioner", array(array('Bruce Starr', 'Bruce Starr, NP'), array('Brad Avakian', 'Brad Avakian, NP')))
	
	));


echo "<PRE>" . $output->out . "</pre>";

if( SERIAL )
	$serial->send_and_close($output->out);

$output = $raceLoader->output_loader("AT-OR-MeasAll-Glance-Sum",
	($use_state) ? array(
		$raceLoader->txt_measure($sos, "Measure 81 - Gillnet Fishing", "<strong> State Ballot Measure No. 81"),
		$raceLoader->txt_measure($sos, "Measure 84 - Estate Taxes", "<strong> State Ballot Measure No. 84"),
		$raceLoader->txt_measure($sos, "Measure 85 - Kicker Rebates", "<strong> State Ballot Measure No. 85"))
	: array(
		$raceLoader->txt_measure_add(array(array($multnomah, "State Measure 81"), array($washington, "State Measure 81"), 
			array($clackamas, "<strong>81 Prohibits commercial non")), "Measure 81 - Gillnet Fishing"),
		$raceLoader->txt_measure_add(array(array($multnomah, "State Measure 84"), array($washington, "State Measure 84"), 
			array($clackamas, "<strong>84 Phases out inheritance taxes on large ")), "Measure 84 - Estate Taxes"),
		$raceLoader->txt_measure_add(array(array($multnomah, "State Measure 85"), array($washington, "State Measure 85"), 
			array($clackamas, "<strong>85 Amends Constitution: Allocates ")), "Measure 85 - Kicker Rebates"),

	));

echo "<PRE>" . $output->out . "</pre>";

if( SERIAL )
	$serial->send_and_close($output->out);

$output = $raceLoader->output_loader("AT-LocalRaces-OR", 
	($use_state) 
	? array(
		$raceLoader->txt_candidate($sos, "District 1 Northwest", array(array('Bob Ekstrom', 'Bob Ekstrom, CST'), array('Delinda Morgan', 'Delinda Morgan, GOP'), array('Suzanne Bonamici', 'Suzanne Bonamici, Dem (i)'), array('Steven Reynolds', 'Steven Reynolds, Prg')), "<strong>Representative in Congress, 1st District"),
		$raceLoader->txt_candidate($sos, "District 3 Portland Area", array(array('Earl Blumenauer', 'Earl Blumenauer, Dem (i)'), array('Woodrow Broadnax', 'Woodrow Broadnax, PAG'), array('Michael Cline', 'Michael Cline, Lib'), array('Ronald Green', 'Ronald Green, GOP')), "<strong>Representative in Congress, 3rd District"),
		$raceLoader->txt_candidate($sos, "District 5 Willamette and Coast", array(array('Kurt Schrader', 'Kurt Schrader, Dem (i)'), array('Fred Thompson', 'Fred Thompson, GOP'), array('Raymond Baldwin', 'Raymond Baldwin, CST'), array('Christina Jean Lugo', 'Christina Lugo, PAG')), "<strong>Representative in Congress, 5th District"))
	: array(
		$raceLoader->txt_candidate_add(array(array($multnomah, "Representative in Congress, 1st District"), array($washington, "Representative in Congress, 1st District")), 
			"District 1 Northwest", array(array('Bob Ekstrom', 'Bob Ekstrom, CST'), array('Delinda Morgan', 'Delinda Morgan, GOP'), array('Suzanne Bonamici', 'Suzanne Bonamici, Dem (i)'), array('Steven Reynolds', 'Steven Reynolds, Prg'))),
		$raceLoader->txt_candidate_add(array(array($multnomah, "Representative in Congress, 3rd District"), array($clackamas, "<strong>Representative in Congress, 3rd District")), 
			"District 3 Portland Area", array(array('Earl Blumenauer', 'Earl Blumenauer, Dem (i)'), array('Woodrow Broadnax', 'Woodrow Broadnax, PAG'), array('Michael Cline', 'Michael Cline, Lib'), array('Ronald Green', 'Ronald Green, GOP'))),
		$raceLoader->txt_candidate_add(array(array($multnomah, "Representative in Congress, 5th District"), array($clackamas, "<strong>Representative in Congress, 5th District")), 
			"District 5 Willamette and Coast", array(array('Kurt Schrader', 'Kurt Schrader, Dem (i)'), array('Fred Thompson', 'Fred Thompson, GOP'), array('Raymond Baldwin', 'Raymond Baldwin, CST'), array('Christina Jean Lugo', 'Christina Lugo, PAG')))
	));

echo "<PRE>" . $output->out . "</pre>";


if( SERIAL )
	$serial->send_and_close($output->out);

//Local Races

$output = $raceLoader->output_loader("AT-LocalRaces-OR", array(
		$raceLoader->txt_candidate($sos, "District 2 E and SW", array(array('Joyce B Segers', 'Joyce Segers, Dem'), array('Greg Walden', 'Greg Walden, GOP (i)'), array('Joe Tabor', 'Joe Tabor, Lib')), "<strong>Representative in Congress, 2nd District"),
		$raceLoader->txt_candidate($sos, "District 4 Southwestern", array(array('Peter A DeFazio', 'Peter DeFazio, Dem (i)'), array('Chuck Huntting', 'Chuck Huntting, Lib'), array('Art Robinson', 'Art Robinson, GOP')), "<strong>Representative in Congress, 4th District"),
		$raceLoader->txt_candidate_add(array(array($multnomah, "Mayor CITY OF PORTLAND"), array($washington, "Mayor PORTLAND CITY"),
			array($clackamas, "<strong>City of Portland, Mayor")), "Portland Mayor", array('Jefferson Smith', 'Charlie Hales')),
		$raceLoader->txt_candidate_add(array(array($multnomah, "Commissioner, Pos. 1 CITY OF PORTLAND"), array($washington, "Commissioner, Pos. 1 PORTLAND CITY"),
			array($clackamas, "<strong>City of Portland, Commissioner Position 1")), "Portland Comm Pos 1", array('Mary Nolan', 'Amanda Fritz')),
		$raceLoader->txt_measure_add(array(array($multnomah, "26-144 PORTLAND SCHOOL DISTRICT"), array($washington, "26-144 PORTLAND SCHOOL"),
			array($clackamas, "<strong>26-144 Portland Public School")), "Portland Public Schools"),
		$raceLoader->txt_measure_add(array(array($multnomah, "26-145 CITY OF PORTLAND"), array($washington, "26-145 PORTLAND CITY"),
			array($clackamas, "<strong>26-145 City of Portland: Amends Charter: Changes")), "Portland Fire-Police Disability-Retirement"),
		$raceLoader->txt_measure_add(array(array($multnomah, "26-146 CITY OF PORTLAND"), array($washington, "26-146 PORTLAND CITY"),
			array($clackamas, "<strong>26-146 City of Portland: Restore School Arts")), "Portland Arts Tax"),
		$raceLoader->txt_measure_add(array(array($multnomah, "3-405 CITY OF LAKE OSWEGO"), array($washington, "3-405 LAKE OSWEGO CITY"),
			array($clackamas, "<strong>3-405 City of Lake Oswego: Public Library")), "Lake Oswego Public Libraries"),
		$raceLoader->txt_measure_add(array(array($multnomah, "3-406 CITY OF LAKE OSWEGO"), array($washington, "3-406 LAKE OSWEGO CITY"),
			array($clackamas, "<strong>3-406 City of Lake Oswego: Boones Ferry")), "Boones Ferry Road"),
		$raceLoader->txt_candidate_add(array(array($multnomah, "Mayor CITY OF LAKE OSWEGO"), array($washington, "Mayor LAKE OSWEGO CITY"),
			array($clackamas, "<strong>City of Lake Oswego, Mayor")), "Lake Oswego Mayor", array('Kent Studebaker', 'Greg Macpherson')),
		$raceLoader->txt_candidate_add(array(array($multnomah, "City Councilor CITY OF LAKE OSWEGO "), array($washington, "City Councilor LAKE OSWEGO CITY"),
			array($clackamas, "<strong>City of Lake Oswego, Councilor")), "Lake Oswego Council", array("Skip O'Neill", 'Dan Williams', 'Terry Jordan', 'Bill Tierney', 'Karen Bowerman', 'on Gustafson')),
		$raceLoader->txt_candidate_add(array(array($multnomah, "City Councilor Position 1 CITY OF MILWAUKIE"), array($clackamas, "<strong>City of Milwaukie, Councilor Position 1")), 
			"Milwaukie Council Pos. 1", array('Mandy Zelinka Anderson', 'Richard S Cayo', 'Scott Churchill')),
		$raceLoader->txt_candidate_add(array(array($multnomah, "City Councilor Position 3 CITY OF MILWAUKIE"), array($clackamas, "<strong>City of Milwaukie, Councilor Position 3")), 
			"Milwaukie Council Pos. 3", array('Scott N Barbur', 'Mark Gamba'))
		));

echo "<PRE>" . $output->out . "</pre>";

if( SERIAL )
	$serial->send_and_close($output->out);

$output = $raceLoader->output_loader("AT-LocalRaces-OR", array(
	$raceLoader->txt_measure($multnomah, "Multnomah Co Library", "County Measure 26-143"),
	$raceLoader->txt_measure($multnomah, "Council Vote Recording", "26-147 CITY OF FAIRVIEW"),
	$raceLoader->txt_measure($multnomah, "Ordinance Process", "26-148 CITY OF FAIRVIEW"),
	$raceLoader->txt_measure($multnomah, "Filling Vacancies", "26-149 CITY OF FAIRVIEW"),
	$raceLoader->txt_measure($multnomah, "City Government", "26-141 CITY OF GRESHAM"),
	$raceLoader->txt_measure($multnomah, "Wood Village Casino", "26-142 CITY OF WOOD VILLAGE"),
	$raceLoader->txt_measure($clackamas, "Canby Measure 3-408 Annexation", "<strong>3-408"),	
	$raceLoader->txt_measure($clackamas, "Spending Growth Rate", "<strong>3-404"),	
	$raceLoader->txt_measure($clackamas, "Urban Growth Boundary", "<strong>3-411"),	
	$raceLoader->txt_measure($clackamas, "Measure 3-410 Annexation", "<strong>3-410"),
	$raceLoader->txt_measure($clackamas, "Rural Fire Protection", "<strong>3-409"),	
	$raceLoader->txt_measure($clackamas, "Sewer Rate Increase", "<strong>3-414"),	
	$raceLoader->txt_measure($clackamas, "Fire and Medical Levy", "<strong>3-402"),	
	$raceLoader->txt_measure($clackamas, "Police Services Levy", "<strong>3-403"),	
	$raceLoader->txt_measure($clackamas, "New Library Construction", "<strong>3-413"),	
	$raceLoader->txt_measure($clackamas, "Rural Fire Tax", "<strong>3-412"),	
	$raceLoader->txt_measure($clackamas, "Measure 3-415 Annexation", "<strong>3-415"),	
	$raceLoader->txt_measure($clackamas, "Urban Renewal Bonds", "<strong>3-407"),	
	$raceLoader->txt_measure($clackamas, "Measure 3-416 Annexation", "<strong>3-416"),	
	$raceLoader->txt_measure($clackamas, "Measure 3-417 Annexation", "<strong>3-417"),	
	$raceLoader->txt_measure($clackamas, "Measure 3-418 Annexation", "<strong>3-418"),	
	$raceLoader->txt_measure($clackamas, "Measure 3-419 Annexation", "<strong>3-419"),	
	$raceLoader->txt_measure($clackamas, "Govt. Camp Rd.", "<strong>3-420"),	
	$raceLoader->txt_measure($washington, "Municipal Court Charter", "34-200 CITY OF CORNELIUS"),
	$raceLoader->txt_measure($washington, "Vehicle Fuel Tax Repeal", "34-201 CITY OF CORNELIUS"),
	$raceLoader->txt_measure($washington, "Tonquin Annexation", "34-202 CITY OF SHERWOOD"),
	$raceLoader->txt_measure($washington, "Light Rail Vote", "34-203 CITY OF TIGARD"),
	$raceLoader->txt_measure($washington, "Police Response Levy", "34-198 ENHANCED SHERIFF'S PATROL DISTRICT")
	
	));

echo "<PRE>" . $output->out . "</pre>";

if( SERIAL )
	$serial->send_and_close($output->out);

$output = $raceLoader->output_loader("AT-LocalRaces-OR", array(
	$raceLoader->txt_candidate($clackamas, "Clackamas Comm Pos 1", array('Charlotte Lehan', 'John Ludlow'), "<strong>County Commissioner Position 1"),
	$raceLoader->txt_candidate($clackamas, "Clackamas Comm Pos 4", array('Tootie Smith', 'Jamie Damon'), "<strong>County Commissioner Position 4"),
	$raceLoader->txt_candidate($clackamas, "Canby Mayor", array('Brian D Hodson', 'Randy Carson'), "<strong>City of Canby, Mayor"),
	$raceLoader->txt_candidate($clackamas, "Canby City Council", array('Tim Dale', 'Traci Hensley', 'Ken Rider'), "<strong>City of Canby, Councilor"),
	$raceLoader->txt_candidate($clackamas, "Damascus Mayor", array('Steve Spinnett', 'Mary Wescott', 'Andrew Jackman'), "<strong>City of Damascus, Mayor"),
	$raceLoader->txt_candidate($clackamas, "Damascus Council Pos. 1", array('Bill Wehr', 'Kevin Reedy'), "<strong>City of Damascus, Councilor Position 1"),
	$raceLoader->txt_candidate($clackamas, "Damascus Council Pos. 2", array("Mel O'Brien", 'Don Arbuckle', 'Michael Hammons'), "<strong>City of Damascus, Councilor Position 2"),
	$raceLoader->txt_candidate($clackamas, "Damascus Council Pos. 6", array('Jim De Young', 'Dan Phegley'), "<strong>City of Damascus, Councilor Position 6"),
	$raceLoader->txt_candidate($clackamas, "Estacada Mayor", array('Pat Watkins', 'Brent Dodrill'), "<strong>City of Estacada, Mayor"),
	$raceLoader->txt_candidate($clackamas, "Estacada City Council", array('Curt Steininger', 'Edward L Smith', 'Sean Drinkwine'), "<strong>City of Estacada, Councilor"),
	$raceLoader->txt_candidate($clackamas, "Gladstone Council Pos. 4", array('Walt Fitch', 'Neal Reisner'), "<strong>City of Gladstone, Councilor Position 4"),
	$raceLoader->txt_candidate($clackamas, "Molalla Mayor", array('Jim Needham', 'Debbie Rogge'), "<strong>City of Molalla, Mayor"),
	$raceLoader->txt_candidate($clackamas, "Molalla City Council", array('Dennis Wise', 'Shane Potter', 'Jimmy Thompson', 'Glen Boreth', 'Chris Cook', 'Jason Griswold'), "<strong>City of Molalla, Councilor"),
	$raceLoader->txt_candidate($multnomah, "Fairview Council Pos. 1", array('Dan Kreamier', 'Leslie Moore'), "Council, Pos. 1 CITY OF FAIRVIEW"),
	$raceLoader->txt_candidate($multnomah, "Fairview Council Pos. 2", array('Barbara E Jones', 'Ken Quinby'), "Council, Pos. 2 CITY OF FAIRVIEW"),
	$raceLoader->txt_candidate($multnomah, "Fairview Council Pos. 6", array('Tamie Tlustos-Arnold', 'Brian L Cooper'), "Council, Pos. 6 CITY OF FAIRVIEW"),
	$raceLoader->txt_candidate($multnomah, "Gresham Council Pos. 1", array('Mario A Palmero', 'Jerry Hinton'), "Council, Pos. 1 CITY OF GRESHAM"),
	$raceLoader->txt_candidate($multnomah, "Gresham Council Pos. 3", array('Karylinn Echols', 'Richard A Strathern', 'John Deer', 'John W Dillow'), "Council, Pos. 3 CITY OF GRESHAM"),
	$raceLoader->txt_candidate($multnomah, "Gresham Council Pos. 5", array('Paul Warr-King', 'Mike McCormick'), "Council, Pos. 5 CITY OF GRESHAM"),
	$raceLoader->txt_candidate($multnomah, "Maywood Park City Council", array('Jim Akers', 'Don Meyer', 'Marci Marshall', 'Robert Burrow', 'Matthew Castor'), "Council CITY OF MAYWOOD PARK"),
	$raceLoader->txt_candidate($multnomah, "Troutdale Mayor", array('Doug Daoust', 'Jim Kight'), "Mayor CITY OF TROUTDALE"),
	$raceLoader->txt_candidate($multnomah, "Troutdale Council Pos. 6", array('Tom Slyter', 'John L Wilson', 'Zach Hudson'), "Councilor, Pos. 6 CITY OF TROUTDALE"),
	$raceLoader->txt_candidate($multnomah, "East Multnomah SWCD At Large", array('John Sweeney', 'Rick Till', 'Eric Mader', 'Justin Bauer', 'Kelly Caldwell'), "Director, At Large 1 SOIL AND WATER, EAST DISTRICT"),
	$raceLoader->txt_candidate($washington, "Forest Grove City Council", array('Richard Kidd', 'Aldie Howard', 'Victoria Johnson', 'Victoria Lowe', 'Elena Uhing'), "City of Forest Grove Council"),
	$raceLoader->txt_candidate($washington, "North Plains Council", array('Charlynn Newton', 'Robert Kindel', 'Teri Lenahan'), "City of North Plains Council"),
	$raceLoader->txt_candidate($washington, "Rivergrove City Council", array('Arne Nyberg', 'David Dull', array('Tuttle', 'William Tuttle')), "City Councilor RIVERGROVE CITY"),
	$raceLoader->txt_candidate($washington, "Sherwood Mayor", array('Bill Middleton', 'Keith Mays'), "City of Sherwood Mayor"),
	$raceLoader->txt_candidate($washington, "Tigard Mayor", array('Nick Wilson', 'John Cook'), "City of Tigard Mayor"),
	$raceLoader->txt_candidate($washington, "Tualatin Council Pos. 2", array('Monique Beikman', 'Jan Giunta'), "City of Tualatin Council Pos 2"),
	$raceLoader->txt_candidate($washington, "Tualatin SWCD At Large", array('Anthony Mills', 'Steven VanGrunsven'), "Water Director At Large 1"),

	
	));

echo "<PRE>" . $output->out . "</pre>";

if( SERIAL )
	$serial->send_and_close($output->out);

if( SERIAL )
	echo "<script language='javascript'>setTimeout(\"window.location='test.php'\", 15000);</script>";


?>