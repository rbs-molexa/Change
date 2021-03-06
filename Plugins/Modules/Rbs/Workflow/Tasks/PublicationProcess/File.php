<?php
namespace Rbs\Workflow\Tasks\PublicationProcess;

use Change\Documents\Interfaces\Publishable;
use Change\Workflow\Interfaces\WorkItem;
use Change\Events\Event;

/**
* @name \Rbs\Workflow\Tasks\PublicationProcess\File
*/
class File
{
	/**
	 * @param Event $event
	 * @throws \Exception
	 */
	public function execute(Event $event)
	{
		/* @var $workItem WorkItem */
		$workItem = $event->getParam('workItem');
		$document = $workItem->getWorkflowInstance()->getDocument();
		if ($document instanceof Publishable)
		{
			$publicationStatus = $document->getDocumentModel()->getPropertyValue($document, 'publicationStatus');
			if ($publicationStatus === Publishable::STATUS_PUBLISHABLE)
			{
				$applicationServices = $event->getApplicationServices();
				$transactionManager = $applicationServices->getTransactionManager();
				try
				{
					$transactionManager->begin();

					$document->updatePublicationStatus(Publishable::STATUS_FILED);
					$transactionManager->commit();
				}
				catch (\Exception $e)
				{
					throw $transactionManager->rollBack($e);
				}
			}
		}
	}
}