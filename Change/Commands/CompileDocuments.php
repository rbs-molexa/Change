<?php
namespace Change\Commands;

use Change\Commands\Events\Event;

/**
 * @name \Change\Commands\CompileDocuments
 */
class CompileDocuments
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$application = $event->getApplication();
		$applicationServices = $event->getApplicationServices();
		$compiler = new \Change\Documents\Generators\Compiler($application, $applicationServices);
		$compiler->generate();
		$nbModels = count($compiler->getModels());

		$response = $event->getCommandResponse();
		$response->addInfoMessage($nbModels. ' document model compiled.');
	}
}