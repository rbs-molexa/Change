<?php
namespace Rbs\Generic\Events\JobManager;

use Change\Job\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Generic\Events\JobManager\Listeners
 */
class Listeners implements ListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function attach(EventManagerInterface $events)
	{
		$callback = function (Event $event)
		{
			(new \Rbs\Workflow\Job\ExecuteDeadLineTask())->execute($event);
		};
		$events->attach('process_Rbs_Workflow_ExecuteDeadLineTask', $callback, 5);

		$callback = function (Event $event)
		{
			(new \Rbs\Workflow\Job\DocumentCleanUp())->cleanUp($event);
			(new \Rbs\Collection\Job\CleanUpListItems())->cleanUp($event);
		};
		$events->attach('process_Change_Document_CleanUp', $callback, 10);

		$callBack = function ($event)
		{
			(new \Rbs\Workflow\Job\DocumentCleanUp())->onCorrectionFiled($event);
		};
		$events->attach('process_Change_Correction_Filed', $callBack, 5);

		$callback = function (Event $event)
		{
			(new \Rbs\Workflow\Job\DocumentCleanUp())->localizedCleanUp($event);
		};
		$events->attach('process_Change_Document_LocalizedCleanUp', $callback, 5);

		$callBack = function ($event)
		{
			(new \Rbs\Timeline\Job\SendTemplateMail())->execute($event);
		};
		$events->attach('process_Rbs_Timeline_SendTemplateMail', $callBack, 5);

		$callBack = function ($event)
		{
			(new \Rbs\Notification\Job\SendMails())->execute($event);
		};
		$events->attach('process_Rbs_Notification_SendMails', $callBack, 5);

		$callBack = function ($event)
		{
			(new \Rbs\Seo\Job\GenerateSitemap())->execute($event);
		};
		$events->attach('process_Rbs_Seo_GenerateSitemap', $callBack, 5);

		$callBack = function ($event)
		{
			(new \Rbs\Seo\Job\DocumentSeoGenerator())->execute($event);
		};
		$events->attach('process_Rbs_Seo_DocumentSeoGenerator', $callBack, 5);

		$callback = function (\Change\Job\Event $event)
		{
			$genericServices = $event->getServices('genericServices');
			if ($genericServices instanceof \Rbs\Generic\GenericServices)
			{
				$genericServices->getIndexManager()->dispatchIndexationEvents($event->getJob()->getArguments());
			}
			elseif ($event->getApplicationServices())
			{
				$event->getApplicationServices()->getLogging()->error(__METHOD__ . ' Elasticsearch services not registered');
			}
		};
		$events->attach('process_Elasticsearch_Index', $callback, 5);

		$callback = function (\Change\Job\Event $event)
		{
			/* TODO Update Mapping*/
		};
		$events->attach('process_Elasticsearch_Mapping', $callback, 5);

		$callBack = function ($event)
		{
			(new \Rbs\User\Job\SendMail())->execute($event);
		};
		$events->attach('process_Rbs_User_SendMail', $callBack, 5);

		$callback = function ($event)
		{
			(new \Rbs\User\Job\CleanAccountRequestTable())->execute($event);
		};
		$events->attach('process_Rbs_User_CleanAccountRequestTable', $callback, 5);
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}
}