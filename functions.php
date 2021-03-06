<?php
require_once ("./index.php");

function Burn_Tokens($officialTokens, $voteEquation)
	{
	$asset = $officialTokens["Token"]; //Original asset
	$voteToken = $officialTokens["Vote"]; //Original Vote
	echo "<h4> The Chain Gang Token: <a href='http://api.blockscan.com/api2?module=asset&action=holders&name=$asset'><b>$asset</b></a> </h4>";
	echo "<h4>Vote Token : <a href='http://api.blockscan.com/api2?module=asset&action=holders&name=$voteToken'><b>$voteToken</b></a> </h4>";
	echo "<h4>Candidates must issue a numeric and an alphabet asset to the holders of :<a href='http://api.blockscan.com/api2?module=asset&action=holders&name=$asset'><b> $asset</b></h4></a>";
	echo " <h4>The candidate with the most <a href='http://api.blockscan.com/api2?module=asset&action=holders&name=$voteToken'><b>$voteToken</b></a> will become the next Official Token<h4>";
	echo " <br />";
	echo " Follow the instructions on <a href='https://github.com/Buskcoin/The-Chain-Gang'><b>Github</b></a> to join. ";
	echo "</div><div class='jumbotron offset'><b>";
	echo "<h3> Candidates: </h3>";
	$candidates = Find_Candidates($asset); //Search through assets for match that is distributed to all asset holders and returns an array.

	// var_dump($candidates);

	$poll = [];
	foreach($candidates as $newCan)
		{ //creating burn addresses that contain Vote & Token Name.
		$voteToken = $newCan["Vote"];
		$canToken = $newCan["Token"];
		if (isset($voteToken) && isset($canToken))
			{
			$validAddress = Burn_Prep($canToken, $voteToken);
			echo "<br /> <h4>To vote for $canToken send your vote Tokens to: <a>$validAddress </a> </h4>";
			$poll[$validAddress] = array(
				"Vote" => $voteToken,
				"Token" => $canToken
			);
			}
		  else
			{
			unset($newcan);
			}
		}

	// var_dump($poll);

	if ($candidates == NULL)
		{
		echo "<div class='margin offset'><span class='label label-warning center'>No Valid Candidates! :(</span></div>";

		// var_dump($officialTokens);

		die();
		}

	$officialTokens = Vote($officialTokens, $poll, $voteEquation);
	return $officialTokens;
	}

function Find_Candidates($asset)
	{
	$allowedIssuance = 5000;
	$minIssuance = $allowedIssuance / 100;
	$assetDetail = 'http://api.blockscan.com/api2?module=asset&action=info&name=';
	$asset_info = json_decode(file_get_contents($assetDetail . $asset) , true);
	$chain_token_info = $asset_info['data'];
	$assetHolders = 'http://api.blockscan.com/api2?module=asset&action=holders&name=';
	$jarr = json_decode(file_get_contents($assetHolders . $asset) , true);
	$Holders_of_Chain_token = $jarr['data'];
	$totalCount = $jarr['totalcount'];
	$array_length_of_chain_token = count($Holders_of_Chain_token);
	$Other_Assets = 'http://api.blockscan.com/api2?module=address&action=balance&btc_address=';
	$store = array();
	for ($i = 0; $i < $array_length_of_chain_token; ++$i)
		{
		$balance = $Holders_of_Chain_token[$i]['balance'];
		$compare = json_decode(file_get_contents($Other_Assets . $Holders_of_Chain_token[$i]['address']) , true);
		$arraylength = count(json_decode(file_get_contents($Other_Assets . $Holders_of_Chain_token[$i]['address']) , true) ['data']);
		for ($x = 0; $x < $arraylength; ++$x)
			{
			$compare_data = $compare['data'];
			$balances = $compare_data[$x]['balance'];
			$assets = $compare_data[$x]['asset'];
			if (($asset != $assets) & ($balances >= $balance))
				{
				$store[] = $assets;
				}
			}
		}

	$c = array_count_values($store);
	$var = array_keys($c, $array_length_of_chain_token);

	// echo var_dump($var);

	$viableAssets = Array();
	foreach($var as $thing)
		{
		$testing = $chain_token_info[0];
		$new = json_decode(file_get_contents($assetDetail . $thing) , true);
		$new_data = $new['data'];
		$the_data = $new_data[0];
		$circulation = $the_data["circulation"];
		$issuer = $the_data["issuer"];
		if (strcspn($thing, '0123456789') != strlen($thing))
			{
			$Vote = true;
			}
		  else
			{
			$Vote = false;
			}

		if ($testing["circulation"] + $allowedIssuance >= $circulation && $circulation >= $testing["circulation"] + $minIssuance)
			{
			if ($the_data["locked"] == "False")
				{

				// echo "<br /> WARNING! THIS ASSET: ( $thing ) IS NOT LOCKED <br />";

				}
			  else
			if ($Vote == false)
				{
				$viableAssets["$issuer"]["Token"] = $thing;
				if (isset($viableAssets["$issuer"]["Vote"]))
					{
					Pairs($viableAssets["$issuer"]);
					echo "<h4>Asset Stats</h4> <ul>";
					foreach($new_data[0] as $key => $val)
						{
						echo "<li> $key : $val </li> ";
						}

					echo "</ul>";
					}
				}
			elseif ($Vote == true)
				{
				$viableAssets["$issuer"]["Vote"] = $thing;
				if (isset($viableAssets["$issuer"]["Token"]))
					{
					Pairs($viableAssets["$issuer"]);
					echo "<h4>Asset Stats</h4> <ul>";
					foreach($new_data[0] as $key => $val)
						{
						echo "<li> $key : $val </li> ";
						}

					echo "</ul>";
					}
				}
			}
		}

	return $viableAssets;
	};

function Pairs($sets)
	{
	echo "<ul> Pair";

	//  Check type
	//  Scan through inner loop

	foreach($sets as $key => $value)
		{
		echo "<li> $key : $value </li> ";
		}

	echo "</ul>";
	}

function Burn_Prep($token, $vote)
	{
	$voteNumber = substr($vote, 1);
	$burn = "1" . $token . $voteNumber . "xxxxx";
	while (strlen($burn) < 33)
		{
		$burn = $burn . "x";
		}

	$validAddress = burn_address($burn);
	return $validAddress;
	}

function Extract_Vote($voteAddress)
	{
	$voteAddress = substr($voteAddress, 1, 26);
	$vote = strpbrk($voteAddress, 678912345);
	$vote = "A" . str_replace("o", 0, $vote);
	$vote = "A" . preg_replace("/[^0-9,.]/", "", $vote);
	return $vote;
	}

function Extract_Token($voteAddress)
	{
	$voteAddress = substr($voteAddress, 1, 25);
	$r = my_offset($voteAddress);
	$token = substr($voteAddress, 0, $r);
	return $token;
	}

function my_offset($text)
	{
	preg_match('/\d/', $text, $m, PREG_OFFSET_CAPTURE);
	if (sizeof($m)) return $m[0][1]; // 24 in your example

	// return anything you need for the case when there's no numbers in the string

	return strlen($text);
	}

?>
