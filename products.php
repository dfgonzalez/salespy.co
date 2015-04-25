<?php
require_once (__DIR__.'/database.php');

$date_from = date("Y-m-d", time() - 60 * 60 * 24);
if ($_GET['date_from'])
{
        $date_from = $_GET['date_from'];
}
$date_to = date("Y-m-d");
if ($_GET['date_to'])
{
        $date_to = $_GET['date_to'];
}

$products = $db->query('SELECT id, name FROM products ORDER BY name ASC LIMIT 100');

echo '<ul>';

while ($product = $products->fetch_assoc())
{

	$listings_db = $db->query('SELECT id, meli_id, meli_url, meli_title, meli_price, meli_listing_type_id, meli_seller_id, meli_sold_quantity FROM listings WHERE meli_url != "" AND product_id = '.$product['id'].' ORDER BY meli_sold_quantity DESC LIMIT 500');
	$listing_ids = [];
	while ($listing = $listings_db->fetch_assoc())
	{
	        $listing_ids[] = $listing['id'];
	}
	
	$visits_db = $db->query('SELECT SUM(visits) AS visits FROM visits WHERE listing_id IN ('.implode(',',$listing_ids).') AND date >= "'.$date_from.'" AND date <= "'.$date_to.'" ORDER BY listing_id, date');
	$visits = $visits_db->fetch_assoc();

	$sales_db = $db->query('SELECT SUM(price) AS price, COUNT(*) AS sales FROM sales WHERE listing_id IN ('.implode(",",$listing_ids).') AND created >= "'.$date_from.'" AND created <= "'.$date_to.'" GROUP BY listing_id ORDER BY listing_id ASC LIMIT 500');
	$sales = $sales_db->fetch_assoc();

	echo '<li><a href="/product.php?id='.$product['id'].'">'.$product['name'].'</a> - '.$visits['visits'].' visits, '.$sales['sales'].' sales</li>';
}

echo '</ul>';
