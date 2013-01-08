<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'firewall_custom';
$app['version'] = '1.1.1';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = lang('firewall_custom_summary');
$app['description'] = lang('firewall_custom_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('firewall_custom_app_name');
$app['category'] = lang('base_category_network');
$app['subcategory'] = lang('base_subcategory_firewall');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-firewall-core'
);

$app['delete_dependency'] = array(
    'app-firewall-custom-core'
);
