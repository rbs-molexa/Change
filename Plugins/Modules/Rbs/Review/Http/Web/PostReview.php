<?php
namespace Rbs\Review\Http\Web;

use Change\Http\Web\Event;
use Zend\Http\Response as HttpResponse;

/**
* @name \Rbs\Review\Http\Web\PostReview
*/
class PostReview extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param Event $event
	 * @return mixed
	 */
	public function execute(Event $event)
	{
		if ($event->getRequest()->getMethod() === 'POST')
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$data = $event->getRequest()->getPost()->toArray();
			$userId = $data['userId'];
			$user = $documentManager->getDocumentInstance($userId);
			if ($user instanceof \Rbs\User\Documents\User)
			{
				$rating = $data['rating'] ? $data['rating'] : 0;
				$content = $data['content'];
				$target = $documentManager->getDocumentInstance($data['targetId']);
				$section = $documentManager->getDocumentInstance($data['sectionId']);
				if ($content && $target && $section)
				{
					$review = $documentManager->getNewDocumentInstanceByModelName('Rbs_Review_Review');
					/* @var $review \Rbs\Review\Documents\Review */
					$review->setAuthorId($user->getId());
					$review->setPseudonym($user->getLabel());
					$review->setRating($rating);
					$review->setTarget($target);
					$review->setSection($section);
					$review->setContent($content);
					$review->setReviewDate(new \DateTime());
					$tm = $event->getApplicationServices()->getTransactionManager();
					try
					{
						$tm->begin();
						$review->save();
						$tm->commit();
					}
					catch(\Exception $e)
					{
						throw $tm->rollBack($e);
					}
				}
				else
				{
					$data['error'] = 'Invalid parameters';
				}
			}
			else
			{
				$data = [ 'error' => 'Invalid user' ];
			}
			$result = new \Change\Http\Web\Result\AjaxResult($data);
			$event->setResult($result);
		}
	}
}