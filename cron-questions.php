<?php

require_once(__DIR__.'/database.php');

$kimono_request_url = 'https://www.kimonolabs.com/api/cp1tqlvu?apikey=efa2ccc7d85b7f04888c95b0744f1e1a';

$ch = curl_init($kimono_request_url);
curl_setopt($ch, CURLOPT_USERAGENT, "Salespy");
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$kimono_response = json_decode (curl_exec ($ch));
curl_close($ch);

$current_listing = [];

foreach ($kimono_response->results->collection1 as $result)
{
	if ($result->listing_id != '')
	{
		$current_listing['meli_id'] = substr($result->listing_id, 14);
		// Get listing_id
		$listing_result = $db->query('SELECT id FROM listings WHERE meli_id LIKE "%-'.$current_listing['meli_id'].'" LIMIT 1');
		
		if ($listing_result->num_rows > 0)
		{
			$current_listing['id'] = $listing_result->fetch_assoc()['id'];
		}
	}
	if ($current_listing['id'] != '')
	{
		// Get question FROM DB
		$questions_db = $db->query('SELECT id, listing_id, question, answer FROM questions WHERE listing_id = '.$current_listing['id'].' AND question = "'.$result->question.'" LIMIT 1');
		$answer = null;
                if (isset($result->answer) && $result->answer != '')
                {
                        $answer = $result->answer;
                }
		if ($questions_db->num_rows == 0 && isset($result->question))
		{
			$db->query('INSERT INTO questions (listing_id, question, answer, created) VALUES('.$current_listing['id'].', "'.$result->question.'", "'.$answer.'", "'.date('Y-m-d H:i:s').'")');
		}
		else
		{
			$question = $questions_db->fetch_assoc();
			if ($question['answer'] == '' && $answer != '')
			{
				$db->query('UPDATE questions SET answer = "'.$answer.'" WHERE id = '.$question['id']);
			}
		}
	}
}
