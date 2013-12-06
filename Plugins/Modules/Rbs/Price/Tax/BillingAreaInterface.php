<?php
namespace Rbs\Price\Tax;

/**
* @name \Rbs\Commerce\Interfaces\BillingArea
*/
interface BillingAreaInterface
{
	/**
	 * @return string
	 */
	public function getCode();

	/**
	 * @return string
	 */
	public function getCurrencyCode();

	/**
	 * @return \Rbs\Price\Tax\TaxInterface []
	 */
	public function getTaxes();
}