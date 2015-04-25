<?php

require_once (__DIR__.'/database.php');

$product_id = $_GET['id'];
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

$product = $db->query('SELECT id, name FROM products WHERE id = '.$product_id.' LIMIT 1');
$product = $product->fetch_assoc();

$listings_db = $db->query('SELECT id, meli_id, meli_url, meli_title, meli_price, meli_listing_type_id, meli_seller_id, meli_sold_quantity FROM listings WHERE meli_url != "" AND product_id = '.$product_id.' ORDER BY meli_sold_quantity DESC LIMIT 500');
$listings = [];
$listing_ids = [];
while ($listing = $listings_db->fetch_assoc())
{
	$listings[$listing['id']] = $listing;
	$listing_ids[] = $listing['id'];
}

$searches_db = $db->query('SELECT id, category_id, query, site, status FROM searches WHERE product_id = '.$product_id.' LIMIT 50');
$searches = [];
while ($search = $searches_db->fetch_assoc())
{
	$searches[] = $search;
}

$visits_db = $db->query('SELECT listing_id, date, visits FROM visits WHERE listing_id IN ('.implode(',',$listing_ids).') AND date >= "'.$date_from.'" AND date <= "'.$date_to.'" ORDER BY listing_id, date');
$visits_by_day = [];
$visits_by_listing = [];
$visits = 0;

while ($visit = $visits_db->fetch_assoc())
{
	$visits_by_day[$visit['date']] = $visits_by_day[$visit['date']] + $visit['visits'];
	$visits_by_listing[$visit['listing_id']] = $visits_by_listing[$visit['listing_id']] + $visit['visits'];
	$visits = $visits + $visit['visits'];
}

$sales_db = $db->query('SELECT listing_id, SUM(price) AS price, COUNT(*) AS sales FROM sales WHERE listing_id IN ('.implode(",",$listing_ids).') AND created >= "'.$date_from.'" AND created <= "'.$date_to.'" GROUP BY listing_id ORDER BY listing_id ASC LIMIT 500');
$sales = 0;
$sales_by_listing = [];
$sales_rev_by_listing = [];
while ($sale = $sales_db->fetch_assoc())
{
	$sales_by_listing[$sale['listing_id']] = $sales_by_listing[$sale['listing_id']] + $sale['sales'];
	$sales_rev_by_listing[$sale['listing_id']] = $sales_rev_by_listing[$sale['listing_id']] + $sales['price'];
	$sales = $sales + $sale['price'];
}

?>
<h1><?= $product['name'] ?></h1>

<dl>
<dt>Visits</dt>
<dd><?= $visits ?></dd>
<dt>Questions</dt>
<dd>-</dd>
<dt>Sales</dt>
<dd><?= $sales ?> AR$</dd>
</dt>

<table>
	<thead>
		<tr>
			<th>Seller</th>
			<th>Type</th>
			<th>Listing</th>
			<th>Visits</th>
			<th>Questions</th>
			<th>Sales</th>
		</tr>
	</thead>
	<tbody>
<?php
	foreach ($listings as $listing)
	{
		echo '<tr>';
		echo '<td>'.$listing['meli_seller_id'].'</td>';
		echo '<td>'.$listing['meli_listing_type_id'].'</td>';
		echo '<td><a href="listing.php?id='.$listing['id'].'">'.$listing['meli_title'].'</a> <a href="'.$listing['meli_url'].'" target="_blank">&uarr;</a></td>';
		echo '<td>'.$visits_by_listing[$listing['id']].'</td>';
		echo '<td>-</td>';
		echo '<td>'.$sales_by_listing[$listing['id']].' ('.$sales_rev_by_listing[$listing['id']].')</td>';
		echo '</tr>';
	}
?>
	</tbody>
</table>
