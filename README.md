<img src="resources/img/plugin-logo.png" width="130" height="90" alt="TaxCloud logo">

# TaxCloud plugin for Craft CMS 3.x

TaxCloud integration for Craft Commerce

## Requirements

This plugin requires Craft Commerce (Pro edition) 3.1 or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require surprisehighway/craft-taxcloud

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for TaxCloud.


## Configuring TaxCloud

### Step 1: Configure the plugin

Add your API connection settings and plugin defaults:

1. Define `TAXCLOUD_API_ID` and `TAXCLOUD_API_KEY` environmental variables in your `.env` file.

```
# Set your TaxCloud API ID
TAXCLOUD_API_ID="xxxxxxxx"

# Set your TaxCloud API Key
TAXCLOUD_API_KEY="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"

```

2. Create a `config/tax-cloud.php` file using the example found at `vendor/surprisehighway/taxcloud/config.php`

```
<?php

return [
	'apiId' => getenv('TAXCLOUD_API_ID'),
	'apiKey' => getenv('TAXCLOUD_API_KEY'),
	'verifyAddress' => false,
	'defaultShippingTic' => '11010',
];
```

> **TIP** You can check your connection settings by navigating to [your_cp_url]/actions/tax-cloud/categories/ping

## Step 2: Sync your tax categories

To sync your tax categories with [TaxCloud's TICs](https://taxcloud.com/TIC), go to Commerce → Tax → Tax Categories in the control panel and click the "Sync TaxCloud Categories" button.

Once the sync is complete **assign the categories you will use to your Product Types** to make them available for selection in your product entries. Most likely you will want to set a default category as well, such as "Uncategorized - 00000".

![Screenshot](resources/img/tax-categories.png)

> **Warning** Don't change the tax category handles, they are used to keep the categories in sync so you could potentially cause duplicates. Note that the handle is the actual [TIC code](https://taxcloud.com/TIC) value that is sent to TaxCloud. You can change the name and description. 

## Step 3: Check your store location

Got to Commerce → Store Settings → Store Location and make sure the address is set correctly. This will be used as the shipping Origin by TaxCloud and is required.


## Using TaxCloud

Once everything is set up tax adjustments will be added to new orders automatically based on the line items's Tax Category in the product entry.

You can see the full TaxCloud API response in the order line item's `sourceSnapshot`.

> **NOTE** TaxCloud sets the sales tax per line item, so you may want to disable the display of that in your cart templates. The total tax is calculated automatically by Commerce.

## TaxCloud Roadmap

Some things to do, and ideas for potential features:

- [x] Release it
- [x] Replace the built-in tax engine in Craft Commerce
- [x] Manage product TICs suing tax categories synced from TaxCloud
- [x] Live rates from TaxCloud
- [x] Authorize and Capture orders in TaxCloud for reporting purposes
- [ ] Handle refunds

Brought to you by [Surprise Highway](https://surprisehighway.com)

## Credits

This plugin is largely based on the first-party TaxJar plugin in code and the approach of using tax categories rather than a field for TICs.
