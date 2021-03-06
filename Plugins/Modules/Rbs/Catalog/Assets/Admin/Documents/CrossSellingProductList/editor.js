(function ()
{
	"use strict";

	function Editor(REST, $routeParams, Breadcrumb, i18n, UrlManager)
	{
		return {
			restrict: 'EA',
			templateUrl: 'Document/Rbs/Catalog/CrossSellingProductList/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link : function (scope, elm, attrs, editorCtrl)
			{
				scope.onReady = function(){
					if ($routeParams.productId)
					{
						//Creation : get Product
						REST.resource('Rbs_Catalog_Product', $routeParams.productId).then(function(product){
							scope.document.product = product;
						});
					}

					if (scope.document.product)
					{
						Breadcrumb.setLocation([
							[i18n.trans('m.rbs.catalog.adminjs.module_name | ucf'), "Rbs/Catalog"],
							[i18n.trans('m.rbs.catalog.adminjs.product_list | ucf'), UrlManager.getUrl(scope.document.product, 'list')],
							[scope.document.product.label, UrlManager.getUrl(scope.document.product, 'form') ],
							[i18n.trans('m.rbs.catalog.adminjs.cross_selling_list | ucf'), "Rbs/Catalog/Product"]],
							[scope.document.label, UrlManager.getUrl(scope.document, 'form')]
						);
					}
				};

				editorCtrl.init('Rbs_Catalog_CrossSellingProductList');
			}
		};
	}

	Editor.$inject = ['RbsChange.REST', '$routeParams', 'RbsChange.Breadcrumb', 'RbsChange.i18n', 'RbsChange.UrlManager'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsCatalogCrossSellingProductList', Editor);
})();
