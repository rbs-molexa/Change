<?php
namespace Michal\Video\Documents;

/**
 * @name \Michal\Video\Documents\Video
 */
class Video extends \Compilation\Michal\Video\Documents\Video
{

	/**
	 * @var \Change\Storage\StorageManager;
	 */
	private $storageManager;

	/**
	 * @throws \RuntimeException
	 * @return \Change\Storage\StorageManager
	 */
	protected function getStorageManager()
	{
		if ($this->storageManager === null)
		{
			throw new \RuntimeException('Storage manager not set', 999999);
		}
		return $this->storageManager;
	}

	public function onDefaultInjection(\Change\Events\Event $event)
	{
		parent::onDefaultInjection($event);
		//Initialize Storage Manager (register change:// scheme)
		$this->storageManager = $event->getApplicationServices()->getStorageManager();
	}

	/**
	 * @return string
	 */
	public function getMimeType()
	{
		return $this->getStorageManager()->getMimeType($this->getFile());
	}

}
