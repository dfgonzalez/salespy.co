<?php

$meli_base_url = 'https://api.mercadolibre.com/items/visits?ids={ids}&date_from={date_from}&date_to={date_to}';
$hour_limit = 23;

require_once(__DIR__.'/database.php');

$listings = $db->query('SELECT id, meli_id FROM listings WHERE last_check_visits < DATE_SUB(NOW(), INTERVAL '.$hour_limit.' HOUR) OR last_check_visits IS NULL AND status != "deleted" LIMIT 50');
$listings_index = [];
$collector = [];

if ($listings->num_rows == 0)
{
	die('Nothing to work with');
}

while ($listing = $listings->fetch_assoc())
{
	$listings_index[str_replace('-','',$listing['meli_id'])] = $listing;
	$collector[] = str_replace('-','',$listing['meli_id']);
}

$to_replace = ['{ids}', '{date_from}', '{date_to}'];
$with = [implode($collector, ','), date("Y-m-d", time() - 60 * 60 * 24), date("Y-m-d")];

$meli_request_url = str_replace ($to_replace, $with, $meli_base_url);

$ch = curl_init($meli_request_url);
curl_setopt($ch, CURLOPT_USERAGENT, "Salespy");
curl_setopt($ch, CURLOPT_TIMEOUT, 10); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$meli_response = json_decode (curl_exec ($ch));
curl_close($ch);

foreach ($meli_response as $meli_visits)
{
	if ($meli_visits->total_visits == 0 )
	{
		$db->query('INSERT INTO visits (date, listing_id, visits) VALUES ("'.$meli_visits->date_from.'", '.$listings_index[$meli_visits->item_id]['id'].', '.$meli_visits->total_visits.')');
	}
	else
	{
		$db->query('INSERT INTO visits (date, listing_id, visits) VALUES ("'.$meli_visits->date_from.'", '.$listings_index[$meli_visits->item_id]['id'].', '.$meli_visits->total_visits.')');
	}

	// Update the last_checked flag
	$db->query('UPDATE listings SET last_check_visits = "'.date('Y-m-d H:i:s').'" WHERE id = '.$listings_index[$meli_visits->item_id]['id']);
}
