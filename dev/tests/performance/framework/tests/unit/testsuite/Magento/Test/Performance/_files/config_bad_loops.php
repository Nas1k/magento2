<?php
/**
 * @copyright Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 */

$result = require __DIR__ . '/config_data.php';
$result['scenario']['scenarios']['Scenario']['arguments'] = [
    \Magento\TestFramework\Performance\Scenario::ARG_LOOPS => 'A',
];
return $result;
