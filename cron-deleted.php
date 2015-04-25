<?php

$minutes_limit = 5;

require_once(__DIR__.'/database.php');

$listings = $db->query('SELECT id, product_id, meli_id, meli_url, meli_seller_id FROM listings WHERE status = "deleted" AND child_id IS NULL AND (last_check < DATE_SUB(NOW(), INTERVAL '.$minutes_limit.' MINUTE) OR last_check IS NULL) LIMIT 50');
$listings_index = [];

if ($listings->num_rows == 0)
{
	die('Nothing to work with');
}

while ($listing = $listings->fetch_assoc())
{
	$listings_index[str_replace('-','',$listing['meli_id'])] = $listing;
	
	// Detect if the URL redirects to a new offer:
	
	$url = $listing['meli_url'];
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Must be set to true so that PHP follows any "Location:" header
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$a = curl_exec($ch); // $a will contain all headers

	$url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
	if ($url != '')
	{
		$item_id = explode('/', $url);
		$item_id = explode('-', $item_id[3]);
		$item_id_dash = $item_id[0].'-'.$item_id[1];
		$item_id_joint = $item_id[0].$item_id[1];
	
		$db->query('INSERT INTO listings (product_id, meli_id, parent_id) VALUES ('.$listing['product_id'].', "'.$item_id_dash.'", '.$listing['id'].')');
		$last = $db->query('SELECT id FROM listings WHERE meli_id = "'.$item_id_dash.'" LIMIT 1');
                $last = $last->fetch_assoc();
		$db->query('UPDATE listings SET child_id = '.$last['id'].' WHERE id = '.$listing['id']);
	}
}
