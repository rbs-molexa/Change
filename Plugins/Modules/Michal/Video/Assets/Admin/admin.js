(function () {
	"use strict";

	var app = angular.module('RbsChange');

	// Register default editors:
	// Do not declare an editor here if you have an 'geditor.js2' for your Model.
	__change.createEditorsForLocalizedModel('Michal_Video_Video');

	/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.model('Michal_Video').route('home', 'Michal/Video', { 'redirectTo': 'Michal/Video/Video/'});
			$delegate.routesForLocalizedModels(['Michal_Video_Video']);
			return $delegate;
		}]);
	}]);
})();