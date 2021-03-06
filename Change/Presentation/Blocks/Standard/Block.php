<?php
namespace Change\Presentation\Blocks\Standard;

use Change\Http\Web\Result\BlockResult;
use Change\Presentation\Blocks\Parameters;

/**
 * @api
 * @name \Change\Presentation\Blocks\Standard\Block
 */
class Block
{
	/**
	 * @api
	 * Set Block Parameters on $event
	 * Required Event method: getBlockLayout, getApplication, getApplicationServices, getServices, getHttpRequest
	 * Event params includes all params from Http\Event (ex: pathRule and page).
	 * @param \Change\Presentation\Blocks\Event $event
	 * @return \Change\Presentation\Blocks\Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = new Parameters($event->getBlockLayout()->getName());
		return $parameters;
	}

	/**
	 * @api
	 * Set Block Parameters on $event
	 * Required Event method: getBlockLayout, getApplication, getApplicationServices, getServices, getHttpRequest
	 * Event params includes all params from Http\Event (ex: pathRule and page).
	 * @param \Change\Presentation\Blocks\Event $event
	 * @return \Change\Presentation\Blocks\Parameters
	 */
	public function onParameterize($event)
	{
		$parameters = $event->getBlockParameters();
		if (!($parameters instanceof Parameters))
		{
			$parameters = $this->parameterize($event);
			$event->setBlockParameters($parameters);
		}
	}

	/**
	 * @var string
	 */
	protected $templateModuleName;

	/**
	 * @param string $templateModuleName
	 */
	public function setTemplateModuleName($templateModuleName)
	{
		$this->templateModuleName = $templateModuleName;
	}

	/**
	 * @return string
	 */
	public function getTemplateModuleName()
	{
		return $this->templateModuleName;
	}

	/**
	 * @param \Change\Presentation\Blocks\Event $event
	 */
	public function onExecute($event)
	{
		$blockLayout = $event->getBlockLayout();
		$result = new BlockResult($blockLayout->getId(), $blockLayout->getName());
		$event->setBlockResult($result);

		$attributes = new  \ArrayObject(array('parameters' => $event->getBlockParameters(), 'blockId' => $blockLayout->getId()));
		$templateName = $this->execute($event, $attributes);

		if (is_string($templateName) && !$result->hasHtml())
		{
			$applicationServices = $event->getApplicationServices();
			$templateModuleName = $this->getTemplateModuleName();
			if ($templateModuleName === null)
			{
				$sn = explode('_', $blockLayout->getName());
				$templateModuleName = $sn[0] . '_' . $sn[1];
			}
			$this->setTemplateRenderer($applicationServices, $result, $attributes->getArrayCopy(), $templateModuleName,
				$templateName);
		}

		if (!$result->hasHtml())
		{
			$result->setHtml('');
		}
	}

	/**
	 * @api
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * Required Event method: getBlockLayout(), getBlockParameters(), getBlockResult(),
	 *        getApplication, getApplicationServices, getServices, getUrlManager()
	 * @param \Change\Presentation\Blocks\Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		return null;
	}

	/**
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @param \Change\Http\Web\Result\BlockResult $result
	 * @param array $attributes
	 * @param string $templateModuleName
	 * @param string $templateName
	 */
	protected function setTemplateRenderer($applicationServices, $result, $attributes, $templateModuleName, $templateName)
	{
		$relativePath = $applicationServices->getThemeManager()->getCurrent()
			->getTemplateRelativePath($templateModuleName, 'Blocks/' . $templateName);

		$templateManager = $applicationServices->getTemplateManager();
		$result->setHtml($templateManager->renderThemeTemplateFile($relativePath, $attributes));
	}

	/**
	 * @param integer $pageNumber
	 * @param integer $pageCount
	 * @return integer
	 */
	protected function fixPageNumber($pageNumber, $pageCount)
	{
		if (!is_numeric($pageNumber) || $pageNumber < 1 || $pageNumber > $pageCount)
		{
			return 1;
		}
		return $pageNumber;
	}
}