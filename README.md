# Mage2 Module Channel3 Analytics

    ``channel3/module-analytics``

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities
Channel3 inventory sync and tracking for Magento / Adobe Commerce storefronts

## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/Channel3`
 - Enable the module by running `php bin/magento module:enable Channel3_Analytics`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require channel3/module-analytics`
 - enable the module by running `php bin/magento module:enable Channel3_Analytics`
 - apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration




## Specifications

 - Block
	- Tracking > tracking.phtml

 - Controller
	- adminhtml > settings/index/index

 - Observer
	- checkout_onepage_controller_success_action > Channel3\Analytics\Observer\Frontend\Checkout\OnepageControllerSuccessAction


## Attributes



