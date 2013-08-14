<?php
/**
 * Represents a shipping or billing address which are both attached to {@link Order}.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage order
 */
class Address extends DataObject {

	/**
	 * DB fields for an address
	 * 
	 * @var Array
	 */
	private static $db = array(
		'Default' => 'Boolean',
		'FirstName' => 'Varchar',
		'Surname' => 'Varchar',
		'Company' => 'Varchar',
		'Address' => 'Varchar(255)',
		'AddressLine2' => 'Varchar(255)',
		'City' => 'Varchar(100)',
		'PostalCode' => 'Varchar(30)',
		'State' => 'Varchar(100)',

		//De-normalise these values in case region or country is deleted
		'CountryName' => 'Varchar',
		'CountryCode' => 'Varchar(2)', //ISO 3166 
		'RegionName' => 'Varchar',
		'RegionCode' => 'Varchar(2)'
	);

	/**
	 * Relations for address
	 * 
	 * @var Array
	 */
	private static $has_one = array(
		'Member' => 'Customer',  
		'Country' => 'Country',
		'Region' => 'Region'
	);

	public function onAfterWrite() {
		parent::onAfterWrite();

		//Make sure there is only one default address
		if ($this->Default == true) {

			$addrs = Address::get()
				->where("\"ClassName\" = '" . get_class($this) . "' AND \"MemberID\" = '{$this->MemberID}' AND \"Default\" = 1 AND \"ID\" != {$this->ID}");

			if ($addrs && $addrs->exists()) foreach ($addrs as $addr) {
				$addr->Default = 0;
				$addr->write();
			}
		}
	}
	
}

class Address_Shipping extends Address {

	public function onBeforeWrite() {
		parent::onBeforeWrite();

		$code = $this->CountryCode;
		$country = Country_Shipping::get()
			->where("\"Code\" = '$code'")
			->first();

		if ($country && $country->exists()) {
			$this->CountryName = $country->Title;
			$this->CountryID = $country->ID;
		}

		$code = $this->RegionCode;
		$region = Region_Shipping::get()
			->where("\"Code\" = '$code'")
			->first();

		if ($region && $region->exists()) {
			$this->RegionName = $region->Title;
			$this->RegionID = $region->ID;
		}
	}

	/**
	 * Return data in an Array with keys formatted to match the field names
	 * on the checkout form so that it can be loaded into an order form.
	 * 
	 * @see Form::loadDataFrom()
	 * @return Array Data for loading into the form
	 */
	public function getCheckoutFormData() {
		$formattedData = array();
		
		$formattedData['ShippingFirstName'] = $this->FirstName;
		$formattedData['ShippingSurname'] = $this->Surname;
		$formattedData['ShippingCompany'] = $this->Company;
		$formattedData['ShippingAddress'] = $this->Address;
		$formattedData['ShippingAddressLine2'] = $this->AddressLine2;
		$formattedData['ShippingCity'] = $this->City;
		$formattedData['ShippingPostalCode'] = $this->PostalCode;
		$formattedData['ShippingState'] = $this->State;
		$formattedData['ShippingCountryCode'] = $this->CountryCode;
		$formattedData['ShippingRegionCode'] = $this->RegionCode;
		
		return $formattedData;
	}
}

class Address_Billing extends Address {

	public function onBeforeWrite() {
		parent::onBeforeWrite();

		$code = $this->CountryCode;
		$country = Country_Billing::get()
			->where("\"Code\" = '$code'")
			->first();

		if ($country && $country->exists()) {
			$this->CountryName = $country->Title;
			$this->CountryID = $country->ID;
		}
	}

	/**
	 * Return data in an Array with keys formatted to match the field names
	 * on the checkout form so that it can be loaded into an order form.
	 * 
	 * @see Form::loadDataFrom()
	 * @return Array Data for loading into the form
	 */
	public function getCheckoutFormData() {
		$formattedData = array();
		
		$formattedData['BillingFirstName'] = $this->FirstName;
		$formattedData['BillingSurname'] = $this->Surname;
		$formattedData['BillingCompany'] = $this->Company;
		$formattedData['BillingAddress'] = $this->Address;
		$formattedData['BillingAddressLine2'] = $this->AddressLine2;
		$formattedData['BillingCity'] = $this->City;
		$formattedData['BillingPostalCode'] = $this->PostalCode;
		$formattedData['BillingState'] = $this->State;
		$formattedData['BillingCountryCode'] = $this->CountryCode;
		
		return $formattedData;
	}
}
