<?php

class Addresses_Order extends DataExtension {

	public static $db = array(
		//Address fields
		'ShippingFirstName' => 'Varchar',
		'ShippingSurname' => 'Varchar',
		'ShippingCompany' => 'Varchar',
		'ShippingAddress' => 'Varchar(255)',
		'ShippingAddressLine2' => 'Varchar(255)',
		'ShippingCity' => 'Varchar(100)',
		'ShippingPostalCode' => 'Varchar(30)',
		'ShippingState' => 'Varchar(100)',
		'ShippingCountryName' => 'Varchar',
		'ShippingCountryCode' => 'Varchar(2)', //ISO 3166 
		'ShippingRegionName' => 'Varchar',
		'ShippingRegionCode' => 'Varchar(2)',

		'BillingFirstName' => 'Varchar',
		'BillingSurname' => 'Varchar',
		'BillingCompany' => 'Varchar',
		'BillingAddress' => 'Varchar(255)',
		'BillingAddressLine2' => 'Varchar(255)',
		'BillingCity' => 'Varchar(100)',
		'BillingPostalCode' => 'Varchar(30)',
		'BillingState' => 'Varchar(100)',
		'BillingCountryName' => 'Varchar',
		'BillingCountryCode' => 'Varchar(2)', //ISO 3166 
		'BillingRegionName' => 'Varchar',
		'BillingRegionCode' => 'Varchar(2)'
	);

	public function onBeforeWrite() {

		//Update address names
		$country = Country_Shipping::get()->where("\"Code\" = '{$this->owner->ShippingCountryCode}'")->first();
		if ($country && $country->exists()) $this->owner->ShippingCountryName = $country->Title;

		$region = Region_Shipping::get()->where("\"Code\" = '{$this->owner->ShippingRegionCode}'")->first();
		if ($region && $region->exists()) $this->owner->ShippingRegionName = $region->Title;

		$country = Country_Billing::get()->where("\"Code\" = '{$this->owner->BillingCountryCode}'")->first();
		if ($country && $country->exists()) $this->owner->BillingCountryName = $country->Title;
	}

	public function onBeforePayment() {

		//Save the addresses to the Customer
		$customer = $this->owner->Member();
		if ($customer && $customer->exists()) {
			$customer->createAddresses($this->owner);
		}
	}
}

class Addresses_Customer extends DataExtension {

	static $has_many = array(
		'ShippingAddresses' => 'Address_Shipping',
		'BillingAddresses' => 'Address_Billing'
	);

	public function createAddresses($order) {
		//Find identical addresses
		//If none exist then create a new address and set it as default
		//Default is not used when comparing

		$data = $order->toMap();
		
		// Set Firstname/Surname Fields on Member table
		if(!$this->owner->FirstName) {
			$this->owner->FirstName = $data['ShippingFirstName'];
			$this->owner->write();
		}
		if(!$this->owner->Surname) {
			$this->owner->Surname = $data['ShippingSurname'];
			$this->owner->write();
		}
		
		$shippingAddress = Address_Shipping::create(array(
			'MemberID' => $this->owner->ID,
			'FirstName' => $data['ShippingFirstName'],
			'Surname' => $data['ShippingSurname'],
			'Company' => $data['ShippingCompany'],
			'Address' => $data['ShippingAddress'],
			'AddressLine2' => $data['ShippingAddressLine2'],
			'City' => $data['ShippingCity'],
			'PostalCode' => $data['ShippingPostalCode'],
			'State' => $data['ShippingState'],
			'CountryName' => $data['ShippingCountryName'],
			'CountryCode' => $data['ShippingCountryCode'],
			'RegionName' => (isset($data['ShippingRegionName'])) ? $data['ShippingRegionName'] : null,
			'RegionCode' => (isset($data['ShippingRegionCode'])) ? $data['ShippingRegionCode'] : null,
		));

		$billingAddress = Address_Billing::create(array(
			'MemberID' => $this->owner->ID,
			'FirstName' => $data['BillingFirstName'],
			'Surname' => $data['BillingSurname'],
			'Company' => $data['BillingCompany'],
			'Address' => $data['BillingAddress'],
			'AddressLine2' => $data['BillingAddressLine2'],
			'City' => $data['BillingCity'],
			'PostalCode' => $data['BillingPostalCode'],
			'State' => $data['BillingState'],
			'CountryName' => $data['BillingCountryName'],
			'CountryCode' => $data['BillingCountryCode'],
			'RegionName' => (isset($data['BillingRegionName'])) ? $data['ShippingRegionName'] : null,
			'RegionCode' => (isset($data['BillingRegionCode'])) ? $data['ShippingRegionCode'] : null,
		));

		//Look for identical existing addresses

		//TODO when a match is made then make that matched address the default now

		$match = false;
		foreach ($this->owner->ShippingAddresses() as $address) {

			$existing = $address->toMap();
			$new = $shippingAddress->toMap();
			$result = array_intersect_assoc($existing, $new);

			//If no difference, then match is found
			$diff = array_diff_assoc($new, $result);
			$match = empty($diff);
		}

		if (!$match) {
			$shippingAddress->Default = true;
			$shippingAddress->write();
		}

		$match = false;
		foreach ($this->owner->BillingAddresses() as $address) {

			$existing = $address->toMap();
			$new = $billingAddress->toMap();
			$result = array_intersect_assoc($existing, $new);

			$diff = array_diff_assoc($new, $result);
			$match = empty($diff);
		}

		if (!$match) {
			$billingAddress->Default = true;
			$billingAddress->write();
		}
	}

	/**
	 * Retrieve the last used billing address for this Member from their previous saved addresses.
	 * TODO make this more efficient
	 * 
	 * @return Address The last billing address
	 */
	public function BillingAddress() {

		$addrs = $this->owner->BillingAddresses();
		if ($addrs && $addrs->exists()) {
			return $addrs
				->where("\"Default\" = 1")
				->first();
		}
		return null;
	}
	
	/**
	 * Retrieve the last used shipping address for this Member from their previous saved addresses.
	 * TODO make this more efficient
	 * 
	 * @return Address The last shipping address
	 */
	public function ShippingAddress() {

		$addrs = $this->owner->ShippingAddresses();
		if ($addrs && $addrs->exists()) {
			return $addrs
				->where("\"Default\" = 1")
				->first();
		}
		return null;
	}
}

class Addresses_OrderForm extends Extension {

	public function updateFields($fields) {

		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript('swipestripe-addresses/javascript/Addresses_OrderForm.js');

		$shippingAddressFields = CompositeField::create(
			HeaderField::create(_t('CheckoutPage.SHIPPING_ADDRESS',"Shipping Address"), 3),
			TextField::create('ShippingFirstName', _t('CheckoutPage.FIRSTNAME',"First Name"))
				->addExtraClass('shipping-firstname')
				->setCustomValidationMessage(_t('CheckoutPage.PLEASE_ENTER_FIRSTNAME',"Please enter a first name.")),
			TextField::create('ShippingSurname', _t('CheckoutPage.SURNAME',"Surname"))
				->setCustomValidationMessage(_t('CheckoutPage.PLEASE_ENTER_SURNAME',"Please enter a surname.")),
			TextField::create('ShippingCompany', _t('CheckoutPage.COMPANY',"Company")),
			TextField::create('ShippingAddress', _t('CheckoutPage.ADDRESS',"Address"))
				->setCustomValidationMessage(_t('CheckoutPage.PLEASE_ENTER_ADDRESS',"Please enter an address."))
				->addExtraClass('address-break'),
			TextField::create('ShippingAddressLine2', '&nbsp;'),
			TextField::create('ShippingCity', _t('CheckoutPage.CITY',"City"))
				->setCustomValidationMessage(_t('CheckoutPage.PLEASE_ENTER_CITY',"Please enter a city.")),
			TextField::create('ShippingPostalCode', _t('CheckoutPage.POSTAL_CODE',"Zip / Postal Code")),
			TextField::create('ShippingState', _t('CheckoutPage.STATE',"State / Province"))
				->addExtraClass('address-break'),
			DropdownField::create('ShippingCountryCode', 
					_t('CheckoutPage.COUNTRY',"Country"), 
					Country_Shipping::get()->map('Code', 'Title')->toArray()
				)
				->setCustomValidationMessage(_t('CheckoutPage.PLEASE_ENTER_COUNTRY',"Please enter a country."))
				->addExtraClass('country-code')
		)->setID('ShippingAddress')->setName('ShippingAddress');

		$billingAddressFields = CompositeField::create(
			HeaderField::create(_t('CheckoutPage.BILLINGADDRESS',"Billing Address"), 3),
			$checkbox = CheckboxField::create('BillToShippingAddress', _t('CheckoutPage.SAME_ADDRESS',"same as shipping address?"))
				->addExtraClass('shipping-same-address'),
			TextField::create('BillingFirstName', _t('CheckoutPage.FIRSTNAME',"First Name"))
				->setCustomValidationMessage(_t('CheckoutPage.PLEASEENTERYOURFIRSTNAME',"Please enter your first name."))
				->addExtraClass('address-break'),
			TextField::create('BillingSurname', _t('CheckoutPage.SURNAME',"Surname"))
				->setCustomValidationMessage(_t('CheckoutPage.PLEASEENTERYOURSURNAME',"Please enter your surname.")),
			TextField::create('BillingCompany', _t('CheckoutPage.COMPANY',"Company")),
			TextField::create('BillingAddress', _t('CheckoutPage.ADDRESS',"Address"))
				->setCustomValidationMessage(_t('CheckoutPage.PLEASEENTERYOURADDRESS',"Please enter your address."))
				->addExtraClass('address-break'),
			TextField::create('BillingAddressLine2', '&nbsp;'),
			TextField::create('BillingCity', _t('CheckoutPage.CITY',"City"))
				->setCustomValidationMessage(_t('CheckoutPage.PLEASEENTERYOURCITY',"Please enter your city")),
			TextField::create('BillingPostalCode', _t('CheckoutPage.POSTALCODE',"Zip / Postal Code")),
			TextField::create('BillingState', _t('CheckoutPage.STATE',"State / Province"))
				->addExtraClass('address-break'),
			DropdownField::create('BillingCountryCode', 
					_t('CheckoutPage.COUNTRY',"Country"), 
					Country_Billing::get()->map('Code', 'Title')->toArray()
				)->setCustomValidationMessage(_t('CheckoutPage.PLEASEENTERYOURCOUNTRY',"Please enter your country."))
		)->setID('BillingAddress')->setName('BillingAddress');

		$fields->push($shippingAddressFields);
		$fields->push($billingAddressFields);
	}

	public function updateValidator($validator) {

		$validator->appendRequiredFields(RequiredFields::create(
			'ShippingFirstName',
			'ShippingSurname',
			'ShippingAddress',
			'ShippingCity',
			'ShippingCountryCode',
			'BillingFirstName',
			'BillingSurname',
			'BillingAddress',
			'BillingCity',
			'BillingCountryCode'
		));
	}

	public function updatePopulateFields(&$data) {

		$member = Customer::currentUser() ? Customer::currentUser() : singleton('Customer');

		$shippingAddress = $member->ShippingAddress();
		$shippingAddressData = ($shippingAddress && $shippingAddress->exists()) 
			? $shippingAddress->getCheckoutFormData()
			: array();
		unset($shippingAddressData['ShippingRegionCode']); //Not available billing address option

		$billingAddress = $member->BillingAddress();
		$billingAddressData = ($billingAddress && $billingAddress->exists()) 
			? $billingAddress->getCheckoutFormData()
			: array();

		//If billing address is a subset of shipping address, consider them equal
		$intersect = array_intersect(array_values($shippingAddressData), array_values($billingAddressData));
		if (array_values($intersect) == array_values($billingAddressData)) $billingAddressData['BillToShippingAddress'] = true;

		$data = array_merge(
			$data, 
			$shippingAddressData,
			$billingAddressData
		);
	}

	public function getShippingAddressFields() {
		return $this->owner->Fields()->fieldByName('ShippingAddress');
	}

	public function getBillingAddressFields() {
		return $this->owner->Fields()->fieldByName('BillingAddress');
	}
}

class Addresses_Extension extends DataExtension {

	public static $has_many = array(
		'ShippingCountries' => 'Country_Shipping',
		'BillingCountries' => 'Country_Billing',
		'ShippingRegions' => 'Region_Shipping',
		'BillingRegions' => 'Region_Billing'
	);
}

class Addresses_CountriesAdmin extends ShopAdmin {

	static $url_rule = 'ShopConfig/Countries';
	static $url_priority = 70;
	static $menu_title = 'Shop Countries';

	public static $url_handlers = array(
		'ShopConfig/Countries/CountriesForm' => 'CountriesForm',
		'ShopConfig/Countries' => 'Countries'
	);

	public function init() {
		parent::init();
		if (!in_array(get_class($this), self::$hidden_sections)) {
			$this->modelClass = 'ShopConfig';
		}
	}

	public function Breadcrumbs($unlinked = false) {

		$request = $this->getRequest();
		$items = parent::Breadcrumbs($unlinked);

		if ($items->count() > 1) $items->remove($items->pop());

		$items->push(new ArrayData(array(
			'Title' => 'Countries',
			'Link' => $this->Link(Controller::join_links($this->sanitiseClassName($this->modelClass), 'Countries'))
		)));

		return $items;
	}

	public function SettingsForm($request = null) {
		return $this->CountriesForm();
	}

	public function Countries($request) {

		if ($request->isAjax()) {
			$controller = $this;
			$responseNegotiator = new PjaxResponseNegotiator(
				array(
					'CurrentForm' => function() use(&$controller) {
						return $controller->CountriesForm()->forTemplate();
					},
					'Content' => function() use(&$controller) {
						return $controller->renderWith('ShopAdminSettings_Content');
					},
					'Breadcrumbs' => function() use (&$controller) {
						return $controller->renderWith('CMSBreadcrumbs');
					},
					'default' => function() use(&$controller) {
						return $controller->renderWith($controller->getViewer('show'));
					}
				),
				$this->response
			); 
			return $responseNegotiator->respond($this->getRequest());
		}

		return $this->renderWith('ShopAdminSettings');
	}

	public function CountriesForm() {

		$shopConfig = ShopConfig::get()->First();

		$fields = new FieldList(
			$rootTab = new TabSet("Root",
				$tabMain = new Tab('Shipping',
					new HiddenField('ShopConfigSection', null, 'Countries'),
					new GridField(
						'ShippingCountries',
						'Shipping Countries',
						$shopConfig->ShippingCountries(),
						GridFieldConfig_RecordEditor::create()
							->removeComponentsByType('GridFieldFilterHeader')
							->removeComponentsByType('GridFieldAddExistingAutocompleter')
					)
				),
				new Tab('Billing',
					new GridField(
						'BillingCountries',
						'Billing Countries',
						$shopConfig->BillingCountries(),
						GridFieldConfig_RecordEditor::create()
							->removeComponentsByType('GridFieldFilterHeader')
							->removeComponentsByType('GridFieldAddExistingAutocompleter')
					)
				)
			)
		);

		$actions = new FieldList();

		$form = new Form(
			$this,
			'EditForm',
			$fields,
			$actions
		);

		$form->setTemplate('ShopAdminSettings_EditForm');
		$form->setAttribute('data-pjax-fragment', 'CurrentForm');
		$form->addExtraClass('cms-content cms-edit-form center ss-tabset');
		if($form->Fields()->hasTabset()) $form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');
		$form->setFormAction(Controller::join_links($this->Link($this->sanitiseClassName($this->modelClass)), 'Countries/CountriesForm'));

		$form->loadDataFrom($shopConfig);

		return $form;
	}

	public function getSnippet() {

		if (!$member = Member::currentUser()) return false;
		if (!Permission::check('CMS_ACCESS_' . get_class($this), 'any', $member)) return false;

		return $this->customise(array(
			'Title' => 'Countries and Regions',
			'Help' => 'Shipping and billing countries and regions.',
			'Link' => Controller::join_links($this->Link('ShopConfig'), 'Countries'),
			'LinkTitle' => 'Edit Countries and Regions'
		))->renderWith('ShopAdmin_Snippet');
	}

}
