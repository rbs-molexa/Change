<?php
namespace Change\Http\Rest\Actions;

use Change\Http\Rest\Result\BlockLink;
use Change\Http\Rest\Result\CollectionResult;
use Change\Http\Rest\Result\Link;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\Actions\GetBlockCollection
 */
class GetBlockCollection
{
	/**
	 * Use Event Params: vendor, shortModuleName
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$vendor = $event->getParam('vendor');
		$shortModuleName = $event->getParam('shortModuleName');
		if ($vendor && $shortModuleName)
		{
			$this->generateResult($event, $vendor, $shortModuleName);
		}
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param string $vendor
	 * @param string $shortModuleName
	 */
	protected function generateResult($event, $vendor, $shortModuleName)
	{
		$bm = $event->getApplicationServices()->getBlockManager();
		$shortBlocksName = array();
		foreach ($bm->getBlockNames() as $name)
		{
			$a = explode('_', $name);
			if ($a[0] === $vendor && $a[1] === $shortModuleName)
			{
				$shortBlocksName[] = $name;
			}
		}

		if (!count($shortBlocksName))
		{
			return;
		}

		$urlManager = $event->getUrlManager();
		$result = new CollectionResult();
		$result->setOffset(0);
		$basePath = $event->getRequest()->getPath();
		$selfLink = new Link($urlManager, $basePath);
		$result->addLink($selfLink);
		$result->setCount(count($shortBlocksName));
		$result->setSort(null);

		foreach ($shortBlocksName as $name)
		{
			$info = $bm->getBlockInformation($name);
			$l = new BlockLink($urlManager, $info);
			$result->addResource($l);
		}

		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		$event->setResult($result);
	}
}
