<?php

$meli_base_url = 'https://api.mercadolibre.com/sites/{uppercase_site}/search?category={category_id}&q={query}';
$hour_limit = 12;

require_once(__DIR__.'/database.php');

$searches = $db->query('SELECT id, product_id, category_id, query, site FROM searches WHERE status = "active"  AND (last_check < DATE_SUB(NOW(), INTERVAL '.$hour_limit.' HOUR) OR last_check IS NULL)');

if ($searches->num_rows == 0)
{
	die ('Nothing to work with');
}

while ($search = $searches->fetch_assoc())
{

	$listings = $db->query('SELECT id, meli_id FROM listings WHERE product_id = '.$search['product_id']);
	$listings_index = [];

	if ($listings->num_rows == 0)
	{
		print ('No listings to work with for search id '.$search['id']);
	}

	while ($listing = $listings->fetch_assoc())
	{
		$listings_index[str_replace('-','',$listing['meli_id'])] = $listing;
	}

	$to_replace = ['{uppercase_site}', '{category_id}', '{query}'];
	$with = [strtoupper($search['site']), $search['category_id'], str_replace(' ','+',$search['query'])];

	$meli_request_url = str_replace ($to_replace, $with, $meli_base_url);

	$ch = curl_init($meli_request_url);
	curl_setopt($ch, CURLOPT_USERAGENT, "Salespy");
	curl_setopt($ch, CURLOPT_TIMEOUT, 10); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$meli_response = json_decode (curl_exec ($ch));
	curl_close($ch);

	foreach ($meli_response->results as $key=>$listing)
	{
		$position = $key + 1;
		if (isset($listings_index[$listing->id]))
		{
			$db->query('INSERT INTO positions (search_id, listing_id, date, position) VALUES ('.$search['id'].', '.$listings_index[$listing->id]['id'].', "'.date('Y-m-d').'", '.$position.')');
		}
	}

	// Update the last_checked flag
	$db->query('UPDATE searches SET last_check = "'.date('Y-m-d H:i:s').'" WHERE id = '.$search['id']);
}
