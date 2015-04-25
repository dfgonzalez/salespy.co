<?php

$meli_base_url = 'https://api.mercadolibre.com/items?ids=';
$minutes_limit = 2;

require_once(__DIR__.'/database.php');
$listings = $db->query('SELECT id, meli_id, meli_price, meli_listing_type_id, meli_sold_quantity, last_check FROM listings WHERE status != "deleted" AND last_check < DATE_SUB(NOW(), INTERVAL '.$minutes_limit.' MINUTE) OR last_check IS NULL LIMIT 50');
$listings_index = [];

if ($listings->num_rows == 0)
{
	die('Nothing to work with');
}

while ($listing = $listings->fetch_assoc())
{
	$listings_index[str_replace('-','',$listing['meli_id'])] = $listing;
	$meli_base_url .= str_replace('-','',$listing['meli_id']) . ',';
}

$meli_request_url = substr($meli_base_url, 0, -1);

$ch = curl_init($meli_request_url);
curl_setopt($ch, CURLOPT_USERAGENT, "Salespy");
curl_setopt($ch, CURLOPT_TIMEOUT, 10); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$meli_response = json_decode (curl_exec ($ch));
curl_close($ch);

foreach ($meli_response as $meli_listing)
{
	// if its a new publication, save it and continue
	if ($listings_index[$meli_listing->id]['last_check'] == null)
	{
		$db->query('UPDATE listings SET meli_url = "'.$meli_listing->permalink.'", meli_title = "'.$meli_listing->title.'", meli_accepts_mercadopago = "'.$meli_listing->accepts_mercadopago.'", meli_price = '.$meli_listing->price.', meli_listing_type_id = "'.$meli_listing->listing_type_id.'", meli_available_quantity = '.$meli_listing->available_quantity.', meli_start_date = "'.$meli_listing->start_date.'", meli_stop_time = "'.$meli_listing->stop_time.'", meli_last_updated = "'.$meli_listing->last_updated.'", meli_seller_id = '.$meli_listing->seller_id.', meli_sold_quantity = '.$meli_listing->sold_quantity.', created = "'.date('Y-m-d H:i:s').'", last_check = "'.date('Y-m-d H:i:s').'" WHERE id = '.$listings_index[$meli_listing->id]['id']);
	} 
	else
	{

		//$db->query('UPDATE listings SET meli_title = "'.$meli_listing->title.'" WHERE id = '.$listings_index[$meli_listing->id]['id']);

		// Compare if there are changes on the price, listing type or sales
		if ($listings_index[$meli_listing->id]['meli_price'] != $meli_listing->price)
		{
			// echo 'in price';
			$db->query('UPDATE listings SET meli_price = '.$meli_listing->price.', updated = "'.date('Y-m-d H:i:s').'" WHERE id = '.$listings_index[$meli_listing->id]['id']);
			$db->query('INSERT INTO prices (listing_id, created, price, old_price) VALUES ('.$listings_index[$meli_listing->id]['id'].', "'.date('Y-m-d H:i:s').'", '.$meli_listing->price.', '.$listings_index[$meli_listing->id]['meli_price'].')');
		}

                if ($listings_index[$meli_listing->id]['meli_listing_type_id'] != $meli_listing->listing_type_id)
                {

			// echo 'in listing type';
                        $db->query('UPDATE listings SET meli_listing_type_id = "'.$meli_listing->listing_type_id.'", updated = "'.date('Y-m-d H:i:s').'" WHERE id = '.$listings_index[$meli_listing->id]['id']);
                        $db->query('INSERT INTO listing_types (listing_id, created, listing_type_id) VALUES ('.$listings_index[$meli_listing->id]['id'].', "'.date('Y-m-d H:i:s').'", "'.$meli_listing->listing_type_id.'")');
                }

		if ($listings_index[$meli_listing->id]['meli_sold_quantity'] != $meli_listing->sold_quantity)
                {
			// echo 'in sales';
                        $db->query('UPDATE listings SET meli_sold_quantity = '.$meli_listing->sold_quantity.', updated = "'.date('Y-m-d H:i:s').'" WHERE id = '.$listings_index[$meli_listing->id]['id']);
			for ($i=0; $i<($meli_listing->sold_quantity - $listings_index[$meli_listing->id]['meli_sold_quantity']); $i++)
			{
				// echo 'sale';
                        	$db->query('INSERT INTO sales (listing_id, created, price) VALUES ('.$listings_index[$meli_listing->id]['id'].', "'.date('Y-m-d H:i:s').'", '.$meli_listing->price.')');
			}
                }
		// Update the last_checked flag
		$status = null;
		if (is_array($meli_listing->sub_status) && ! empty($meli_listing->sub_status))
		{
			$status = $meli_listing->sub_status[0];
		}
		$db->query('UPDATE listings SET last_check = "'.date('Y-m-d H:i:s').'", meli_last_updated = "'.$meli_listing->last_updated.'", status = "'.$status.'" WHERE id = '.$listings_index[$meli_listing->id]['id']);
	}
}
