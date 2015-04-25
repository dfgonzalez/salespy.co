<?php

require_once(__DIR__.'/database.php');

$hour_limit = 6;

$listings = $db->query('SELECT id, meli_id, meli_url FROM listings WHERE meli_url != "" AND (last_check_questions < DATE_SUB(NOW(), INTERVAL '.$hour_limit.' HOUR) OR last_check_questions IS NULL) AND status != "deleted" LIMIT 200');

$data = [];

if ($listings->num_rows == 0)
{
        $data['error'] = 'There are no listings to parse.';
}
else
{
	while ($listing = $listings->fetch_assoc())
	{
		$data[] = $listing['meli_url'];
	}
}

echo array2ul($data);

function array2ul($array) {
  $output = '<ul>';
  foreach ($array as $key => $value) {
    $function = is_array($value) ? __FUNCTION__ : 'htmlspecialchars';
    $output .= '<li>' . $key . ': <em>' . $function($value) . '</em></li>';
  }
  return $output . '</ul>';
}
