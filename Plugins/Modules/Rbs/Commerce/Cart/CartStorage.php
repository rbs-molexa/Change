<?php
namespace Rbs\Commerce\Cart;

use Change\Documents\AbstractDocument;
use Change\Documents\DocumentWeakReference;

/**
 * @name \Rbs\Commerce\Cart\CartStorage
 */
class CartStorage
{
	/**
	 * @var \Change\Transaction\TransactionManager
	 */
	protected $transactionManager;

	/**
	 * @var \Change\Db\DbProvider
	 */
	protected $dbProvider;

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var \Rbs\Commerce\Std\Context
	 */
	protected $context;

	protected $cachedCarts = array();

	/**
	 * @param \Change\Transaction\TransactionManager $transactionManager
	 * @return $this
	 */
	public function setTransactionManager($transactionManager)
	{
		$this->transactionManager = $transactionManager;
		return $this;
	}

	/**
	 * @return \Change\Transaction\TransactionManager
	 */
	protected function getTransactionManager()
	{
		return $this->transactionManager;
	}

	/**
	 * @param \Change\Db\DbProvider $dbProvider
	 * @return $this
	 */
	public function setDbProvider($dbProvider)
	{
		$this->dbProvider = $dbProvider;
		return $this;
	}

	/**
	 * @return \Change\Db\DbProvider
	 */
	protected function getDbProvider()
	{
		return $this->dbProvider;
	}

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return $this
	 */
	public function setDocumentManager($documentManager)
	{
		$this->documentManager = $documentManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	public function getDocumentManager()
	{
		return $this->documentManager;
	}

	/**
	 * @param \Rbs\Commerce\Std\Context $context
	 * @return $this
	 */
	public function setContext($context)
	{
		$this->context = $context;
		return $this;
	}

	/**
	 * @return \Rbs\Commerce\Std\Context
	 */
	protected function getContext()
	{
		return $this->context;
	}

	/**
	 * @param array $cachedCarts
	 * @return $this
	 */
	public function setCachedCarts(array $cachedCarts)
	{
		$this->cachedCarts = $cachedCarts;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getCachedCarts()
	{
		return $this->cachedCarts;
	}

	/**
	 * @param string $identifier
	 * @throws \Exception
	 * @return Cart
	 */
	public function getNewCart($identifier = null)
	{
		$tm = $this->getTransactionManager();
		$cart = null;
		try
		{
			$tm->begin();

			$qb = $this->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$date = new \DateTime();

			$qb->insert($fb->table('rbs_commerce_dat_cart'),
				$fb->column('creation_date'), $fb->column('last_update'));
			$qb->addValue($fb->dateTimeParameter('creationDate'));
			$qb->addValue($fb->dateTimeParameter('lastUpdate'));

			$iq = $qb->insertQuery();
			$iq->bindParameter('creationDate', $date);
			$iq->bindParameter('lastUpdate', $date);
			$iq->execute();

			$storageId = $iq->getDbProvider()->getLastInsertId('rbs_commerce_dat_cart');
			if ($identifier === null)
			{
				$identifier = sha1($storageId . '-' . $date->getTimestamp());
			}

			$cart = new Cart($identifier);
			$cart->lastUpdate($date);
			$cart->getContext()->set('storageId', $storageId);

			$qb = $this->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->update($fb->table('rbs_commerce_dat_cart'));
			$qb->assign($fb->column('identifier'), $fb->parameter('identifier'));
			$qb->assign($fb->column('cart_data'), $fb->lobParameter('cartData'));
			$qb->where($fb->eq($fb->column('id'), $fb->integerParameter('id')));
			$uq = $qb->updateQuery();

			$uq->bindParameter('identifier', $cart->getIdentifier());
			$uq->bindParameter('cartData', serialize($cart));
			$uq->bindParameter('id', $storageId);
			$uq->execute();

			$this->cachedCarts = array();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $cart;
	}

	/**
	 * @param string $identifier
	 * @return Cart|null
	 */
	public function loadCart($identifier)
	{
		if (!array_key_exists($identifier, $this->cachedCarts))
		{
			$qb = $this->getDbProvider()->getNewQueryBuilder('loadCart');
			if (!$qb->isCached())
			{
				$fb = $qb->getFragmentBuilder();
				$qb->select($fb->column('cart_data'), $fb->column('owner_id'), $fb->column('store_id')
					, $fb->column('user_id'), $fb->column('transaction_id'), $fb->column('ordered'),
					$fb->column('locked'), $fb->column('last_update'));
				$qb->from($fb->table('rbs_commerce_dat_cart'));
				$qb->where($fb->eq($fb->column('identifier'), $fb->parameter('identifier')));
			}
			$sq = $qb->query();
			$sq->bindParameter('identifier', $identifier);

			$cartInfo = $sq->getFirstResult($sq->getRowsConverter()
				->addLobCol('cart_data')
				->addIntCol('owner_id', 'store_id', 'user_id', 'transaction_id')
				->addBoolCol('locked', 'ordered')->addDtCol('last_update'));

			$this->cachedCarts[$identifier] = $cartInfo;
		}
		else
		{
			$cartInfo = $this->cachedCarts[$identifier];
		}

		if ($cartInfo)
		{
			$cart = unserialize($cartInfo['cart_data']);
			if ($cart instanceof Cart)
			{
				$cart->setIdentifier($identifier)
					->setWebStoreId($cartInfo['store_id'])
					->setUserId($cartInfo['user_id'])
					->setLocked($cartInfo['locked'])
					->setOwnerId($cartInfo['owner_id'])
					->setTransactionId($cartInfo['transaction_id'])
					->setOrdered($cartInfo['ordered']);
				$cart->lastUpdate($cartInfo['last_update']);
				return $cart;
			}
		}
		return null;
	}

	/**
	 * @param Cart $cart
	 * @throws \Exception
	 */
	public function saveCart(Cart $cart)
	{
		$cart->lastUpdate(new \DateTime());
		$tm = $this->getTransactionManager();
		try
		{
			$tm->begin();
			$qb = $this->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();

			$qb->update($fb->table('rbs_commerce_dat_cart'));
			$qb->assign($fb->column('last_update'), $fb->dateTimeParameter('lastUpdate'));
			$qb->assign($fb->column('cart_data'), $fb->lobParameter('cartData'));
			$qb->assign($fb->column('store_id'), $fb->integerParameter('webStoreId'));
			$qb->assign($fb->column('user_id'), $fb->integerParameter('userId'));
			$qb->assign($fb->column('owner_id'), $fb->integerParameter('ownerId'));
			$qb->assign($fb->column('transaction_id'), $fb->integerParameter('transactionId'));
			$qb->assign($fb->column('ordered'), $fb->integerParameter('ordered'));
			$qb->assign($fb->column('line_count'), $fb->integerParameter('lineCount'));
			$qb->assign($fb->column('price_value'), $fb->decimalParameter('priceValue'));
			$qb->assign($fb->column('price_value_with_tax'), $fb->decimalParameter('priceValueWithTax'));
			$qb->assign($fb->column('currency_code'), $fb->parameter('currencyCode'));
			$qb->where(
				$fb->logicAnd(
					$fb->eq($fb->column('identifier'), $fb->parameter('identifier')),
					$fb->eq($fb->column('locked'), $fb->booleanParameter('locked'))
				)
			);
			$uq = $qb->updateQuery();

			$uq->bindParameter('lastUpdate', $cart->lastUpdate());
			$uq->bindParameter('cartData', serialize($cart));
			$uq->bindParameter('webStoreId', $cart->getWebStoreId());
			$uq->bindParameter('ownerId', $cart->getOwnerId());
			$uq->bindParameter('userId', $cart->getUserId());
			$uq->bindParameter('transactionId', $cart->getTransactionId());
			$uq->bindParameter('ordered', $cart->getOrdered());

			$uq->bindParameter('lineCount', count($cart->getLines()));
			$uq->bindParameter('priceValue', $cart->getPriceValue());
			$uq->bindParameter('priceValueWithTax', $cart->getPriceValueWithTax());
			$uq->bindParameter('currencyCode', $cart->getCurrencyCode());

			$uq->bindParameter('identifier', $cart->getIdentifier());
			$uq->bindParameter('locked', false);
			$uq->execute();

			$this->cachedCarts = array();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}

	/**
	 * @param Cart $cart
	 * @throws \Exception
	 */
	public function lockCart(Cart $cart)
	{
		$cart->lastUpdate(new \DateTime());
		$tm = $this->getTransactionManager();
		try
		{
			$tm->begin();
			$qb = $this->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->update($fb->table('rbs_commerce_dat_cart'));
			$qb->assign($fb->column('last_update'), $fb->dateTimeParameter('lastUpdate'));
			$qb->assign($fb->column('owner_id'), $fb->integerParameter('ownerId'));
			$qb->assign($fb->column('locked'), $fb->booleanParameter('locked'));
			$qb->where(
				$fb->logicAnd(
					$fb->eq($fb->column('identifier'), $fb->parameter('identifier')),
					$fb->eq($fb->column('locked'), $fb->booleanParameter('whereLocked'))
				)
			);

			$uq = $qb->updateQuery();
			$uq->bindParameter('lastUpdate', $cart->lastUpdate());
			$uq->bindParameter('ownerId', $cart->getOwnerId());
			$uq->bindParameter('locked', true);
			$uq->bindParameter('identifier', $cart->getIdentifier());
			$uq->bindParameter('whereLocked', false);
			$uq->execute();

			$tm->commit();
			$this->cachedCarts = array();
			$cart->setLocked(true);
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}

	/**
	 * @param Cart $cart
	 * @param integer $transactionId
	 * @throws \Exception
	 */
	public function affectTransactionId(Cart $cart, $transactionId)
	{
		$tm = $this->getTransactionManager();
		try
		{
			$tm->begin();

			$qb = $this->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->update($fb->table('rbs_commerce_dat_cart'));
			$qb->assign($fb->column('transaction_id'), $fb->integerParameter('transactionId'));
			$qb->where(
				$fb->logicAnd(
					$fb->eq($fb->column('identifier'), $fb->parameter('identifier')),
					$fb->eq($fb->column('locked'), $fb->booleanParameter('whereLocked'))
				)
			);

			$uq = $qb->updateQuery();
			$uq->bindParameter('transactionId', $transactionId);
			$uq->bindParameter('identifier', $cart->getIdentifier());
			$uq->bindParameter('whereLocked', true);
			$uq->execute();

			$tm->commit();
			$this->cachedCarts = array();
			$cart->setTransactionId($transactionId);
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}

	/**
	 * @param Cart $cart
	 * @param mixed $order
	 * @throws \Exception
	 */
	public function affectOrder(Cart $cart, $order)
	{
		$tm = $this->getTransactionManager();
		try
		{
			$tm->begin();
			$ordered = isset($order) && $order !== false && $order !== 0;
			$qb = $this->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->update($fb->table('rbs_commerce_dat_cart'));
			$qb->assign($fb->column('ordered'), $fb->booleanParameter('ordered'));
			$qb->where(
				$fb->logicAnd(
					$fb->eq($fb->column('identifier'), $fb->parameter('identifier')),
					$fb->eq($fb->column('locked'), $fb->booleanParameter('whereLocked'))
				)
			);

			$uq = $qb->updateQuery();
			$uq->bindParameter('ordered', $ordered);
			$uq->bindParameter('identifier', $cart->getIdentifier());
			$uq->bindParameter('whereLocked', true);
			$uq->execute();

			$tm->commit();
			$this->cachedCarts = array();
			$cart->setOrdered($ordered);
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}

	/**
	 * @param Cart $cart
	 * @throws \Exception
	 */
	public function deleteCart(Cart $cart)
	{
		$tm = $this->getTransactionManager();
		try
		{
			$tm->begin();
			$qb = $this->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->delete($fb->table('rbs_commerce_dat_cart'));
			$qb->where(
				$fb->logicAnd(
					$fb->eq($fb->column('identifier'), $fb->parameter('identifier')),
					$fb->eq($fb->column('locked'), $fb->booleanParameter('whereLocked'))
				)
			);
			$uq = $qb->deleteQuery();
			$uq->bindParameter('identifier', $cart->getIdentifier());
			$uq->bindParameter('whereLocked', false);
			$uq->execute();

			$tm->commit();
			$this->cachedCarts = array();
			$cart->getContext()->set('storageId', null);
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param \Rbs\Commerce\Cart\Cart $cartToMerge
	 * @return \Rbs\Commerce\Cart\Cart
	 */
	public function mergeCart($cart, $cartToMerge)
	{
		if ($cart->getWebStoreId() == $cartToMerge->getWebStoreId())
		{
			$cartManager = $cart->getCartManager();

			foreach ($cartToMerge->getLines() as $lineToMerge)
			{
				$currentCartLine = $cart->getLineByKey($lineToMerge->getKey());
				if ($currentCartLine === null)
				{
					$cartManager->addLine($cart, $lineToMerge->toArray());
				}
				else
				{
					$newQuantity = $currentCartLine->getQuantity() + $lineToMerge->getQuantity();
					$cartManager->updateLineQuantityByKey($cart, $currentCartLine->getKey(), $newQuantity);
				}
			}
		}
		return $cart;
	}

	/**
	 * @param string $identifier
	 * @throws \Exception
	 */
	public function purgeCart($identifier)
	{
		$tm = $this->getTransactionManager();
		try
		{
			$tm->begin();
			$qb = $this->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->delete($fb->table('rbs_commerce_dat_cart'));
			$qb->where($fb->eq($fb->column('identifier'), $fb->parameter('identifier')));
			$uq = $qb->deleteQuery();
			$uq->bindParameter('identifier', $identifier);

			$this->cachedCarts = array();

			$uq->execute();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}

	/**
	 * @param mixed $value
	 * @return array|\Change\Documents\DocumentWeakReference|mixed
	 */
	public function getSerializableValue($value)
	{
		if ($value instanceof AbstractDocument)
		{
			return new DocumentWeakReference($value);
		}
		elseif (is_array($value) || $value instanceof \Zend\Stdlib\Parameters)
		{
			foreach ($value as $k => $v)
			{
				$value[$k] = $this->getSerializableValue($v);
			}
		}
		return $value;
	}

	/**
	 * @param mixed $value
	 * @throws \RuntimeException
	 * @return array|\Change\Documents\AbstractDocument|mixed
	 */
	public function restoreSerializableValue($value)
	{
		if ($value instanceof DocumentWeakReference)
		{
			if ($this->getDocumentManager() === null)
			{
				throw new \RuntimeException('Document manager not set.', 999999);
			}
			return $value->getDocument($this->getDocumentManager());
		}
		elseif (is_array($value) || $value instanceof \Zend\Stdlib\Parameters)
		{
			foreach ($value as $k => $v)
			{
				$value[$k] = $this->restoreSerializableValue($v);
			}
		}
		return $value;
	}
}