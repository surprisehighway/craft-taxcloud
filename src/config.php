<?php

// This is an example config. Rename and move to config/tax-cloud.php

return [
	'apiId' => getenv('TAXCLOUD_API_ID'),
    'apiKey' => getenv('TAXCLOUD_API_KEY'),
    'verifyAddress' => false,
    'defaultShippingTic' => '11010',
];