(function ()
{
	"use strict";

	/**
	 * @param $timeout
	 * @param $http
	 * @param Loading
	 * @param REST
	 * @constructor
	 */
	function Editor($timeout, $http, Loading, REST)
	{
		return {
			restrict : 'C',
			templateUrl : 'Rbs/Catalog/Product/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link: function (scope, elm, attrs, editorCtrl)
			{
				scope.onLoad = function() {
					if (!angular.isArray(scope.document.attributeValues))
					{
						scope.document.attributeValues = [];
					}
				};

				scope.onReady = function() {
					scope.loadCategorizations();
				};

				scope.attributeGroupId = null;

				scope.attributesDef = [];

				scope.loadCategorizations = function() {
					if (scope.document.META$.links.hasOwnProperty('productcategorizations')) {
						REST.collection(scope.document.META$.links['productcategorizations'].href).then(function(result){
							if (angular.isObject(result) && result.hasOwnProperty('resources'))
							{
								scope.categorizations = result.resources;
							}
						});
					}
					else {
						scope.categorizations = [];
					}
				};

				scope.cascadeEditCategorization = function(cat){
					scope.cascade(cat, scope.document.label, function(doc){scope.loadCategorizations();});
				};

				scope.cascadeCreateCategorization = function(doc){
					var newCat = REST.newResource('Rbs_Catalog_ProductCategorization');
					newCat.product = scope.document;
					scope.cascade(newCat, scope.document.label, function(doc){scope.loadCategorizations()});
				};

				scope.toggleHighlight = function(doc){
					var url = null;
					if (!doc.isHighlighted)
					{
						url = doc.META$.actions['downplay'].href;
					}
					else
					{
						url = doc.META$.actions['highlight'].href;
					}
					if (url)
					{
						Loading.start();
						$http.get(url)
							.success(function (data) {
								scope.loadCategorizations();
								Loading.stop();
							})
							.error(function errorCallback(data, status) {
								Loading.stop();
							}
						);
					}
				};

				scope.deleteCategorization = function(doc){
					REST['delete'](doc).then(function(){
						scope.loadCategorizations();
					});
				};

				editorCtrl.init('Rbs_Catalog_Product');

				scope.$watch('document.attribute', function(newValue){
					var attrGrpId = null;
					if (newValue)
					{
						if (angular.isObject(newValue) && newValue.hasOwnProperty('id'))
						{
							attrGrpId = newValue.id;
						}
						else
						{
							attrGrpId = parseInt(newValue, 10);
							if (isNaN(attrGrpId))
							{
								attrGrpId = null;
							}
						}
					}
					
					if (attrGrpId != scope.attributeGroupId)
					{
						scope.clearAttributesEditor();
						scope.attributeGroupId = attrGrpId;
					}
				});

				scope.$watch('document.attributeValues', function(newValue){
					if (angular.isArray(newValue))
					{
						scope.assocValues(scope.attributesDef, newValue);
					}
				});

				scope.$watch('attributeGroupId', function(newValue){
					if (newValue)
					{
						REST.resource('Rbs_Catalog_Attribute', newValue).then(scope.generateAttributesEditor);
					}
				});

				scope.clearAttributesEditor = function (){
					scope.attributesDef = [];
					$timeout(function () {
						scope.$emit('Change:Editor:UpdateMenu');
					});
				};

				scope.generateAttributesEditor = function (attribute) {
					var editorDefinition = attribute.editorDefinition;
					if (angular.isObject(editorDefinition))
					{
						scope.attributesDef = editorDefinition.attributes;
						if (angular.isArray(scope.document.attributeValues))
						{
							scope.assocValues(scope.attributesDef, scope.document.attributeValues);
						}
						else
						{
							scope.assocValues(scope.attributesDef, []);
						}
					}

					$timeout(function () {
						scope.$emit('Change:Editor:UpdateMenu');
					});
				};

				scope.assocValues = function (attributes, attributeValues) {
					for (var i = 0; i < attributes.length; i++)
					{
						if (attributes[i].attributes)
						{
							scope.assocValues(attributes[i].attributes, attributeValues)
						}
						else
						{
							attributes[i].value = scope.getAttributeValue(attributes[i], attributeValues);
						}
					}
				};

				scope.getAttributeValue = function (attribute, attributeValues) {
					var v = null;
					for (var i = 0; i < attributeValues.length; i++)
					{
						v = attributeValues[i];
						if (v.id == attribute.id)
						{
							if (v.value === null && attribute.valueType == 'Property' && 'propertyName' in attribute)
							{
								v.value = scope.document[attribute.propertyName];
							}
							return v;
						}
					}

					var defaultValue = attribute.defaultValue;
					if (attribute.valueType == 'Property' && 'propertyName' in attribute)
					{
						defaultValue = scope.document[attribute.propertyName];
					}

					v = {id: attribute.id, valueType: attribute.valueType, value: defaultValue};
					attributeValues.push(v);
					return v;
				};
			}
		};
	}

	Editor.$inject = ['$timeout', '$http', 'RbsChange.Loading', 'RbsChange.REST'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsCatalogProduct', Editor);
})();