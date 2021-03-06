<?php

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

/**
 * This is the personal-data-action (default), it will display a personal data form
 *
 * @author Tim van Wolfswinkel <tim@webleads.nl>
 */
class FrontendCatalogPersonalData extends FrontendBaseBlock
{
	/**
	 * The url to checkout page
	 *
	 * @var	array
	 */
	private $checkoutUrl;

	/**
	 * The order id in cookie
	 *
	 * @var	int
	 */
	private $cookieOrderId;

	
	/**
	 * Execute the action
	 */
	public function execute()
	{
		parent::execute();
		
		$this->loadTemplate();
		$this->getData();
		
		$this->loadForm();
		$this->validateForm();
		
		$this->parse();
	}

	/**
	 * Load the data, don't forget to validate the incoming data
	 */
	private function getData()
	{
		// requested page
		$requestedPage = $this->URL->getParameter('page', 'int', 1);
		
		// get order
		$this->cookieOrderId = CommonCookie::get('order_id');
		
		// set checkout url
        $this->checkoutUrl = FrontendNavigation::getURLForBlock('catalog', 'checkout');
	}

	/**
	 * Load the form
	 */
	private function loadForm()
	{
		// create form
		$this->frm = new FrontendForm('personalDataForm');
		
		// init vars
		$email = (CommonCookie::exists('email')) ? CommonCookie::get('email') : null;
		$fname = (CommonCookie::exists('fname')) ? CommonCookie::get('fname') : null;
		$lname = (CommonCookie::exists('lname')) ? CommonCookie::get('lname') : null;
		$address = (CommonCookie::exists('address')) ? CommonCookie::get('address') : null;
		$hnumber = (CommonCookie::exists('hnumber')) ? CommonCookie::get('hnumber') : null;
		$postal = (CommonCookie::exists('postal')) ? CommonCookie::get('postal') : null;
		$hometown = (CommonCookie::exists('hometown')) ? CommonCookie::get('hometown') : null;
		
		// create elements
		$this->frm->addText('email', $email)->setAttributes(array('required' => null, 'type' => 'email'));
		$this->frm->addText('fname', $fname, null)->setAttributes(array('required' => null));
		$this->frm->addText('lname', $lname, null)->setAttributes(array('required' => null));
		$this->frm->addText('address', $address, null)->setAttributes(array('required' => null));
		$this->frm->addText('hnumber', $hnumber, null)->setAttributes(array('required' => null));
		$this->frm->addText('postal', $postal, null)->setAttributes(array('required' => null));
		$this->frm->addText('hometown', $hometown, null)->setAttributes(array('required' => null));
		
		$this->frm->addTextarea('message');
	}

	/**
	 * Validate the form
	 */ 
	private function validateForm()
	{
		// is the form submitted
		if($this->frm->isSubmitted())
		{
			// cleanup the submitted fields, ignore fields that were added by hackers
			$this->frm->cleanupFields();
			
			// validate required fields
			$this->frm->getField('email')->isEmail(FL::err('EmailIsRequired'));
			$this->frm->getField('fname')->isFilled(FL::err('MessageIsRequired'));
			$this->frm->getField('lname')->isFilled(FL::err('MessageIsRequired'));
			$this->frm->getField('address')->isFilled(FL::err('MessageIsRequired'));
			$this->frm->getField('hnumber')->isFilled(FL::err('MessageIsRequired'));
			$this->frm->getField('postal')->isFilled(FL::err('MessageIsRequired'));
			$this->frm->getField('hometown')->isFilled(FL::err('MessageIsRequired'));
			
			// correct?
			if($this->frm->isCorrect())
			{
				// build array
				$order['email'] = $this->frm->getField('email')->getValue();
				$order['fname'] = $this->frm->getField('fname')->getValue();
				$order['lname'] = $this->frm->getField('lname')->getValue();
				$order['address'] = $this->frm->getField('address')->getValue();
				$order['hnumber'] = $this->frm->getField('hnumber')->getValue();
				$order['postal'] = $this->frm->getField('postal')->getValue();
				$order['hometown'] = $this->frm->getField('hometown')->getValue();
				$order['status'] = 'moderation';
				
				// insert values in database
				FrontendCatalogModel::updateOrder($order, $this->cookieOrderId);
								
				// delete cookie
				$argument = 'order_id';
				unset($_COOKIE[(string) $argument]);
				setcookie((string) $argument, null, 1, '/');
								
				// set cookies person --> optional
				CommonCookie::set('email', $order['email']);
				CommonCookie::set('fname', $order['fname']);
				CommonCookie::set('lname', $order['lname']);
				CommonCookie::set('address', $order['address']);
				CommonCookie::set('hnumber', $order['hnumber']);
				CommonCookie::set('postal', $order['postal']);
				CommonCookie::set('hometown', $order['hometown']);
				CommonCookie::set('status', $order['status']);
				
				// trigger event
				FrontendModel::triggerEvent('catalog', 'after_add_order', array('order' => $order));
				
				$url = FrontendNavigation::getURLForBlock('catalog', 'order_received');
				$this->redirect($url);
			}
		}
	}
	
	/**
	 * Parse the page
	 */
	protected function parse()
	{
		// add css
		$this->header->addCSS('/frontend/modules/' . $this->getModule() . '/layout/css/catalog.css');
		
		// url to checkout page
		$this->tpl->assign('checkoutUrl', $this->checkoutUrl);
		
		// parse the form
		$this->frm->parse($this->tpl);
	}
}
